<?php
/**
 * plotcp – frontend entry point.
 *
 * Views: plot browse (category tabs, plot nav by year, detail), plot detail with
 * linked scenes, groups page (join/leave, propose), plot submission form.
 * Every state-changing action requires POST + verify_post_check().
 */

define('IN_MYBB', 1);
define('THIS_SCRIPT', 'plotcp.php');

$templatelist = 'plotcp_browse,plotcp_browse_body,plotcp_browse_nav,plotcp_detail,plotcp_index_empty,'
    . 'plotcp_view_scene_bit,plotcp_view_teambar,plotcp_submit_form,plotcp_edit_form,plotcp_random_plot';

require_once './global.php';

// Inactive plugin: the hook/loader functions from inc/plugins/plotcp.php are
// not loaded, so bail out with the default error instead of fataling.
if (!function_exists('plotcp_load_core')) {
    error_no_permission();
}

plotcp_load_core();
require_once MYBB_ROOT . 'inc/plugins/plotcp/core/frontend.php';

$lang->load('plotcp');

add_breadcrumb($lang->plotcp, 'plotcp.php');

$action = $mybb->get_input('action');

/* -------------------------------------------------------------------------
 * POST actions
 * ---------------------------------------------------------------------- */

$post_actions = ['do_submit', 'do_edit', 'publish', 'link_thread', 'unlink_thread'];

if (in_array($action, $post_actions, true)) {
    if ($mybb->request_method !== 'post') {
        error_no_permission();
    }
    verify_post_check($mybb->get_input('my_post_key'));

    if ((int)$mybb->user['uid'] <= 0) {
        error_no_permission();
    }
}

switch ($action) {
    /* ---- plot submission ---------------------------------------------- */
    case 'do_submit':
        if (!isset($mybb->settings['plotcp_allow_plot_submissions'])
            || (int)$mybb->settings['plotcp_allow_plot_submissions'] !== 1
        ) {
            error($lang->plotcp_error_submissions_disabled);
        }

        $title = trim((string)$mybb->get_input('title'));
        if ($title === '') {
            error($lang->plotcp_error_title_missing);
        }

        $cid = (int)$mybb->get_input('cid', MyBB::INPUT_INT);
        if ($cid <= 0 || !Plotcp_Database::getCategory($cid)) {
            error($lang->plotcp_error_invalid_category);
        }

        $gids = Plotcp_Groups::filterApprovedGids($mybb->get_input('gids', MyBB::INPUT_ARRAY));

        $contacts = Plotcp_Database::parseContactInput((string)$mybb->get_input('contacts'));
        if ($contacts['unknown']) {
            error($lang->sprintf($lang->plotcp_error_unknown_contacts,
                htmlspecialchars_uni(implode(', ', $contacts['unknown']))));
        }

        // The inplay date is mandatory (drives the year navigation).
        $dates = Plotcp_Database::validateDates(true, [
            'start_year' => $mybb->get_input('start_year'), 'start_month' => $mybb->get_input('start_month'),
            'start_day'  => $mybb->get_input('start_day'),
            'end_year'   => $mybb->get_input('end_year'),   'end_month'   => $mybb->get_input('end_month'),
            'end_day'    => $mybb->get_input('end_day'),
        ]);
        if (!$dates['ok']) {
            error($lang->{'plotcp_' . $dates['error']});
        }

        $pid = (int)$db->insert_query('plotcp_plots', array_merge([
            'cid'              => $cid,
            'title'            => $db->escape_string($title),
            'location'         => $db->escape_string(trim((string)$mybb->get_input('location'))),
            'contacts'         => $db->escape_string(implode(',', $contacts['uids'])),
            'description'      => $db->escape_string((string)$mybb->get_input('description')),
            'visible'          => 0,
            'submitted_uid'    => (int)$mybb->user['uid'],
            'created_dateline' => TIME_NOW,
        ], $dates['fields']));
        Plotcp_Database::setPlotGroups($pid, $gids);

        Plotcp_Alerts::sendSubmissionTeam('plot', $title, 'plotcp.php?action=view&pid=' . $pid, $pid);

        redirect('plotcp.php?action=view&pid=' . $pid, $lang->plotcp_plot_submitted);
        break;

    /* ---- full plot edit (team, or submitter of own preview) ------------- */
    case 'do_edit':
        $pid  = (int)$mybb->get_input('pid', MyBB::INPUT_INT);
        $plot = Plotcp_Database::getPlot($pid);
        if (!$plot) {
            error($lang->plotcp_error_invalid_plot);
        }
        if (!Plotcp_Permissions::canEditPlot($mybb->user, $plot)) {
            error_no_permission();
        }

        $title = trim((string)$mybb->get_input('title'));
        if ($title === '') {
            error($lang->plotcp_error_title_missing);
        }

        $cid = (int)$mybb->get_input('cid', MyBB::INPUT_INT);
        if ($cid <= 0 || !Plotcp_Database::getCategory($cid)) {
            error($lang->plotcp_error_invalid_category);
        }

        $gids = Plotcp_Groups::filterApprovedGids($mybb->get_input('gids', MyBB::INPUT_ARRAY));

        $contacts = Plotcp_Database::parseContactInput((string)$mybb->get_input('contacts'));
        if ($contacts['unknown']) {
            error($lang->sprintf($lang->plotcp_error_unknown_contacts,
                htmlspecialchars_uni(implode(', ', $contacts['unknown']))));
        }

        $dates = Plotcp_Database::validateDates(true, [
            'start_year' => $mybb->get_input('start_year'), 'start_month' => $mybb->get_input('start_month'),
            'start_day'  => $mybb->get_input('start_day'),
            'end_year'   => $mybb->get_input('end_year'),   'end_month'   => $mybb->get_input('end_month'),
            'end_day'    => $mybb->get_input('end_day'),
        ]);
        if (!$dates['ok']) {
            error($lang->{'plotcp_' . $dates['error']});
        }

        $db->update_query('plotcp_plots', array_merge([
            'cid'         => $cid,
            'title'       => $db->escape_string($title),
            'location'    => $db->escape_string(trim((string)$mybb->get_input('location'))),
            'contacts'    => $db->escape_string(implode(',', $contacts['uids'])),
            'description' => $db->escape_string((string)$mybb->get_input('description')),
        ], $dates['fields']), 'pid = ' . $pid);

        // groups added to an already published plot alert only the new members
        $diff = Plotcp_Database::setPlotGroups($pid, $gids);
        if ((int)$plot['visible'] === 1 && $diff['added']) {
            $plot['title'] = $title;
            Plotcp_Alerts::sendPlotPublished($plot, $diff['added'], $diff['kept']);
        }

        redirect('plotcp.php?action=view&pid=' . $pid, $lang->plotcp_plot_updated);
        break;

    /* ---- publish (team) -------------------------------------------------- */
    case 'publish':
        if (!Plotcp_Permissions::isTeam($mybb->user)) {
            error_no_permission();
        }

        $pid  = (int)$mybb->get_input('pid', MyBB::INPUT_INT);
        $plot = Plotcp_Database::getPlot($pid);
        if (!$plot) {
            error($lang->plotcp_error_invalid_plot);
        }
        if ((int)$plot['visible'] === 1) {
            error($lang->plotcp_error_already_published);
        }
        $db->update_query('plotcp_plots',
            ['visible' => 1, 'published_dateline' => TIME_NOW], 'pid = ' . $pid);
        Plotcp_Alerts::sendPlotPublished($plot, Plotcp_Database::getPlotGids($pid));
        redirect('plotcp.php?action=view&pid=' . $pid, $lang->plotcp_published);
        break;

    /* ---- scene linking --------------------------------------------------- */
    case 'link_thread':
    case 'unlink_thread':
        $tid    = (int)$mybb->get_input('tid', MyBB::INPUT_INT);
        $thread = get_thread($tid);
        if (!$thread || (int)$thread['visible'] < 0) {
            error($lang->error_invalidthread);
        }
        // never trust the form: repeat the forum check server-side
        if (!Plotcp_Permissions::isSceneForum((int)$thread['fid'])) {
            error($lang->plotcp_error_not_scene_forum);
        }
        if (!Plotcp_Permissions::canLinkThread($mybb->user, $thread)) {
            error_no_permission();
        }

        if ($action === 'link_thread') {
            $pid  = (int)$mybb->get_input('pid', MyBB::INPUT_INT);
            $plot = Plotcp_Database::getPlot($pid);
            if (!$plot) {
                error($lang->plotcp_error_invalid_plot);
            }
            if ((int)$plot['visible'] === 0 && !Plotcp_Permissions::isTeam($mybb->user)) {
                error($lang->plotcp_error_invalid_plot);
            }

            $existing = $db->fetch_array($db->simple_select('plotcp_plot_threads', 'id', 'tid = ' . $tid));
            if ($existing) {
                error($lang->plotcp_error_already_linked);
            }
            // INSERT IGNORE + UNIQUE(tid) closes the remaining race window
            $db->write_query('INSERT IGNORE INTO ' . TABLE_PREFIX . 'plotcp_plot_threads
                (pid, tid, uid, dateline)
                VALUES (' . $pid . ', ' . $tid . ', ' . (int)$mybb->user['uid'] . ', ' . TIME_NOW . ')');

            redirect(get_thread_link($tid), $lang->plotcp_thread_linked);
        } else {
            $db->delete_query('plotcp_plot_threads', 'tid = ' . $tid);
            redirect(get_thread_link($tid), $lang->plotcp_thread_unlinked);
        }
        break;

    /* ---- views ------------------------------------------------------------ */
    case 'view':
        $pid  = (int)$mybb->get_input('pid', MyBB::INPUT_INT);
        $plot = Plotcp_Database::getPlot($pid);
        if (!$plot) {
            error($lang->plotcp_error_invalid_plot);
        }
        if ((int)$plot['visible'] === 0 && !Plotcp_Permissions::canViewPreview($mybb->user, $plot)) {
            error($lang->plotcp_error_invalid_plot);
        }

        add_breadcrumb(htmlspecialchars_uni((string)$plot['title']));
        output_page(Plotcp_Frontend::renderBrowse((int)$plot['cid'], (int)$plot['pid']));
        break;

    case 'edit':
        $pid  = (int)$mybb->get_input('pid', MyBB::INPUT_INT);
        $plot = Plotcp_Database::getPlot($pid);
        if (!$plot) {
            error($lang->plotcp_error_invalid_plot);
        }
        if (!Plotcp_Permissions::canEditPlot($mybb->user, $plot)) {
            error_no_permission();
        }

        add_breadcrumb(htmlspecialchars_uni((string)$plot['title']),
            'plotcp.php?action=view&amp;pid=' . (int)$plot['pid']);
        add_breadcrumb($lang->plotcp_edit_title);
        output_page(Plotcp_Frontend::renderEditForm($plot));
        break;

    case 'submit':
        if ((int)$mybb->user['uid'] <= 0) {
            error_no_permission();
        }
        if (!isset($mybb->settings['plotcp_allow_plot_submissions'])
            || (int)$mybb->settings['plotcp_allow_plot_submissions'] !== 1
        ) {
            error($lang->plotcp_error_submissions_disabled);
        }
        if (!Plotcp_Database::getCategories()) {
            error($lang->plotcp_error_invalid_category);
        }

        add_breadcrumb($lang->plotcp_submit_title);
        output_page(Plotcp_Frontend::renderSubmitForm());
        break;


    default:
        output_page(Plotcp_Frontend::renderBrowse(
            (int)$mybb->get_input('cat', MyBB::INPUT_INT),
            (int)$mybb->get_input('pid', MyBB::INPUT_INT)
        ));
        break;
}
