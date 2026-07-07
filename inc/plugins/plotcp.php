<?php
/**
 * Plot Control Panel (plotcp) – plot management for RPG forums.
 *
 * Plots are organised in categories, linked to plotting groups (users join
 * groups themselves), created as previews and published with a MyAlerts
 * notification to all group members. Users can submit plots and propose new
 * groups (team approval required). Inplay threads can be linked to plots,
 * and dated plots are delivered to the RPG Calendar plugin via its
 * 'rpgcalendar_collect_events' provider hook (no hard dependency in either
 * direction).
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

$plugins->add_hook('global_start',                   'plotcp_register_alert_formatter');
$plugins->add_hook('xmlhttp',                        'plotcp_register_alert_formatter');
$plugins->add_hook('showthread_start',               'plotcp_handle_showthread');
$plugins->add_hook('newthread_start',                'plotcp_handle_newthread');
$plugins->add_hook('newthread_do_newthread_end',     'plotcp_handle_newthread_save');
$plugins->add_hook('forumdisplay_thread',            'plotcp_handle_forumdisplay_thread');
$plugins->add_hook('rpgcalendar_collect_events',     'plotcp_rpgcalendar_collect');
$plugins->add_hook('class_moderation_delete_thread', 'plotcp_handle_thread_delete');
$plugins->add_hook('admin_tools_menu',               'plotcp_admin_menu');
$plugins->add_hook('admin_tools_action_handler',     'plotcp_admin_action');

function plotcp_info(): array
{
    return [
        'name'          => 'Plot Control Panel',
        'description'   => 'Plot management for RPG forums: categories, plotting groups with self-service membership, preview/publish workflow with MyAlerts notifications, user submissions, scene linking and RPG Calendar integration.',
        'website'       => 'https://github.com/pand0rica/plot-cp',
        'author'        => 'pand0rica',
        'authorsite'    => 'https://github.com/pand0rica',
        'version'       => '1.0.0',
        'guid'          => '',
        'codename'      => 'plotcp',
        'compatibility' => '18*',
    ];
}

function plotcp_install(): void
{
    require_once MYBB_ROOT . 'inc/plugins/plotcp/core/installer.php';
    Plotcp_Installer::createTables();
}

function plotcp_is_installed(): bool
{
    global $db;

    return $db->table_exists('plotcp_plots');
}

function plotcp_activate(): void
{
    require_once MYBB_ROOT . 'inc/plugins/plotcp/core/installer.php';
    Plotcp_Installer::installSettings();
    Plotcp_Installer::installTemplates();
    Plotcp_Installer::installStylesheet();
    Plotcp_Installer::registerAlertTypes();
    Plotcp_Installer::injectSceneBox();
}

function plotcp_deactivate(): void
{
    require_once MYBB_ROOT . 'inc/plugins/plotcp/core/installer.php';
    Plotcp_Installer::removeSceneBoxInjection();
    Plotcp_Installer::unregisterAlertTypes();
    Plotcp_Installer::removeStylesheet();
    Plotcp_Installer::removeTemplates();
    Plotcp_Installer::removeSettings();
}

function plotcp_uninstall(): void
{
    require_once MYBB_ROOT . 'inc/plugins/plotcp/core/installer.php';
    Plotcp_Installer::dropTables();
}

/**
 * Loads the core classes once. Handlers call this lazily so ordinary page
 * loads that never touch plotcp data do not pay the include cost.
 */
function plotcp_load_core(): void
{
    static $loaded = false;

    if ($loaded) {
        return;
    }
    require_once MYBB_ROOT . 'inc/plugins/plotcp/core/database.php';
    require_once MYBB_ROOT . 'inc/plugins/plotcp/core/permissions.php';
    require_once MYBB_ROOT . 'inc/plugins/plotcp/core/alerts.php';
    $loaded = true;
}

/**
 * Alert type codes shared by installer (register/unregister), alert sending
 * and formatter registration. Single source of truth.
 */
function plotcp_alert_codes(): array
{
    return [
        'plotcp_plot_published',
        'plotcp_new_group',
        'plotcp_submission_team',
    ];
}

/**
 * Registers one formatter per alert type. Best effort: silently does nothing
 * when MyAlerts is missing or incompatible. Runs at global_start and xmlhttp
 * (bell dropdown) with a static guard against double registration.
 */
function plotcp_register_alert_formatter(): void
{
    static $registered = false;
    global $mybb, $lang;

    if ($registered) {
        return;
    }

    if (!class_exists('MybbStuff_MyAlerts_AlertFormatterManager')
        || !class_exists('MybbStuff_MyAlerts_Formatter_AbstractFormatter')
    ) {
        return;
    }

    $lang->load('plotcp_myalerts');

    $manager = MybbStuff_MyAlerts_AlertFormatterManager::getInstance();
    if (!$manager) {
        $manager = MybbStuff_MyAlerts_AlertFormatterManager::createInstance($mybb, $lang);
    }
    if (!$manager) {
        return;
    }

    require_once MYBB_ROOT . 'inc/plugins/plotcp/myalerts_formatter.php';
    if (!class_exists('Plotcp_MyAlerts_Formatter')) {
        return;
    }

    foreach (plotcp_alert_codes() as $code) {
        $manager->registerFormatter(new Plotcp_MyAlerts_Formatter($mybb, $lang, $code));
    }

    $registered = true;
}

/**
 * Builds the scene box for thread views ({$plotcp_scene_box} is injected into
 * the showthread template on activation). Empty outside configured forums.
 */
function plotcp_handle_showthread(): void
{
    global $plotcp_scene_box;

    $plotcp_scene_box = '';

    plotcp_load_core();
    require_once MYBB_ROOT . 'inc/plugins/plotcp/core/frontend.php';
    $plotcp_scene_box = Plotcp_Frontend::buildSceneBox();
}

/**
 * Plot select in the new-thread form ({$plotcp_newthread_select} is injected
 * into the newthread template on activation). Empty outside configured forums.
 */
function plotcp_handle_newthread(): void
{
    global $plotcp_newthread_select;

    $plotcp_newthread_select = '';

    plotcp_load_core();
    require_once MYBB_ROOT . 'inc/plugins/plotcp/core/frontend.php';
    $plotcp_newthread_select = Plotcp_Frontend::buildNewthreadSelect();
}

/**
 * Saves the plot link chosen in the new-thread form. Runs after MyBB accepted
 * and stored the thread (its own post-key check already passed), so only the
 * plot-side validation is repeated here. Silently does nothing on invalid
 * input – a failed link must never break thread creation.
 */
function plotcp_handle_newthread_save(): void
{
    global $mybb, $db, $tid, $fid;

    $pid = (int)$mybb->get_input('plotcp_pid', MyBB::INPUT_INT);
    if ($pid <= 0 || (int)$tid <= 0) {
        return;
    }

    plotcp_load_core();

    if (!Plotcp_Permissions::isSceneForum((int)$fid)) {
        return;
    }
    $plot = Plotcp_Database::getPlot($pid);
    if (!$plot) {
        return;
    }
    if ((int)$plot['visible'] === 0 && !Plotcp_Permissions::isTeam($mybb->user)) {
        return;
    }

    // INSERT IGNORE + UNIQUE(tid): a race or double submit stays a no-op
    $db->write_query('INSERT IGNORE INTO ' . TABLE_PREFIX . 'plotcp_plot_threads
        (pid, tid, uid, dateline)
        VALUES (' . $pid . ', ' . (int)$tid . ', ' . (int)$mybb->user['uid'] . ', ' . TIME_NOW . ')');
}

/**
 * Prepends "[Plot name] " to the thread subject on forum listings for threads
 * linked to a plot. Runs at the top of the forumdisplay thread loop where
 * $thread['subject'] is still raw – MyBB escapes it afterwards, so the plot
 * title must stay raw here to avoid double escaping. The tid→plot map is built
 * once per page from $threadcache (one query). Preview plots only show their
 * name to viewers who may see the preview.
 */
function plotcp_handle_forumdisplay_thread(): void
{
    global $thread, $threadcache, $mybb;

    static $map = null;

    if ($map === null) {
        plotcp_load_core();
        $tids = is_array($threadcache) ? array_keys($threadcache) : [];
        $map = Plotcp_Database::getPlotsForThreads($tids);
    }

    $tid = (int)(isset($thread['tid']) ? $thread['tid'] : 0);
    if (!isset($map[$tid])) {
        return;
    }

    $plot = $map[$tid];
    if ($plot['visible'] !== 1 && !Plotcp_Permissions::canViewPreview($mybb->user, $plot)) {
        return;
    }

    $thread['subject'] = '[' . $plot['title'] . '] ' . $thread['subject'];
}

/**
 * Provider handler for the RPG Calendar. The hook only ever fires when
 * rpgcalendar is installed and rendering a month, so no plugin cache check is
 * needed here.
 */
function plotcp_rpgcalendar_collect(array &$args): void
{
    require_once MYBB_ROOT . 'inc/plugins/plotcp/calendar_provider.php';
    plotcp_collect_calendar_events($args);
}

/**
 * Removes the plot link of a deleted thread. class_moderation_delete_thread
 * covers all UI deletion paths (inline moderation, ACP, thread tools).
 */
function plotcp_handle_thread_delete($tid)
{
    global $db;

    $tid = (int)$tid;
    if ($tid > 0) {
        $db->delete_query('plotcp_plot_threads', "tid = {$tid}");
    }

    return $tid;
}

function plotcp_admin_menu(array &$sub_menu): void
{
    global $lang;

    $lang->load('plotcp');

    $sub_menu[] = [
        'id'    => 'plotcp',
        'title' => $lang->plotcp,
        'link'  => 'index.php?module=tools-plotcp',
    ];
}

function plotcp_admin_action(array &$actions): void
{
    $actions['plotcp'] = [
        'active' => 'plotcp',
        'file'   => 'plotcp.php',
    ];
}
