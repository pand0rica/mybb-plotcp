<?php
/**
 * plotcp – ACP module (tools).
 *
 * Plot list with publish/archive/delete, plot add/edit form, category CRUD
 * and plotting group management including approval of user proposals.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

// ACP language file (inc/languages/<lang>/admin/plotcp.lang.php). Without this
// call every $lang->plotcp_* string on this page stays empty.
$lang->load('plotcp');

if (function_exists('plotcp_load_core')) {
    plotcp_load_core();
} else {
    require_once MYBB_ROOT . 'inc/plugins/plotcp/core/database.php';
    require_once MYBB_ROOT . 'inc/plugins/plotcp/core/permissions.php';
    require_once MYBB_ROOT . 'inc/plugins/plotcp/core/alerts.php';
}

$page->add_breadcrumb_item($lang->plotcp, 'index.php?module=tools-plotcp');

$action = $mybb->get_input('action');

function plotcp_acp_nav_tabs(string $active): void
{
    global $page, $lang;

    $tabs = [
        'plots' => [
            'title'       => $lang->plotcp_tab_plots,
            'link'        => 'index.php?module=tools-plotcp',
            'description' => $lang->plotcp_tab_desc_plots,
        ],
        'add' => [
            'title'       => $lang->plotcp_tab_add,
            'link'        => 'index.php?module=tools-plotcp&action=add',
            'description' => $lang->plotcp_tab_desc_add,
        ],
        'categories' => [
            'title'       => $lang->plotcp_tab_categories,
            'link'        => 'index.php?module=tools-plotcp&action=categories',
            'description' => $lang->plotcp_tab_desc_categories,
        ],
        'groups' => [
            'title'       => $lang->plotcp_tab_groups,
            'link'        => 'index.php?module=tools-plotcp&action=groups',
            'description' => $lang->plotcp_tab_desc_groups,
        ],
    ];

    $page->output_nav_tabs($tabs, $active);
}

/** "12.3.1247 – 2.4.1247" (single day: only the start date). */
function plotcp_acp_format_date(array $plot): string
{
    if ((int)$plot['has_date'] !== 1) {
        return '–';
    }

    $start = (int)$plot['start_day'] . '.' . (int)$plot['start_month'] . '.' . (int)$plot['start_year'];
    $end   = (int)$plot['end_day'] . '.' . (int)$plot['end_month'] . '.' . (int)$plot['end_year'];

    return $start === $end ? $start : $start . ' – ' . $end;
}

/**
 * Validates and saves a plot from the ACP form (add or edit). Sends publish
 * alerts to newly added groups when an already published plot is edited.
 * Redirects in every case.
 */
function plotcp_acp_save_plot(int $pid): void
{
    global $mybb, $db, $lang;

    $base_url = 'index.php?module=tools-plotcp';
    $back_url = $pid > 0 ? $base_url . '&action=edit&pid=' . $pid : $base_url . '&action=add';

    $title = trim((string)$mybb->get_input('title'));
    if ($title === '') {
        flash_message($lang->plotcp_error_title_missing, 'error');
        admin_redirect($back_url);
    }

    $cid = (int)$mybb->get_input('cid', MyBB::INPUT_INT);
    if ($cid <= 0 || !Plotcp_Database::getCategory($cid)) {
        flash_message($lang->plotcp_error_invalid_category, 'error');
        admin_redirect($back_url);
    }

    $gids = Plotcp_Database::filterApprovedGids($mybb->get_input('gids', MyBB::INPUT_ARRAY));

    $contacts = Plotcp_Database::parseContactInput((string)$mybb->get_input('contacts'));
    if ($contacts['unknown']) {
        flash_message($lang->sprintf($lang->plotcp_error_unknown_contacts,
            htmlspecialchars_uni(implode(', ', $contacts['unknown']))), 'error');
        admin_redirect($back_url);
    }

    $dates = Plotcp_Database::validateDates(
        $mybb->get_input('has_date', MyBB::INPUT_INT) === 1,
        [
            'start_year' => $mybb->get_input('start_year'), 'start_month' => $mybb->get_input('start_month'),
            'start_day'  => $mybb->get_input('start_day'),
            'end_year'   => $mybb->get_input('end_year'),   'end_month'   => $mybb->get_input('end_month'),
            'end_day'    => $mybb->get_input('end_day'),
        ]
    );
    if (!$dates['ok']) {
        flash_message($lang->{'plotcp_' . $dates['error']}, 'error');
        admin_redirect($back_url);
    }

    $data = array_merge([
        'cid'         => $cid,
        'title'       => $db->escape_string($title),
        'location'    => $db->escape_string(trim((string)$mybb->get_input('location'))),
        'contacts'    => $db->escape_string(implode(',', $contacts['uids'])),
        'description' => $db->escape_string((string)$mybb->get_input('description')),
    ], $dates['fields']);

    if ($pid > 0) {
        $plot = Plotcp_Database::getPlot($pid);
        if (!$plot) {
            flash_message($lang->plotcp_error_invalid_plot, 'error');
            admin_redirect($base_url);
        }
        $db->update_query('plotcp_plots', $data, 'pid = ' . $pid);
        $diff = Plotcp_Database::setPlotGroups($pid, $gids);

        // Members of groups added to an already published plot get the publish
        // alert now – minus everyone already covered by a remaining group.
        if ((int)$plot['visible'] === 1 && $diff['added']) {
            $plot['title'] = $title;
            Plotcp_Alerts::sendPlotPublished($plot, $diff['added'], $diff['kept']);
        }
    } else {
        $data['visible']          = 0;
        $data['archived']         = 0;
        $data['submitted_uid']    = (int)$mybb->user['uid'];
        $data['created_dateline'] = TIME_NOW;
        $pid = (int)$db->insert_query('plotcp_plots', $data);
        Plotcp_Database::setPlotGroups($pid, $gids);
    }

    flash_message($lang->plotcp_admin_plot_saved, 'success');
    admin_redirect($base_url);
}

/* -------------------------------------------------------------------------
 * POST-only state changes (publish / archive / unarchive / delete)
 * ---------------------------------------------------------------------- */

if (in_array($action, ['publish', 'archive', 'unarchive', 'delete'], true)) {
    if ($mybb->request_method !== 'post') {
        admin_redirect('index.php?module=tools-plotcp');
    }
    verify_post_check($mybb->get_input('my_post_key'));

    $pid  = (int)$mybb->get_input('pid', MyBB::INPUT_INT);
    $plot = Plotcp_Database::getPlot($pid);
    if (!$plot) {
        flash_message($lang->plotcp_error_invalid_plot, 'error');
        admin_redirect('index.php?module=tools-plotcp');
    }

    switch ($action) {
        case 'publish':
            if ((int)$plot['visible'] === 1) {
                flash_message($lang->plotcp_error_already_published, 'error');
                admin_redirect('index.php?module=tools-plotcp');
            }
            $db->update_query('plotcp_plots',
                ['visible' => 1, 'published_dateline' => TIME_NOW], 'pid = ' . $pid);
            Plotcp_Alerts::sendPlotPublished($plot, Plotcp_Database::getPlotGids($pid));
            flash_message($lang->plotcp_admin_plot_published, 'success');
            break;

        case 'archive':
            $db->update_query('plotcp_plots', ['archived' => 1], 'pid = ' . $pid);
            flash_message($lang->plotcp_admin_plot_archived, 'success');
            break;

        case 'unarchive':
            $db->update_query('plotcp_plots', ['archived' => 0], 'pid = ' . $pid);
            flash_message($lang->plotcp_admin_plot_unarchived, 'success');
            break;

        case 'delete':
            Plotcp_Database::deletePlot($pid);
            flash_message($lang->plotcp_admin_plot_deleted, 'success');
            break;
    }
    admin_redirect('index.php?module=tools-plotcp');
}

/* -------------------------------------------------------------------------
 * Dispatch
 * ---------------------------------------------------------------------- */

switch ($action) {
    case 'add':
    case 'edit':
        plotcp_acp_plot_form($action === 'edit' ? (int)$mybb->get_input('pid', MyBB::INPUT_INT) : 0);
        break;
    case 'categories':
        plotcp_acp_categories();
        break;
    case 'groups':
        plotcp_acp_groups();
        break;
    default:
        plotcp_acp_plot_list();
        break;
}

/* -------------------------------------------------------------------------
 * Plot list
 * ---------------------------------------------------------------------- */

function plotcp_acp_plot_list(): void
{
    global $page, $db, $mybb, $lang;

    $base_url = 'index.php?module=tools-plotcp';

    $mode  = $mybb->get_input('mode');
    $where = '';
    if ($mode === 'preview') {
        $where = 'p.visible = 0';
    } elseif ($mode === 'active') {
        $where = 'p.visible = 1 AND p.archived = 0';
    } elseif ($mode === 'archived') {
        $where = 'p.archived = 1';
    } else {
        $mode = 'all';
    }

    $page->output_header($lang->plotcp . ' – ' . $lang->plotcp_tab_plots);
    plotcp_acp_nav_tabs('plots');

    // filter links
    $filters = [];
    foreach (['all'      => $lang->plotcp_admin_filter_all,
              'preview'  => $lang->plotcp_admin_filter_preview,
              'active'   => $lang->plotcp_admin_filter_active,
              'archived' => $lang->plotcp_admin_filter_archived] as $key => $label) {
        $url = $base_url . ($key === 'all' ? '' : '&amp;mode=' . $key);
        $filters[] = $mode === $key
            ? '<strong>' . htmlspecialchars_uni($label) . '</strong>'
            : '<a href="' . $url . '">' . htmlspecialchars_uni($label) . '</a>';
    }
    echo '<p>' . implode(' | ', $filters) . '</p>';

    // group counts per plot in one query
    $group_counts = [];
    $query = $db->query('SELECT pid, COUNT(*) AS num FROM ' . TABLE_PREFIX . 'plotcp_plot_groups GROUP BY pid');
    while ($row = $db->fetch_array($query)) {
        $group_counts[(int)$row['pid']] = (int)$row['num'];
    }

    $categories = Plotcp_Database::getCategories();

    $table = new Table;
    $table->construct_header($lang->plotcp_admin_col_title);
    $table->construct_header($lang->plotcp_admin_col_category, ['width' => '15%']);
    $table->construct_header($lang->plotcp_admin_col_status, ['class' => 'align_center', 'width' => '10%']);
    $table->construct_header($lang->plotcp_admin_col_groups, ['class' => 'align_center', 'width' => '8%']);
    $table->construct_header($lang->plotcp_admin_col_date, ['width' => '17%']);
    $table->construct_header($lang->plotcp_admin_col_controls, ['class' => 'align_center', 'width' => '15%']);

    $query = $db->query('SELECT p.* FROM ' . TABLE_PREFIX . 'plotcp_plots p'
        . ($where !== '' ? ' WHERE ' . $where : '')
        . ' ORDER BY p.visible ASC, p.archived ASC, p.title ASC');
    while ($plot = $db->fetch_array($query)) {
        $pid = (int)$plot['pid'];

        if ((int)$plot['visible'] === 0) {
            $status = $lang->plotcp_admin_status_preview;
        } elseif ((int)$plot['archived'] === 1) {
            $status = $lang->plotcp_admin_status_archived;
        } else {
            $status = $lang->plotcp_admin_status_published;
        }

        $cid      = (int)$plot['cid'];
        $category = isset($categories[$cid]) ? $categories[$cid]['name'] : '–';

        $table->construct_cell(htmlspecialchars_uni((string)$plot['title']));
        $table->construct_cell(htmlspecialchars_uni((string)$category));
        $table->construct_cell($status, ['class' => 'align_center']);
        $table->construct_cell(isset($group_counts[$pid]) ? $group_counts[$pid] : 0, ['class' => 'align_center']);
        $table->construct_cell(plotcp_acp_format_date($plot));

        $popup = new PopupMenu('plot_' . $pid, $lang->plotcp_admin_options);
        $popup->add_item($lang->plotcp_admin_edit, $base_url . '&amp;action=edit&amp;pid=' . $pid);
        if ((int)$plot['visible'] === 0) {
            $popup->add_item($lang->plotcp_admin_publish,
                "javascript:document.getElementById('publish_{$pid}').submit();");
        } elseif ((int)$plot['archived'] === 0) {
            $popup->add_item($lang->plotcp_admin_archive,
                "javascript:document.getElementById('archive_{$pid}').submit();");
        } else {
            $popup->add_item($lang->plotcp_admin_unarchive,
                "javascript:document.getElementById('unarchive_{$pid}').submit();");
        }
        $popup->add_item($lang->plotcp_admin_delete,
            "javascript:document.getElementById('delete_{$pid}').submit();",
            "return confirm('" . addslashes($lang->plotcp_admin_plot_delete_confirm) . "');");

        $forms = '';
        foreach (['publish', 'archive', 'unarchive', 'delete'] as $act) {
            $forms .= '<form id="' . $act . '_' . $pid . '" method="post" action="'
                . htmlspecialchars_uni($base_url . '&action=' . $act) . '" style="display:none;">'
                . '<input type="hidden" name="pid" value="' . $pid . '" />'
                . '<input type="hidden" name="my_post_key" value="' . $mybb->post_code . '" />'
                . '</form>';
        }

        $table->construct_cell($popup->fetch() . $forms, ['class' => 'align_center']);
        $table->construct_row();
    }

    if ($table->num_rows() === 0) {
        $table->construct_cell($lang->plotcp_admin_no_plots, ['colspan' => 6]);
        $table->construct_row();
    }

    $table->output($lang->plotcp_tab_plots);
    $page->output_footer();
}

/* -------------------------------------------------------------------------
 * Plot add/edit form
 * ---------------------------------------------------------------------- */

function plotcp_acp_plot_form(int $pid): void
{
    global $page, $db, $mybb, $lang;

    $base_url = 'index.php?module=tools-plotcp';

    if ($mybb->request_method === 'post') {
        verify_post_check($mybb->get_input('my_post_key'));
        plotcp_acp_save_plot($pid);
    }

    $plot = [];
    $plot_gids = [];
    if ($pid > 0) {
        $plot = Plotcp_Database::getPlot($pid);
        if (!$plot) {
            flash_message($lang->plotcp_error_invalid_plot, 'error');
            admin_redirect($base_url);
        }
        $plot_gids = Plotcp_Database::getPlotGids($pid);
    }

    $categories = Plotcp_Database::getCategories();
    $groups     = Plotcp_Database::getGroups(true);

    $form_title = $pid > 0 ? $lang->plotcp_admin_edit_plot : $lang->plotcp_admin_add_plot;
    $form_url   = $pid > 0 ? $base_url . '&action=edit&pid=' . $pid : $base_url . '&action=add';

    $page->output_header($lang->plotcp . ' – ' . $form_title);
    plotcp_acp_nav_tabs($pid > 0 ? 'plots' : 'add');

    if (!$categories) {
        echo '<p>' . htmlspecialchars_uni($lang->plotcp_admin_no_categories) . '</p>';
        $page->output_footer();

        return;
    }

    $form = new Form($form_url, 'post');
    $container = new FormContainer($form_title);

    $container->output_row($lang->plotcp_field_title, '',
        $form->generate_text_box('title', (string)(isset($plot['title']) ? $plot['title'] : '')), 'title');

    $cat_options = [];
    foreach ($categories as $cid => $category) {
        $cat_options[$cid] = $category['name'];
    }
    $container->output_row($lang->plotcp_field_category, '',
        $form->generate_select_box('cid', $cat_options,
            [(int)(isset($plot['cid']) ? $plot['cid'] : 0)]), 'cid');

    $checkboxes = '';
    foreach ($groups as $gid => $group) {
        $checkboxes .= '<label style="display:block;">'
            . $form->generate_check_box('gids[]', $gid, htmlspecialchars_uni((string)$group['name']),
                ['checked' => in_array((int)$gid, $plot_gids, true)])
            . '</label>';
    }
    if ($checkboxes === '') {
        $checkboxes = htmlspecialchars_uni($lang->plotcp_admin_no_groups);
    }
    $container->output_row($lang->plotcp_field_groups, $lang->plotcp_field_groups_desc, $checkboxes, 'gids');

    $container->output_row($lang->plotcp_field_location, '',
        $form->generate_text_box('location', (string)(isset($plot['location']) ? $plot['location'] : '')), 'location');

    // stored uid CSV -> username CSV for the form
    $contact_names = '';
    if (!empty($plot['contacts'])) {
        $users = Plotcp_Database::getUsersByUids(explode(',', (string)$plot['contacts']));
        $names = [];
        foreach ($users as $user) {
            $names[] = $user['username'];
        }
        $contact_names = implode(', ', $names);
    }
    $container->output_row($lang->plotcp_field_contacts, $lang->plotcp_field_contacts_desc,
        $form->generate_text_box('contacts', $contact_names), 'contacts');

    $container->output_row($lang->plotcp_field_description, '',
        $form->generate_text_area('description',
            (string)(isset($plot['description']) ? $plot['description'] : ''), ['rows' => 8]), 'description');

    $has_date = !empty($plot['has_date']);
    $container->output_row($lang->plotcp_field_date, $lang->plotcp_field_date_desc,
        $form->generate_check_box('has_date', 1, '', ['checked' => $has_date]), 'has_date');

    $date_inputs = function (string $prefix) use ($form, $plot, $has_date): string {
        $day   = $has_date ? (string)$plot[$prefix . '_day'] : '';
        $month = $has_date ? (string)$plot[$prefix . '_month'] : '';
        $year  = $has_date ? (string)$plot[$prefix . '_year'] : '';
        // single-day plots show an empty end block in the form
        if ($prefix === 'end' && $has_date
            && $plot['start_key'] === $plot['end_key']) {
            $day = $month = $year = '';
        }

        return $form->generate_text_box($prefix . '_day', $day, ['style' => 'width:3em']) . ' . '
            . $form->generate_text_box($prefix . '_month', $month, ['style' => 'width:3em']) . ' . '
            . $form->generate_text_box($prefix . '_year', $year, ['style' => 'width:6em']);
    };
    $container->output_row($lang->plotcp_field_start, '', $date_inputs('start'), 'start_day');
    $container->output_row($lang->plotcp_field_end, '', $date_inputs('end'), 'end_day');

    $container->end();

    $buttons   = [];
    $buttons[] = $form->generate_submit_button($lang->plotcp_admin_save);
    $form->output_submit_wrapper($buttons);
    $form->end();

    $page->output_footer();
}

/* -------------------------------------------------------------------------
 * Categories (CRUD on one page)
 * ---------------------------------------------------------------------- */

function plotcp_acp_categories(): void
{
    global $page, $db, $mybb, $lang;

    $base_url = 'index.php?module=tools-plotcp&action=categories';

    if ($mybb->request_method === 'post') {
        verify_post_check($mybb->get_input('my_post_key'));

        $sub_action = $mybb->get_input('sub_action');
        $cid        = (int)$mybb->get_input('cid', MyBB::INPUT_INT);

        if ($sub_action === 'delete') {
            if (Plotcp_Database::categoryHasPlots($cid)) {
                flash_message($lang->plotcp_admin_category_has_plots, 'error');
                admin_redirect($base_url);
            }
            $db->delete_query('plotcp_categories', 'cid = ' . $cid);
            flash_message($lang->plotcp_admin_category_deleted, 'success');
            admin_redirect($base_url);
        }

        $name = trim((string)$mybb->get_input('name'));
        if ($name === '') {
            flash_message($lang->plotcp_error_category_name_missing, 'error');
            admin_redirect($base_url);
        }
        $data = [
            'name'          => $db->escape_string($name),
            'description'   => $db->escape_string(trim((string)$mybb->get_input('description'))),
            'display_order' => (int)$mybb->get_input('display_order', MyBB::INPUT_INT),
        ];

        if ($cid > 0 && $sub_action === 'edit') {
            $db->update_query('plotcp_categories', $data, 'cid = ' . $cid);
        } else {
            $db->insert_query('plotcp_categories', $data);
        }
        flash_message($lang->plotcp_admin_category_saved, 'success');
        admin_redirect($base_url);
    }

    $is_edit  = $mybb->get_input('do') === 'edit';
    $edit_id  = $is_edit ? (int)$mybb->get_input('cid', MyBB::INPUT_INT) : 0;
    $edit_row = $edit_id > 0 ? Plotcp_Database::getCategory($edit_id) : null;

    $page->output_header($lang->plotcp . ' – ' . $lang->plotcp_tab_categories);
    plotcp_acp_nav_tabs('categories');

    $table = new Table;
    $table->construct_header($lang->plotcp_admin_col_name);
    $table->construct_header($lang->plotcp_admin_col_description);
    $table->construct_header($lang->plotcp_admin_col_order, ['class' => 'align_center', 'width' => '10%']);
    $table->construct_header($lang->plotcp_admin_col_controls, ['class' => 'align_center', 'width' => '20%']);

    foreach (Plotcp_Database::getCategories() as $cid => $category) {
        $table->construct_cell(htmlspecialchars_uni((string)$category['name']));
        $table->construct_cell(htmlspecialchars_uni((string)$category['description']));
        $table->construct_cell((int)$category['display_order'], ['class' => 'align_center']);

        $popup = new PopupMenu('cat_' . $cid, $lang->plotcp_admin_options);
        $popup->add_item($lang->plotcp_admin_edit, $base_url . '&amp;do=edit&amp;cid=' . $cid);
        $popup->add_item($lang->plotcp_admin_delete,
            "javascript:document.getElementById('delete_cat_{$cid}').submit();",
            "return confirm('" . addslashes($lang->plotcp_admin_category_delete_confirm) . "');");

        $delete_form = '<form id="delete_cat_' . $cid . '" method="post" action="'
            . htmlspecialchars_uni($base_url) . '" style="display:none;">'
            . '<input type="hidden" name="sub_action" value="delete" />'
            . '<input type="hidden" name="cid" value="' . $cid . '" />'
            . '<input type="hidden" name="my_post_key" value="' . $mybb->post_code . '" />'
            . '</form>';

        $table->construct_cell($popup->fetch() . $delete_form, ['class' => 'align_center']);
        $table->construct_row();
    }

    if ($table->num_rows() === 0) {
        $table->construct_cell($lang->plotcp_admin_no_categories, ['colspan' => 4]);
        $table->construct_row();
    }

    $table->output($lang->plotcp_tab_categories);

    $form_title   = $edit_row ? $lang->plotcp_admin_edit_category : $lang->plotcp_admin_add_category;

    $form = new Form($base_url, 'post');
    echo $form->generate_hidden_field('sub_action', $edit_row ? 'edit' : 'add');
    if ($edit_row) {
        echo $form->generate_hidden_field('cid', $edit_id);
    }

    $container = new FormContainer($form_title);
    $container->output_row($lang->plotcp_admin_col_name, '',
        $form->generate_text_box('name', (string)($edit_row ? $edit_row['name'] : '')), 'name');
    $container->output_row($lang->plotcp_admin_col_description, '',
        $form->generate_text_box('description', (string)($edit_row ? $edit_row['description'] : '')), 'description');
    $container->output_row($lang->plotcp_admin_col_order, '',
        $form->generate_numeric_field('display_order',
            (int)($edit_row ? $edit_row['display_order'] : 0), ['min' => 0]), 'display_order');
    $container->end();

    $buttons   = [];
    $buttons[] = $form->generate_submit_button($lang->plotcp_admin_save);
    if ($edit_row) {
        $buttons[] = '<a class="button" href="' . htmlspecialchars_uni($base_url) . '">'
            . htmlspecialchars_uni($lang->plotcp_admin_cancel) . '</a>';
    }
    $form->output_submit_wrapper($buttons);
    $form->end();

    $page->output_footer();
}

/* -------------------------------------------------------------------------
 * Plotting groups (list with approval, add/edit form)
 * ---------------------------------------------------------------------- */

function plotcp_acp_groups(): void
{
    global $page, $db, $mybb, $lang;

    $base_url = 'index.php?module=tools-plotcp&action=groups';

    if ($mybb->request_method === 'post') {
        verify_post_check($mybb->get_input('my_post_key'));

        $sub_action = $mybb->get_input('sub_action');
        $gid        = (int)$mybb->get_input('gid', MyBB::INPUT_INT);

        if ($sub_action === 'approve') {
            $group = Plotcp_Database::getGroup($gid);
            if (!$group || (int)$group['approved'] === 1) {
                flash_message($lang->plotcp_error_invalid_group, 'error');
                admin_redirect($base_url);
            }
            $db->update_query('plotcp_groups', ['approved' => 1], 'gid = ' . $gid);
            Plotcp_Alerts::sendNewGroup($group);
            flash_message($lang->plotcp_admin_group_approved, 'success');
            admin_redirect($base_url);
        }

        if ($sub_action === 'delete') {
            Plotcp_Database::deleteGroup($gid);
            flash_message($lang->plotcp_admin_group_deleted, 'success');
            admin_redirect($base_url);
        }

        $name = trim((string)$mybb->get_input('name'));
        if ($name === '') {
            flash_message($lang->plotcp_error_group_name_missing, 'error');
            admin_redirect($base_url);
        }
        if (Plotcp_Database::groupNameExists($name, $sub_action === 'edit' ? $gid : 0)) {
            flash_message($lang->plotcp_error_group_name_exists, 'error');
            admin_redirect($base_url);
        }

        $data = [
            'name'          => $db->escape_string($name),
            'description'   => $db->escape_string(trim((string)$mybb->get_input('description'))),
            'display_order' => (int)$mybb->get_input('display_order', MyBB::INPUT_INT),
        ];

        if ($gid > 0 && $sub_action === 'edit') {
            $db->update_query('plotcp_groups', $data, 'gid = ' . $gid);
        } else {
            // Groups created by the team in the ACP are approved immediately,
            // and users are alerted about the new group right away.
            $data['approved'] = 1;
            $data['submitted_uid'] = (int)$mybb->user['uid'];
            $data['dateline'] = TIME_NOW;
            $gid = (int)$db->insert_query('plotcp_groups', $data);
            Plotcp_Alerts::sendNewGroup(array_merge($data, ['gid' => $gid, 'name' => $name]));
        }
        flash_message($lang->plotcp_admin_group_saved, 'success');
        admin_redirect($base_url);
    }

    $is_edit  = $mybb->get_input('do') === 'edit';
    $edit_id  = $is_edit ? (int)$mybb->get_input('gid', MyBB::INPUT_INT) : 0;
    $edit_row = $edit_id > 0 ? Plotcp_Database::getGroup($edit_id) : null;

    $page->output_header($lang->plotcp . ' – ' . $lang->plotcp_tab_groups);
    plotcp_acp_nav_tabs('groups');

    $member_counts = Plotcp_Database::getGroupMemberCounts();

    // usernames of proposal submitters
    $groups = Plotcp_Database::getGroups(false);
    $submitter_uids = [];
    foreach ($groups as $group) {
        if ((int)$group['submitted_uid'] > 0) {
            $submitter_uids[] = (int)$group['submitted_uid'];
        }
    }
    $submitters = Plotcp_Database::getUsersByUids($submitter_uids);

    $table = new Table;
    $table->construct_header($lang->plotcp_admin_col_name);
    $table->construct_header($lang->plotcp_admin_col_description);
    $table->construct_header($lang->plotcp_admin_col_members, ['class' => 'align_center', 'width' => '10%']);
    $table->construct_header($lang->plotcp_admin_col_status, ['class' => 'align_center', 'width' => '15%']);
    $table->construct_header($lang->plotcp_admin_col_controls, ['class' => 'align_center', 'width' => '20%']);

    foreach ($groups as $gid => $group) {
        $pending = (int)$group['approved'] === 0;

        $status = $pending ? $lang->plotcp_admin_pending : $lang->plotcp_admin_approved_label;
        if ($pending && isset($submitters[(int)$group['submitted_uid']])) {
            $status .= '<br /><small>' . htmlspecialchars_uni($lang->plotcp_admin_submitted_by) . ': '
                . htmlspecialchars_uni($submitters[(int)$group['submitted_uid']]['username']) . '</small>';
        }

        $table->construct_cell(htmlspecialchars_uni((string)$group['name']));
        $table->construct_cell(htmlspecialchars_uni((string)$group['description']));
        $table->construct_cell(isset($member_counts[$gid]) ? $member_counts[$gid] : 0, ['class' => 'align_center']);
        $table->construct_cell($status, ['class' => 'align_center']);

        $popup = new PopupMenu('group_' . $gid, $lang->plotcp_admin_options);
        if ($pending) {
            $popup->add_item($lang->plotcp_admin_approve,
                "javascript:document.getElementById('approve_group_{$gid}').submit();");
        }
        $popup->add_item($lang->plotcp_admin_edit, $base_url . '&amp;do=edit&amp;gid=' . $gid);
        $popup->add_item($lang->plotcp_admin_delete,
            "javascript:document.getElementById('delete_group_{$gid}').submit();",
            "return confirm('" . addslashes($lang->plotcp_admin_group_delete_confirm) . "');");

        $forms = '';
        foreach (['approve', 'delete'] as $act) {
            $forms .= '<form id="' . $act . '_group_' . $gid . '" method="post" action="'
                . htmlspecialchars_uni($base_url) . '" style="display:none;">'
                . '<input type="hidden" name="sub_action" value="' . $act . '" />'
                . '<input type="hidden" name="gid" value="' . $gid . '" />'
                . '<input type="hidden" name="my_post_key" value="' . $mybb->post_code . '" />'
                . '</form>';
        }

        $table->construct_cell($popup->fetch() . $forms, ['class' => 'align_center']);
        $table->construct_row();
    }

    if ($table->num_rows() === 0) {
        $table->construct_cell($lang->plotcp_admin_no_groups, ['colspan' => 5]);
        $table->construct_row();
    }

    $table->output($lang->plotcp_tab_groups);

    $form_title = $edit_row ? $lang->plotcp_admin_edit_group : $lang->plotcp_admin_add_group;

    $form = new Form($base_url, 'post');
    echo $form->generate_hidden_field('sub_action', $edit_row ? 'edit' : 'add');
    if ($edit_row) {
        echo $form->generate_hidden_field('gid', $edit_id);
    }

    $container = new FormContainer($form_title);
    $container->output_row($lang->plotcp_admin_col_name, '',
        $form->generate_text_box('name', (string)($edit_row ? $edit_row['name'] : '')), 'name');
    $container->output_row($lang->plotcp_admin_col_description, '',
        $form->generate_text_area('description', (string)($edit_row ? $edit_row['description'] : '')), 'description');
    $container->output_row($lang->plotcp_admin_col_order, '',
        $form->generate_numeric_field('display_order',
            (int)($edit_row ? $edit_row['display_order'] : 0), ['min' => 0]), 'display_order');
    $container->end();

    $buttons   = [];
    $buttons[] = $form->generate_submit_button($lang->plotcp_admin_save);
    if ($edit_row) {
        $buttons[] = '<a class="button" href="' . htmlspecialchars_uni($base_url) . '">'
            . htmlspecialchars_uni($lang->plotcp_admin_cancel) . '</a>';
    }
    $form->output_submit_wrapper($buttons);
    $form->end();

    $page->output_footer();
}
