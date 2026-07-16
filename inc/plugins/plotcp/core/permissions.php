<?php
/**
 * plotcp – Permissions
 *
 * All permission decisions in one place. Group settings are evaluated against
 * the primary usergroup AND additionalgroups.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

class Plotcp_Permissions
{
    /** @return int[] primary + additional usergroup ids of the user array */
    public static function userGroupIds(array $user): array
    {
        $ids = [(int)(isset($user['usergroup']) ? $user['usergroup'] : 0)];
        if (!empty($user['additionalgroups'])) {
            foreach (explode(',', (string)$user['additionalgroups']) as $gid) {
                if ((int)$gid > 0) {
                    $ids[] = (int)$gid;
                }
            }
        }

        return $ids;
    }

    /** True when the user is in one of the groups of a CSV setting value. */
    public static function userInGroups(array $user, string $csv): bool
    {
        $allowed = array_filter(array_map('intval', explode(',', $csv)),
            function ($gid) { return $gid > 0; });
        if (!$allowed) {
            return false;
        }

        return (bool)array_intersect(self::userGroupIds($user), $allowed);
    }

    /** Team members may publish plots, approve groups and manage scene links. */
    public static function isTeam(array $user): bool
    {
        global $mybb;

        if ((int)(isset($user['uid']) ? $user['uid'] : 0) <= 0) {
            return false;
        }

        $setting = isset($mybb->settings['plotcp_team_groups'])
            ? (string)$mybb->settings['plotcp_team_groups'] : '';

        return self::userInGroups($user, $setting);
    }

    /**
     * Who may edit a plot: the team may edit any plot; the submitter may edit
     * their own plot while it is still a preview (visible = 0). Once published,
     * only the team can edit it.
     */
    public static function canEditPlot(array $user, array $plot): bool
    {
        $uid = (int)(isset($user['uid']) ? $user['uid'] : 0);
        if ($uid <= 0) {
            return false;
        }

        if (self::isTeam($user)) {
            return true;
        }

        return (int)(isset($plot['submitted_uid']) ? $plot['submitted_uid'] : 0) === $uid
            && (int)(isset($plot['visible']) ? $plot['visible'] : 1) === 0;
    }

    /**
     * Preview plots (visible = 0) are shown to team groups, the configured
     * preview groups and the submitter of the plot.
     */
    public static function canViewPreview(array $user, array $plot = []): bool
    {
        global $mybb;

        $uid = (int)(isset($user['uid']) ? $user['uid'] : 0);
        if ($uid <= 0) {
            return false;
        }

        if (self::isTeam($user)) {
            return true;
        }

        $setting = isset($mybb->settings['plotcp_preview_groups'])
            ? (string)$mybb->settings['plotcp_preview_groups'] : '';
        if (self::userInGroups($user, $setting)) {
            return true;
        }

        return isset($plot['submitted_uid']) && (int)$plot['submitted_uid'] === $uid;
    }

    /** True when the user may see previews of ANY plot (list views). */
    public static function canViewAnyPreview(array $user): bool
    {
        global $mybb;

        if (self::isTeam($user)) {
            return true;
        }

        $setting = isset($mybb->settings['plotcp_preview_groups'])
            ? (string)$mybb->settings['plotcp_preview_groups'] : '';

        return self::userInGroups($user, $setting);
    }

    /** Thread starter and team may link/unlink a thread with a plot. */
    public static function canLinkThread(array $user, array $thread): bool
    {
        $uid = (int)(isset($user['uid']) ? $user['uid'] : 0);
        if ($uid <= 0) {
            return false;
        }

        if (self::isTeam($user)) {
            return true;
        }

        return isset($thread['uid']) && (int)$thread['uid'] === $uid;
    }

    /** True when the thread's forum is configured for scene linking. */
    public static function isSceneForum(int $fid): bool
    {
        global $mybb;

        $csv = '';
        foreach (['plotcp_inplay_forums', 'plotcp_archive_forums'] as $name) {
            if (isset($mybb->settings[$name]) && (string)$mybb->settings[$name] !== '') {
                $csv .= ',' . $mybb->settings[$name];
            }
        }

        $fids = array_filter(array_map('intval', explode(',', $csv)),
            function ($id) { return $id > 0; });

        return in_array($fid, $fids, true);
    }
}
