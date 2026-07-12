<?php
/**
 * plotcp – Installer
 *
 * All install/activate/deactivate/uninstall database work lives here so the
 * lifecycle functions in inc/plugins/plotcp.php stay thin. Every artefact
 * created on activation (settings, templates, stylesheet, alert types,
 * showthread injection) is removed on deactivation.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

class Plotcp_Installer
{
    /**
     * Loads inc/adminfunctions_templates.php (find_replace_templatesets) once.
     * The file has no include guard and, on symlinked vhosts (Plesk/netcup),
     * require_once does not reliably deduplicate it because the resolved
     * realpath differs between includes – calling it from several installer
     * methods in one activation would redeclare the function. The
     * function_exists() guard makes the include idempotent regardless.
     */
    private static function loadTemplateFunctions(): void
    {
        if (!function_exists('find_replace_templatesets')) {
            require_once MYBB_ROOT . 'inc/adminfunctions_templates.php';
        }
    }

    /** Same guarded load, plus inc/functions_themes.php (theme stylesheets). */
    private static function loadThemeFunctions(): void
    {
        self::loadTemplateFunctions();
        if (!function_exists('update_theme_stylesheet_list')) {
            require_once MYBB_ADMIN_DIR . 'inc/functions_themes.php';
        }
    }

    public static function createTables(): void
    {
        global $db;

        $collation = $db->build_create_table_collation();

        $db->query("CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "plotcp_categories` (
            `cid`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `name`          VARCHAR(120) NOT NULL DEFAULT '',
            `description`   TEXT NOT NULL,
            `display_order` INT NOT NULL DEFAULT 0,
            PRIMARY KEY (`cid`)
        ) ENGINE=InnoDB{$collation};");

        $db->query("CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "plotcp_groups` (
            `gid`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `name`          VARCHAR(120) NOT NULL DEFAULT '',
            `description`   TEXT NOT NULL,
            `approved`      TINYINT(1) NOT NULL DEFAULT 0,
            `submitted_uid` INT UNSIGNED NOT NULL DEFAULT 0,
            `display_order` INT NOT NULL DEFAULT 0,
            `dateline`      INT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (`gid`)
        ) ENGINE=InnoDB{$collation};");

        $db->query("CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "plotcp_plots` (
            `pid`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `cid`                INT UNSIGNED NOT NULL DEFAULT 0,
            `title`              VARCHAR(200) NOT NULL DEFAULT '',
            `location`           VARCHAR(200) NOT NULL DEFAULT '',
            `contacts`           VARCHAR(500) NOT NULL DEFAULT '',
            `description`        TEXT NOT NULL,
            `visible`            TINYINT(1) NOT NULL DEFAULT 0,
            `submitted_uid`      INT UNSIGNED NOT NULL DEFAULT 0,
            `created_dateline`   INT UNSIGNED NOT NULL DEFAULT 0,
            `published_dateline` INT UNSIGNED NOT NULL DEFAULT 0,
            `has_date`           TINYINT(1) NOT NULL DEFAULT 0,
            `start_year`         SMALLINT NOT NULL DEFAULT 0,
            `start_month`        TINYINT UNSIGNED NOT NULL DEFAULT 1,
            `start_day`          TINYINT UNSIGNED NOT NULL DEFAULT 1,
            `end_year`           SMALLINT NOT NULL DEFAULT 0,
            `end_month`          TINYINT UNSIGNED NOT NULL DEFAULT 1,
            `end_day`            TINYINT UNSIGNED NOT NULL DEFAULT 1,
            `start_key`          INT NOT NULL DEFAULT 0,
            `end_key`            INT NOT NULL DEFAULT 0,
            PRIMARY KEY (`pid`),
            KEY `overlap` (`end_key`, `start_key`),
            KEY `cid` (`cid`)
        ) ENGINE=InnoDB{$collation};");

        $db->query("CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "plotcp_plot_groups` (
            `id`       INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `pid`      INT UNSIGNED NOT NULL DEFAULT 0,
            `gid`      INT UNSIGNED NOT NULL DEFAULT 0,
            `dateline` INT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            UNIQUE KEY `pid_gid` (`pid`, `gid`),
            KEY `gid` (`gid`)
        ) ENGINE=InnoDB{$collation};");

        $db->query("CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "plotcp_group_members` (
            `id`       INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `gid`      INT UNSIGNED NOT NULL DEFAULT 0,
            `uid`      INT UNSIGNED NOT NULL DEFAULT 0,
            `dateline` INT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            UNIQUE KEY `gid_uid` (`gid`, `uid`),
            KEY `uid` (`uid`)
        ) ENGINE=InnoDB{$collation};");

        $db->query("CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "plotcp_plot_threads` (
            `id`       INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `pid`      INT UNSIGNED NOT NULL DEFAULT 0,
            `tid`      INT UNSIGNED NOT NULL DEFAULT 0,
            `uid`      INT UNSIGNED NOT NULL DEFAULT 0,
            `dateline` INT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            UNIQUE KEY `tid` (`tid`),
            KEY `pid` (`pid`)
        ) ENGINE=InnoDB{$collation};");
    }

    /**
     * Schema migrations for existing installs (run on activate). Idempotent.
     */
    public static function migrateSchema(): void
    {
        global $db;

        // v1.0.0: the plot archive feature was dropped – remove its column.
        if ($db->field_exists('archived', 'plotcp_plots')) {
            $db->drop_column('plotcp_plots', 'archived');
        }

        // v1.1.0: category descriptions became long text (were VARCHAR(255)).
        $col = $db->fetch_array($db->query(
            "SHOW COLUMNS FROM " . TABLE_PREFIX . "plotcp_categories LIKE 'description'"
        ));
        if ($col && stripos((string)$col['Type'], 'text') === false) {
            $db->write_query("ALTER TABLE " . TABLE_PREFIX
                . "plotcp_categories MODIFY `description` TEXT NOT NULL");
        }
    }

    public static function dropTables(): void
    {
        global $db;

        $db->drop_table('plotcp_categories');
        $db->drop_table('plotcp_groups');
        $db->drop_table('plotcp_plots');
        $db->drop_table('plotcp_plot_groups');
        $db->drop_table('plotcp_group_members');
        $db->drop_table('plotcp_plot_threads');
    }

    /**
     * Setting definitions: name (without prefix) => [title, description, optionscode, default].
     * Single source for install and for the upgrade path.
     */
    private static function settingDefinitions(): array
    {
        return [
            'preview_groups' => [
                'Preview visibility groups',
                'User groups that may see unpublished plots (previews) in the plot list and detail view. Team groups and the submitter of a plot always see it.',
                'groupselect', '',
            ],
            'team_groups' => [
                'Team groups',
                'User groups that may publish plots, approve group proposals and manage scene links on any thread. These groups also receive submission alerts.',
                'groupselect', '',
            ],
            'inplay_forums' => [
                'Inplay forums',
                'Forums in which the plot link box is shown below threads (active inplay areas).',
                'forumselect', '',
            ],
            'archive_forums' => [
                'Archive forums',
                'Forums in which the plot link box is shown below threads (archived inplay areas).',
                'forumselect', '',
            ],
            'allow_plot_submissions' => [
                'Allow plot submissions',
                'Registered users may submit new plots via the frontend form. Submitted plots start as previews.',
                'onoff', '1',
            ],
            'allow_group_submissions' => [
                'Allow group proposals',
                'Registered users may propose new plotting groups via the frontend form. Proposals require team approval.',
                'onoff', '1',
            ],
            'current_inplay' => [
                'Current inplay months',
                'Drives the global template variable {$plotcp_current_plots} (published plots overlapping these months). Enter one or more inplay months as year-month, comma separated, e.g. 1487-3, 1487-4. Leave empty to disable the variable. The month texts of these months are provided separately by the rpgcalendar plugin.',
                'text', '',
            ],
        ];
    }

    /**
     * Idempotent: creates the setting group if missing, otherwise adds only the
     * individual settings that do not exist yet (upgrade path).
     */
    public static function installSettings(): void
    {
        global $db;

        $definitions = self::settingDefinitions();

        $existing = $db->fetch_array($db->simple_select('settinggroups', 'gid', "name = 'plotcp'"));
        if ($existing) {
            $gid = (int)$existing['gid'];
        } else {
            $gid = (int)$db->insert_query('settinggroups', [
                'name'        => 'plotcp',
                'title'       => 'Plot Control Panel',
                'description' => 'Settings for the Plot Control Panel plugin.',
                'disporder'   => 1,
                'isdefault'   => 0,
            ]);
        }

        $disporder = 1;
        foreach ($definitions as $suffix => $def) {
            $name = 'plotcp_' . $suffix;
            $row = $db->fetch_array($db->simple_select('settings', 'sid',
                "name = '" . $db->escape_string($name) . "'"));
            if (!$row) {
                $db->insert_query('settings', [
                    'name'        => $name,
                    'title'       => $db->escape_string($def[0]),
                    'description' => $db->escape_string($def[1]),
                    'optionscode' => $def[2],
                    'value'       => $def[3],
                    'disporder'   => $disporder,
                    'gid'         => $gid,
                ]);
            }
            $disporder++;
        }

        rebuild_settings();
    }

    public static function removeSettings(): void
    {
        global $db;

        $db->delete_query('settings', "name LIKE 'plotcp\_%'");
        $db->delete_query('settinggroups', "name = 'plotcp'");
        rebuild_settings();
    }

    /**
     * Loads every .tpl file from the plugin template directory into
     * mybb_templates (filename without extension = template title). Existing
     * rows are replaced so re-activation picks up template changes.
     */
    public static function installTemplates(): void
    {
        global $db;

        $template_dir = MYBB_ROOT . 'inc/plugins/plotcp/templates/';
        if (!is_dir($template_dir)) {
            return;
        }

        self::removeTemplates();

        $handle = opendir($template_dir);
        if (!$handle) {
            return;
        }
        while (($file = readdir($handle)) !== false) {
            if (substr($file, -4) !== '.tpl') {
                continue;
            }
            $title   = substr($file, 0, -4);
            $content = (string)file_get_contents($template_dir . $file);
            $db->insert_query('templates', [
                'title'    => $db->escape_string($title),
                'template' => $db->escape_string($content),
                'sid'      => -1,
                'version'  => '',
                'dateline' => TIME_NOW,
            ]);
        }
        closedir($handle);
    }

    public static function removeTemplates(): void
    {
        global $db;

        $db->delete_query('templates', "title LIKE 'plotcp%'");
    }

    /**
     * Registers the plugin stylesheet in every theme, attached to index.php
     * (group notice + {$plotcp_current_plots}), plotcp.php and showthread.php
     * (scene box). The attachment is refreshed on re-activation for existing
     * rows too, so upgrades pick up newly attached scripts.
     */
    public static function installStylesheet(): void
    {
        global $db;

        self::loadThemeFunctions();

        $css_path = MYBB_ROOT . 'inc/plugins/plotcp/assets/plotcp.css';
        if (!file_exists($css_path)) {
            return;
        }

        $attachedto = 'index.php|plotcp.php|showthread.php';
        $css = $db->escape_string((string)file_get_contents($css_path));
        $themes = $db->simple_select('themes', 'tid');
        while ($theme = $db->fetch_array($themes)) {
            $tid = (int)$theme['tid'];
            $existing = $db->fetch_array($db->simple_select(
                'themestylesheets', 'sid',
                "name = 'plotcp.css' AND tid = '" . $tid . "'"
            ));
            if ($existing) {
                $db->update_query('themestylesheets', [
                    'attachedto'   => $attachedto,
                    'stylesheet'   => $css,
                    'lastmodified' => TIME_NOW,
                ], "sid = '" . (int)$existing['sid'] . "'", 1);
            } else {
                $sid = $db->insert_query('themestylesheets', [
                    'name'         => 'plotcp.css',
                    'tid'          => $tid,
                    'attachedto'   => $attachedto,
                    'stylesheet'   => $css,
                    'lastmodified' => TIME_NOW,
                    'cachefile'    => 'plotcp.css',
                ]);
                $db->update_query('themestylesheets',
                    ['cachefile' => "css.php?stylesheet={$sid}"], "sid = '{$sid}'", 1);
            }
            update_theme_stylesheet_list($tid);
        }
    }

    public static function removeStylesheet(): void
    {
        global $db;

        self::loadThemeFunctions();

        $db->delete_query('themestylesheets', "name = 'plotcp.css'");
        $themes = $db->simple_select('themes', 'tid');
        while ($theme = $db->fetch_array($themes)) {
            update_theme_stylesheet_list((int)$theme['tid']);
        }
    }

    /**
     * Injects the two core-template variables: {$plotcp_scene_box} after
     * {$pollbox} in showthread and {$plotcp_newthread_select} after
     * {$posticons} in newthread. Removal runs first so repeated activation
     * never produces a second injection.
     */
    public static function injectSceneBox(): void
    {
        self::loadTemplateFunctions();

        self::removeSceneBoxInjection();
        find_replace_templatesets(
            'showthread',
            '#' . preg_quote('{$pollbox}') . '#',
            "{\$pollbox}\n{\$plotcp_scene_box}"
        );
        find_replace_templatesets(
            'newthread',
            '#' . preg_quote('{$posticons}') . '#',
            "{\$posticons}\n{\$plotcp_newthread_select}"
        );
    }

    public static function removeSceneBoxInjection(): void
    {
        self::loadTemplateFunctions();

        find_replace_templatesets(
            'showthread',
            '#' . preg_quote("\n{\$plotcp_scene_box}") . '#',
            '',
            0
        );
        find_replace_templatesets(
            'newthread',
            '#' . preg_quote("\n{\$plotcp_newthread_select}") . '#',
            '',
            0
        );
    }

    /**
     * Injects {$plotcp_group_notice} after {$header} in the board index so team
     * members see pending group proposals right on the front page. Removal runs
     * first to keep repeated activation from stacking injections.
     */
    public static function injectIndexNotice(): void
    {
        self::loadTemplateFunctions();

        self::removeIndexNoticeInjection();
        find_replace_templatesets(
            'index',
            '#' . preg_quote('{$header}') . '#',
            "{\$header}\n{\$plotcp_group_notice}"
        );
    }

    public static function removeIndexNoticeInjection(): void
    {
        self::loadTemplateFunctions();

        find_replace_templatesets(
            'index',
            '#' . preg_quote("\n{\$plotcp_group_notice}") . '#',
            '',
            0
        );
    }

    /**
     * Injects {$plotcp_modcp_nav} before the closing </table> of the ModCP
     * navigation so the plot team gets a menu entry to the group review page.
     * modcp_nav has a single tborder table, so the last (only) </table> is a
     * stable anchor.
     */
    public static function injectModcpNav(): void
    {
        self::loadTemplateFunctions();

        self::removeModcpNavInjection();
        // Anchor on the final </table> only (negative lookahead) so themes with
        // nested tables in modcp_nav still get exactly one injection.
        find_replace_templatesets(
            'modcp_nav',
            '#</table>(?![\s\S]*</table>)#',
            "{\$plotcp_modcp_nav}</table>"
        );
    }

    public static function removeModcpNavInjection(): void
    {
        self::loadTemplateFunctions();

        find_replace_templatesets(
            'modcp_nav',
            '#' . preg_quote('{$plotcp_modcp_nav}') . '#',
            '',
            0
        );
    }

    /**
     * Registers the MyAlerts alert types. Best effort: silently skipped when
     * MyAlerts is not installed or incompatible – the plugin works without it.
     */
    public static function registerAlertTypes(): void
    {
        global $db, $cache;

        if (!class_exists('MybbStuff_MyAlerts_AlertTypeManager')
            || !class_exists('MybbStuff_MyAlerts_Entity_AlertType')
        ) {
            return;
        }

        try {
            $manager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();
            if (!$manager) {
                $manager = MybbStuff_MyAlerts_AlertTypeManager::createInstance($db, $cache);
            }
            if (!$manager) {
                return;
            }

            $types = [];
            foreach (plotcp_alert_codes() as $code) {
                $type = new MybbStuff_MyAlerts_Entity_AlertType();
                $type->setCode($code);
                $type->setEnabled(true);
                $type->setCanBeUserDisabled(true);
                $types[] = $type;
            }
            $manager->addTypes($types);
        } catch (Throwable $e) {
            // Incompatible MyAlerts version – plugin stays usable without alerts.
        }
    }

    public static function unregisterAlertTypes(): void
    {
        global $db, $cache;

        if (!class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
            return;
        }

        try {
            $manager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();
            if (!$manager) {
                $manager = MybbStuff_MyAlerts_AlertTypeManager::createInstance($db, $cache);
            }
            if (!$manager) {
                return;
            }
            foreach (plotcp_alert_codes() as $code) {
                $manager->deleteByCode($code);
            }
        } catch (Throwable $e) {
            // Best effort – see registerAlertTypes().
        }
    }
}
