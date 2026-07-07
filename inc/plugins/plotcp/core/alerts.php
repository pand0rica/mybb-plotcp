<?php
/**
 * plotcp – MyAlerts integration (optional dependency)
 *
 * Every path is best effort: when MyAlerts is missing or incompatible the
 * methods return silently and the plugin keeps working without alerts.
 * ExtraDetails are stored RAW – the formatter escapes on render.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

class Plotcp_Alerts
{
    public static function isAvailable(): bool
    {
        return class_exists('MybbStuff_MyAlerts_AlertTypeManager')
            && class_exists('MybbStuff_MyAlerts_AlertManager')
            && class_exists('MybbStuff_MyAlerts_Entity_Alert');
    }

    /**
     * Sends one alert of the given type to every uid in the list. MyAlerts
     * itself skips users who disabled the alert type in their UCP settings.
     *
     * @param string $code      full alert type code (plotcp_*)
     * @param int[]  $uids      recipient uids (deduplicated here)
     * @param array  $extra     raw extra details for the formatter
     * @param int    $object_id related object id (pid/gid)
     * @return int number of recipients the alert was handed to MyAlerts for
     */
    public static function send(string $code, array $uids, array $extra, int $object_id = 0): int
    {
        global $mybb;

        if (!self::isAvailable()) {
            return 0;
        }

        $uids = array_values(array_unique(array_filter(array_map('intval', $uids),
            function ($uid) { return $uid > 0; })));
        if (!$uids) {
            return 0;
        }

        $sent = 0;
        try {
            $type_manager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();
            if (!$type_manager) {
                global $db, $cache;
                $type_manager = MybbStuff_MyAlerts_AlertTypeManager::createInstance($db, $cache);
            }
            $alert_manager = MybbStuff_MyAlerts_AlertManager::getInstance();
            if (!$alert_manager && $type_manager) {
                // MyAlerts creates its instances at global_start; in other
                // contexts (CLI, early hooks) we create them ourselves.
                global $db, $cache, $plugins;
                $alert_manager = MybbStuff_MyAlerts_AlertManager::createInstance(
                    $mybb, $db, $cache, $plugins, $type_manager);
            }
            if (!$type_manager || !$alert_manager) {
                return 0;
            }

            $alert_type = $type_manager->getByCode($code);
            if ($alert_type === null) {
                return 0;
            }

            $from_uid = (int)(isset($mybb->user['uid']) ? $mybb->user['uid'] : 0);

            foreach ($uids as $uid) {
                $alert = new MybbStuff_MyAlerts_Entity_Alert($uid, $alert_type, $object_id);
                if ($from_uid > 0) {
                    $alert->setFromUserId($from_uid);
                }
                $alert->setExtraDetails($extra);
                $alert_manager->addAlert($alert);
                $sent++;
            }

            // addAlert() only queues; MyAlerts flushes the queue at the end of
            // a web request, but not in CLI/early-hook contexts. Committing
            // here is safe: the queue is emptied, later commits find nothing.
            $alert_manager->commit();
        } catch (Throwable $e) {
            // Incompatible MyAlerts version – never break the page for alerts.
        }

        return $sent;
    }

    /**
     * Publish alert to all members of the plot's groups. Used both on publish
     * (all linked groups) and when groups are added to an already published
     * plot (only members of the new groups who are not already covered by a
     * remaining group).
     *
     * @param int[] $notify_gids  groups whose members should be alerted
     * @param int[] $already_gids groups whose members were already alerted earlier
     */
    public static function sendPlotPublished(array $plot, array $notify_gids, array $already_gids = []): int
    {
        $uids = Plotcp_Database::getGroupMemberUids($notify_gids);
        if ($already_gids) {
            $uids = array_values(array_diff($uids, Plotcp_Database::getGroupMemberUids($already_gids)));
        }

        return self::send('plotcp_plot_published', $uids, [
            'plot_title' => (string)$plot['title'],
            'link'       => 'plotcp.php?action=view&pid=' . (int)$plot['pid'],
        ], (int)$plot['pid']);
    }

    /** New-group alert to every active main account (see getMainAccountUids). */
    public static function sendNewGroup(array $group): int
    {
        return self::send('plotcp_new_group', Plotcp_Database::getMainAccountUids(), [
            'group_name' => (string)$group['name'],
            'link'       => 'plotcp.php?action=groups',
        ], (int)$group['gid']);
    }

    /**
     * Submission alert to the team groups.
     *
     * @param string $kind 'plot' or 'group' – the formatter picks the wording
     */
    public static function sendSubmissionTeam(string $kind, string $title, string $link, int $object_id = 0): int
    {
        return self::send('plotcp_submission_team', Plotcp_Database::getTeamUids(), [
            'kind'  => $kind,
            'title' => $title,
            'link'  => $link,
        ], $object_id);
    }
}
