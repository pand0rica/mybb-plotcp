<?php
/**
 * plotcp – CLI plugin lifecycle helper (test server only).
 *
 * Usage from the MyBB root:
 *   php tests/cli-plugin.php install     # install + activate + mark active
 *   php tests/cli-plugin.php uninstall   # deactivate + uninstall + unmark
 *   php tests/cli-plugin.php status      # tables/settings/templates/stylesheet/alert type counts
 *
 * Mirrors what the ACP plugin manager does. Not part of a release zip.
 */

if (PHP_SAPI !== 'cli') {
    die('CLI only.');
}

define('IN_MYBB', 1);
chdir(dirname(__DIR__));
require_once './inc/init.php';

if (!defined('MYBB_ADMIN_DIR')) {
    $admin_dir = isset($config['admin_dir']) ? $config['admin_dir'] : 'admin';
    define('MYBB_ADMIN_DIR', MYBB_ROOT . $admin_dir . '/');
}

require_once MYBB_ROOT . 'inc/plugins/plotcp.php';

function plotcp_cli_set_active(bool $active): void
{
    global $cache;

    $plugins_cache = $cache->read('plugins');
    if (!is_array($plugins_cache)) {
        $plugins_cache = [];
    }
    if (!isset($plugins_cache['active']) || !is_array($plugins_cache['active'])) {
        $plugins_cache['active'] = [];
    }
    if ($active) {
        $plugins_cache['active']['plotcp'] = 'plotcp';
    } else {
        unset($plugins_cache['active']['plotcp']);
    }
    $cache->update('plugins', $plugins_cache);
}

function plotcp_cli_status(): void
{
    global $db, $cache;

    $tables = ['plotcp_categories', 'plotcp_groups', 'plotcp_plots',
               'plotcp_plot_groups', 'plotcp_group_members', 'plotcp_plot_threads'];
    foreach ($tables as $table) {
        echo "table {$table}: " . ($db->table_exists($table) ? 'exists' : 'MISSING') . "\n";
    }

    $row = $db->fetch_array($db->query("SELECT COUNT(*) AS num FROM " . TABLE_PREFIX . "settings WHERE name LIKE 'plotcp\_%'"));
    echo 'settings: ' . (int)$row['num'] . "\n";

    $row = $db->fetch_array($db->query("SELECT COUNT(*) AS num FROM " . TABLE_PREFIX . "templates WHERE title LIKE 'plotcp%'"));
    echo 'templates: ' . (int)$row['num'] . "\n";

    $row = $db->fetch_array($db->query("SELECT COUNT(*) AS num FROM " . TABLE_PREFIX . "themestylesheets WHERE name = 'plotcp.css'"));
    echo 'stylesheets: ' . (int)$row['num'] . "\n";

    if ($db->table_exists('alert_types')) {
        $row = $db->fetch_array($db->query("SELECT COUNT(*) AS num FROM " . TABLE_PREFIX . "alert_types WHERE code LIKE 'plotcp\_%'"));
        echo 'alert types: ' . (int)$row['num'] . "\n";
    } else {
        echo "alert types: n/a (MyAlerts not installed)\n";
    }

    // injection counts across all template sets
    $row = $db->fetch_array($db->query("SELECT COUNT(*) AS num FROM " . TABLE_PREFIX . "templates
        WHERE title = 'showthread' AND template LIKE '%{\$plotcp\_scene\_box}%'"));
    echo 'showthread injections: ' . (int)$row['num'] . "\n";

    $row = $db->fetch_array($db->query("SELECT COUNT(*) AS num FROM " . TABLE_PREFIX . "templates
        WHERE title = 'newthread' AND template LIKE '%{\$plotcp\_newthread\_select}%'"));
    echo 'newthread injections: ' . (int)$row['num'] . "\n";

    $plugins_cache = $cache->read('plugins');
    $active = isset($plugins_cache['active']['plotcp']);
    echo 'plugin cache active: ' . ($active ? 'yes' : 'no') . "\n";
}

$cmd = isset($argv[1]) ? $argv[1] : 'status';
switch ($cmd) {
    case 'install':
        plotcp_install();
        plotcp_activate();
        plotcp_cli_set_active(true);
        echo "installed + activated\n";
        plotcp_cli_status();
        break;

    case 'activate':
        // Re-run activation only (e.g. idempotency test of the showthread injection).
        plotcp_activate();
        echo "activated\n";
        plotcp_cli_status();
        break;

    case 'uninstall':
        plotcp_deactivate();
        plotcp_uninstall();
        plotcp_cli_set_active(false);
        echo "deactivated + uninstalled\n";
        plotcp_cli_status();
        break;

    case 'status':
    default:
        plotcp_cli_status();
        break;
}
