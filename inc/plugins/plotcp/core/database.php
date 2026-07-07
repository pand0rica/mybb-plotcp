<?php
/**
 * plotcp – Database layer
 *
 * All CRUD, the date validation (proleptic Gregorian, astronomical year
 * numbering – identical to rpgcalendar) and the alert recipient queries live
 * here. start_key/end_key are always computed in PHP, never by the database.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

class Plotcp_Database
{
    /* ---------------------------------------------------------------------
     * Calendar math (matches rpgcalendar: year 0 exists, negative years OK)
     * ------------------------------------------------------------------ */

    public static function isLeapYear(int $year): bool
    {
        return ($year % 4 === 0) && ($year % 100 !== 0 || $year % 400 === 0);
    }

    public static function daysInMonth(int $year, int $month): int
    {
        static $days = [1 => 31, 2 => 28, 3 => 31, 4 => 30, 5 => 31, 6 => 30,
                        7 => 31, 8 => 31, 9 => 30, 10 => 31, 11 => 30, 12 => 31];

        if ($month === 2 && self::isLeapYear($year)) {
            return 29;
        }

        return isset($days[$month]) ? $days[$month] : 0;
    }

    /**
     * Sort key y*10000 + m*100 + d – monotonic also for negative years, so a
     * plain integer interval overlap works for any date range.
     */
    public static function dateKey(int $year, int $month, int $day): int
    {
        return $year * 10000 + $month * 100 + $day;
    }

    /**
     * Validates the date block of a plot form and returns the normalized
     * column values. Raw form values come in as strings; '' and '0' must be
     * distinguished because year 0 is a valid year.
     *
     * Rules: without has_date everything is reset to defaults. With has_date
     * the start date must be complete and valid. The end date is either
     * completely empty (=> copied from start, single-day plot) or complete and
     * not before the start. Nothing is swapped or clamped silently.
     *
     * @return array ['ok' => bool, 'error' => lang key suffix or '', 'fields' => column array]
     */
    public static function validateDates(bool $has_date, array $in): array
    {
        $defaults = [
            'has_date'    => 0,
            'start_year'  => 0, 'start_month' => 1, 'start_day' => 1,
            'end_year'    => 0, 'end_month'   => 1, 'end_day'   => 1,
            'start_key'   => 0, 'end_key'     => 0,
        ];

        if (!$has_date) {
            return ['ok' => true, 'error' => '', 'fields' => $defaults];
        }

        $start = self::parseDateTriple($in, 'start');
        if ($start['state'] !== 'complete') {
            return ['ok' => false, 'error' => 'error_start_incomplete', 'fields' => $defaults];
        }
        if (!self::isValidDate($start['y'], $start['m'], $start['d'])) {
            return ['ok' => false, 'error' => 'error_start_invalid', 'fields' => $defaults];
        }

        $end = self::parseDateTriple($in, 'end');
        if ($end['state'] === 'empty') {
            $end = $start;
        } elseif ($end['state'] !== 'complete') {
            return ['ok' => false, 'error' => 'error_end_partial', 'fields' => $defaults];
        } elseif (!self::isValidDate($end['y'], $end['m'], $end['d'])) {
            return ['ok' => false, 'error' => 'error_end_invalid', 'fields' => $defaults];
        }

        $start_key = self::dateKey($start['y'], $start['m'], $start['d']);
        $end_key   = self::dateKey($end['y'], $end['m'], $end['d']);
        if ($end_key < $start_key) {
            return ['ok' => false, 'error' => 'error_end_before_start', 'fields' => $defaults];
        }

        return ['ok' => true, 'error' => '', 'fields' => [
            'has_date'    => 1,
            'start_year'  => $start['y'], 'start_month' => $start['m'], 'start_day' => $start['d'],
            'end_year'    => $end['y'],   'end_month'   => $end['m'],   'end_day'   => $end['d'],
            'start_key'   => $start_key,  'end_key'     => $end_key,
        ]];
    }

    /**
     * Reads year/month/day form fields for the given prefix. state is 'empty'
     * (all three blank), 'complete' (all three filled) or 'partial'.
     */
    private static function parseDateTriple(array $in, string $prefix): array
    {
        $raw = [];
        foreach (['year', 'month', 'day'] as $part) {
            $key = $prefix . '_' . $part;
            $raw[$part] = isset($in[$key]) ? trim((string)$in[$key]) : '';
        }

        $filled = 0;
        foreach ($raw as $value) {
            if ($value !== '') {
                $filled++;
            }
        }

        $state = 'partial';
        if ($filled === 0) {
            $state = 'empty';
        } elseif ($filled === 3) {
            $state = 'complete';
        }

        return [
            'state' => $state,
            'y'     => (int)$raw['year'],
            'm'     => (int)$raw['month'],
            'd'     => (int)$raw['day'],
        ];
    }

    public static function isValidDate(int $year, int $month, int $day): bool
    {
        if ($year < -32768 || $year > 32767) {
            return false;
        }
        if ($month < 1 || $month > 12) {
            return false;
        }

        return $day >= 1 && $day <= self::daysInMonth($year, $month);
    }

    /* ---------------------------------------------------------------------
     * Categories
     * ------------------------------------------------------------------ */

    public static function getCategories(): array
    {
        global $db;

        $categories = [];
        $query = $db->simple_select('plotcp_categories', '*', '', ['order_by' => 'display_order, name']);
        while ($row = $db->fetch_array($query)) {
            $categories[(int)$row['cid']] = $row;
        }

        return $categories;
    }

    public static function getCategory(int $cid): ?array
    {
        global $db;

        $row = $db->fetch_array($db->simple_select('plotcp_categories', '*', 'cid = ' . $cid));

        return $row ? $row : null;
    }

    public static function categoryHasPlots(int $cid): bool
    {
        global $db;

        $row = $db->fetch_array($db->simple_select('plotcp_plots', 'pid', 'cid = ' . $cid, ['limit' => 1]));

        return (bool)$row;
    }

    /* ---------------------------------------------------------------------
     * Groups
     * ------------------------------------------------------------------ */

    public static function getGroups(bool $approved_only = true): array
    {
        global $db;

        $groups = [];
        $where = $approved_only ? 'approved = 1' : '';
        $query = $db->simple_select('plotcp_groups', '*', $where,
            ['order_by' => 'approved ASC, display_order ASC, name ASC']);
        while ($row = $db->fetch_array($query)) {
            $groups[(int)$row['gid']] = $row;
        }

        return $groups;
    }

    public static function getGroup(int $gid): ?array
    {
        global $db;

        $row = $db->fetch_array($db->simple_select('plotcp_groups', '*', 'gid = ' . $gid));

        return $row ? $row : null;
    }

    public static function groupNameExists(string $name, int $exclude_gid = 0): bool
    {
        global $db;

        $where = "LOWER(name) = '" . $db->escape_string(my_strtolower(trim($name))) . "'";
        if ($exclude_gid > 0) {
            $where .= ' AND gid != ' . $exclude_gid;
        }
        $row = $db->fetch_array($db->simple_select('plotcp_groups', 'gid', $where, ['limit' => 1]));

        return (bool)$row;
    }

    /** @return array gid => member count */
    public static function getGroupMemberCounts(): array
    {
        global $db;

        $counts = [];
        $query = $db->query('SELECT gid, COUNT(*) AS members FROM ' . TABLE_PREFIX . 'plotcp_group_members
            GROUP BY gid');
        while ($row = $db->fetch_array($query)) {
            $counts[(int)$row['gid']] = (int)$row['members'];
        }

        return $counts;
    }

    public static function isGroupMember(int $gid, int $uid): bool
    {
        global $db;

        $row = $db->fetch_array($db->simple_select('plotcp_group_members', 'id',
            'gid = ' . $gid . ' AND uid = ' . $uid, ['limit' => 1]));

        return (bool)$row;
    }

    /** @return array of gid the user is a member of */
    public static function getUserGroupIds(int $uid): array
    {
        global $db;

        $gids = [];
        $query = $db->simple_select('plotcp_group_members', 'gid', 'uid = ' . $uid);
        while ($row = $db->fetch_array($query)) {
            $gids[] = (int)$row['gid'];
        }

        return $gids;
    }

    /** Deletes a plotting group including memberships and plot links. */
    public static function deleteGroup(int $gid): void
    {
        global $db;

        $db->delete_query('plotcp_plot_groups', 'gid = ' . $gid);
        $db->delete_query('plotcp_group_members', 'gid = ' . $gid);
        $db->delete_query('plotcp_groups', 'gid = ' . $gid);
    }

    /* ---------------------------------------------------------------------
     * Plots
     * ------------------------------------------------------------------ */

    public static function getPlot(int $pid): ?array
    {
        global $db;

        $row = $db->fetch_array($db->simple_select('plotcp_plots', '*', 'pid = ' . $pid));

        return $row ? $row : null;
    }

    /** @return array of gid linked to the plot */
    public static function getPlotGids(int $pid): array
    {
        global $db;

        $gids = [];
        $query = $db->simple_select('plotcp_plot_groups', 'gid', 'pid = ' . $pid);
        while ($row = $db->fetch_array($query)) {
            $gids[] = (int)$row['gid'];
        }

        return $gids;
    }

    /**
     * Replaces the group links of a plot and returns the diff so callers can
     * alert only members of newly added groups.
     *
     * @return array ['added' => gid[], 'removed' => gid[], 'kept' => gid[]]
     */
    public static function setPlotGroups(int $pid, array $gids): array
    {
        global $db;

        $gids = array_values(array_unique(array_map('intval', $gids)));
        $old  = self::getPlotGids($pid);

        $added   = array_values(array_diff($gids, $old));
        $removed = array_values(array_diff($old, $gids));
        $kept    = array_values(array_intersect($old, $gids));

        foreach ($removed as $gid) {
            $db->delete_query('plotcp_plot_groups', 'pid = ' . $pid . ' AND gid = ' . $gid);
        }
        foreach ($added as $gid) {
            $db->insert_query('plotcp_plot_groups', [
                'pid'      => $pid,
                'gid'      => $gid,
                'dateline' => TIME_NOW,
            ]);
        }

        return ['added' => $added, 'removed' => $removed, 'kept' => $kept];
    }

    /** Deletes a plot including group links and thread links. */
    public static function deletePlot(int $pid): void
    {
        global $db;

        $db->delete_query('plotcp_plot_groups', 'pid = ' . $pid);
        $db->delete_query('plotcp_plot_threads', 'pid = ' . $pid);
        $db->delete_query('plotcp_plots', 'pid = ' . $pid);
    }

    /**
     * Filters a submitted gid list down to existing, approved groups.
     */
    public static function filterApprovedGids(array $gids): array
    {
        global $db;

        $gids = array_values(array_unique(array_filter(array_map('intval', $gids),
            function ($gid) { return $gid > 0; })));
        if (!$gids) {
            return [];
        }

        $valid = [];
        $query = $db->simple_select('plotcp_groups', 'gid',
            'approved = 1 AND gid IN (' . implode(',', $gids) . ')');
        while ($row = $db->fetch_array($query)) {
            $valid[] = (int)$row['gid'];
        }

        return $valid;
    }

    /**
     * Plot info for a batch of threads (forum listing prefix). One query.
     *
     * @return array tid => ['title','visible','submitted_uid']
     */
    public static function getPlotsForThreads(array $tids): array
    {
        global $db;

        $tids = array_values(array_unique(array_filter(array_map('intval', $tids),
            function ($tid) { return $tid > 0; })));
        if (!$tids) {
            return [];
        }

        $map = [];
        $query = $db->query('
            SELECT pt.tid, p.title, p.visible, p.submitted_uid
            FROM ' . TABLE_PREFIX . 'plotcp_plot_threads pt
            INNER JOIN ' . TABLE_PREFIX . 'plotcp_plots p ON (p.pid = pt.pid)
            WHERE pt.tid IN (' . implode(',', $tids) . ')
        ');
        while ($row = $db->fetch_array($query)) {
            $map[(int)$row['tid']] = [
                'title'         => (string)$row['title'],
                'visible'       => (int)$row['visible'],
                'submitted_uid' => (int)$row['submitted_uid'],
            ];
        }

        return $map;
    }

    /* ---------------------------------------------------------------------
     * Contacts (plot contact persons, stored as CSV of validated uids)
     * ------------------------------------------------------------------ */

    /**
     * Resolves a comma-separated username list to uids.
     *
     * @return array ['uids' => int[], 'unknown' => string[]]
     */
    public static function parseContactInput(string $names): array
    {
        global $db;

        $uids    = [];
        $unknown = [];
        foreach (explode(',', $names) as $name) {
            $name = trim($name);
            if ($name === '') {
                continue;
            }
            $row = $db->fetch_array($db->simple_select('users', 'uid',
                "username = '" . $db->escape_string($name) . "'", ['limit' => 1]));
            if ($row) {
                $uids[] = (int)$row['uid'];
            } else {
                $unknown[] = $name;
            }
        }

        return ['uids' => array_values(array_unique($uids)), 'unknown' => $unknown];
    }

    /** @return array uid => ['uid','username','usergroup','displaygroup'] */
    public static function getUsersByUids(array $uids): array
    {
        global $db;

        $uids = array_values(array_unique(array_filter(array_map('intval', $uids),
            function ($uid) { return $uid > 0; })));
        if (!$uids) {
            return [];
        }

        $users = [];
        $query = $db->simple_select('users', 'uid, username, usergroup, displaygroup',
            'uid IN (' . implode(',', $uids) . ')');
        while ($row = $db->fetch_array($query)) {
            $users[(int)$row['uid']] = $row;
        }

        return $users;
    }

    /* ---------------------------------------------------------------------
     * Alert recipient queries (deduplicated uid lists)
     * ------------------------------------------------------------------ */

    /** @return int[] distinct uids of all members of the given plotting groups */
    public static function getGroupMemberUids(array $gids): array
    {
        global $db;

        $gids = array_values(array_unique(array_filter(array_map('intval', $gids),
            function ($gid) { return $gid > 0; })));
        if (!$gids) {
            return [];
        }

        $uids = [];
        $query = $db->query('SELECT DISTINCT uid FROM ' . TABLE_PREFIX . 'plotcp_group_members
            WHERE gid IN (' . implode(',', $gids) . ')');
        while ($row = $db->fetch_array($query)) {
            if ((int)$row['uid'] > 0) {
                $uids[] = (int)$row['uid'];
            }
        }

        return $uids;
    }

    /** @return int[] uids of all members of the configured team groups */
    public static function getTeamUids(): array
    {
        global $mybb;

        $setting = isset($mybb->settings['plotcp_team_groups'])
            ? (string)$mybb->settings['plotcp_team_groups'] : '';

        return self::getUidsInUsergroups($setting);
    }

    /**
     * @return int[] uids whose primary or additional usergroup is in the CSV list
     */
    public static function getUidsInUsergroups(string $csv): array
    {
        global $db;

        $group_ids = array_values(array_unique(array_filter(array_map('intval', explode(',', $csv)),
            function ($gid) { return $gid > 0; })));
        if (!$group_ids) {
            return [];
        }

        $conditions = ['usergroup IN (' . implode(',', $group_ids) . ')'];
        foreach ($group_ids as $gid) {
            $conditions[] = "CONCAT(',', additionalgroups, ',') LIKE '%,{$gid},%'";
        }

        $uids = [];
        $query = $db->simple_select('users', 'uid', '(' . implode(' OR ', $conditions) . ')');
        while ($row = $db->fetch_array($query)) {
            if ((int)$row['uid'] > 0) {
                $uids[] = (int)$row['uid'];
            }
        }

        return array_values(array_unique($uids));
    }

    /**
     * All active main accounts. With EnhancedAccountSwitcher installed only
     * accounts with as_uid = 0 (main accounts) are returned; without it every
     * active user counts. Banned (7) and awaiting activation (5) are excluded.
     */
    public static function getMainAccountUids(): array
    {
        global $db;

        $where = 'usergroup NOT IN (5,7)';
        if ($db->field_exists('as_uid', 'users')) {
            $where .= ' AND as_uid = 0';
        }

        $uids = [];
        $query = $db->simple_select('users', 'uid', $where);
        while ($row = $db->fetch_array($query)) {
            if ((int)$row['uid'] > 0) {
                $uids[] = (int)$row['uid'];
            }
        }

        return $uids;
    }
}
