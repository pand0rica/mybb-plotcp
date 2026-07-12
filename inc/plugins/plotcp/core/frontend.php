<?php
/**
 * plotcp – Frontend rendering
 *
 * Builds the frontend views (plot list, detail page, groups page, submission
 * form) and the scene box shown below threads in configured forums. Page
 * skeletons live in MyBB templates; only small fragments (badges, buttons,
 * select options) are assembled here – always escaped.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

class Plotcp_Frontend
{
    /* ---------------------------------------------------------------------
     * Browse view: category tabs on top, plot navigation (grouped by inplay
     * year, newest first) on the left, selected plot detail on the right.
     * ------------------------------------------------------------------ */

    public static function renderBrowse(int $cat, int $pid): string
    {
        global $mybb, $db, $lang, $templates, $theme, $header, $headerinclude, $footer;

        // No active/archive split: every plot (archived ones flagged only by
        // their badge) is listed in its category.
        $condition = self::visibilityCondition();

        $categories = Plotcp_Database::getCategories();

        // plot count per category (visibility only) → which tabs to show
        $counts = [];
        $q = $db->query('SELECT cid, COUNT(*) AS num FROM ' . TABLE_PREFIX . 'plotcp_plots
            WHERE ' . $condition . ' GROUP BY cid');
        while ($row = $db->fetch_array($q)) {
            $counts[(int)$row['cid']] = (int)$row['num'];
        }

        // active category: requested one if it exists, else the first category
        // with plots, else simply the first category (empty ones are reachable)
        $active_cat = 0;
        if ($cat > 0 && isset($categories[$cat])) {
            $active_cat = $cat;
        } else {
            foreach ($categories as $cid => $category) {
                if (!empty($counts[$cid])) {
                    $active_cat = (int)$cid;
                    break;
                }
            }
            if ($active_cat === 0 && $categories) {
                $active_cat = (int)array_key_first($categories);
            }
        }

        // category tab bar (server-side links, thead = active, tcat = rest).
        // Every category gets a tab, including those without plots yet.
        $tab_cells = '';
        foreach ($categories as $cid => $category) {
            $cls = ((int)$cid === $active_cat ? 'thead' : 'tcat') . ' plotcp_tab';
            $tab_cells .= '<td class="' . $cls . '"><a href="plotcp.php?cat=' . (int)$cid . '">'
                . htmlspecialchars_uni((string)$category['name']) . '</a></td>';
        }
        $category_tabs = '';
        if ($tab_cells !== '') {
            $category_tabs = '<table border="0" cellspacing="' . (int)$theme['borderwidth']
                . '" cellpadding="' . (int)$theme['tablespace'] . '" class="tborder plotcp_tabs"><tr>'
                . $tab_cells . '<td class="tcat" width="100%">&nbsp;</td></tr></table>';
        }

        // description of the active category, shown between the tabs and the
        // plot list. Admin-authored, so raw HTML/MyCode is allowed like plot
        // descriptions. Empty categories render nothing.
        $category_description = '';
        if ($active_cat > 0
            && isset($categories[$active_cat])
            && trim((string)$categories[$active_cat]['description']) !== ''
        ) {
            $desc = self::parseDescription((string)$categories[$active_cat]['description']);
            $category_description = '<table border="0" cellspacing="' . (int)$theme['borderwidth']
                . '" cellpadding="' . (int)$theme['tablespace']
                . '" class="tborder plotcp_cat_desc"><tr><td class="trow1 plotcp_description">'
                . $desc . '</td></tr></table>';
        }

        // plots of the active category, newest inplay date first
        $plots = [];
        if ($active_cat > 0) {
            $query = $db->simple_select('plotcp_plots', '*',
                'cid = ' . $active_cat . ' AND ' . $condition,
                ['order_by' => 'start_key DESC, title ASC']);
            while ($row = $db->fetch_array($query)) {
                $plots[] = $row;
            }
        }

        // active plot: requested one if in this list, else the newest
        $active_plot = null;
        if ($pid > 0) {
            foreach ($plots as $p) {
                if ((int)$p['pid'] === $pid) {
                    $active_plot = $p;
                    break;
                }
            }
        }
        if ($active_plot === null && $plots) {
            $active_plot = $plots[0];
        }

        // left navigation, grouped by inplay year (start_year), newest first
        $nav = '';
        if ($plots) {
            $cat_name  = htmlspecialchars_uni((string)$categories[$active_cat]['name']);
            $nav_rows  = '';
            $altbg     = 'trow1';
            $cur_year  = null;
            $active_id = $active_plot ? (int)$active_plot['pid'] : 0;
            foreach ($plots as $p) {
                $year = (int)$p['start_year'];
                if ($year !== $cur_year) {
                    $nav_rows .= '<tr><td class="tcat"><span class="smalltext"><strong>'
                        . $year . '</strong></span></td></tr>';
                    $cur_year = $year;
                    $altbg = 'trow1';
                }
                $rid   = (int)$p['pid'];
                $cls   = $rid === $active_id ? 'trow_shaded' : $altbg;
                $label = htmlspecialchars_uni((string)$p['title']) . self::badges($p);
                $nav_rows .= '<tr><td class="' . $cls . '"><a href="plotcp.php?cat='
                    . $active_cat . '&amp;pid=' . $rid . '">' . $label . '</a></td></tr>';
                $altbg = $altbg === 'trow1' ? 'trow2' : 'trow1';
            }
            eval('$nav = "' . $templates->get('plotcp_browse_nav') . '";');
        }

        // right detail pane
        $detail = '';
        if ($active_plot !== null) {
            $detail = self::renderDetail($active_plot);
        } else {
            $detail = '<table border="0" cellspacing="' . (int)$theme['borderwidth']
                . '" cellpadding="' . (int)$theme['tablespace'] . '" class="tborder"><tr><td class="trow1">'
                . htmlspecialchars_uni($lang->plotcp_no_plots) . '</td></tr></table>';
        }

        // toolbar (submit / groups)
        $toolbar = ['<a href="plotcp.php?action=groups">' . htmlspecialchars_uni($lang->plotcp_btn_groups) . '</a>'];
        if ((int)$mybb->user['uid'] > 0
            && isset($mybb->settings['plotcp_allow_plot_submissions'])
            && (int)$mybb->settings['plotcp_allow_plot_submissions'] === 1
        ) {
            array_unshift($toolbar,
                '<a href="plotcp.php?action=submit">' . htmlspecialchars_uni($lang->plotcp_btn_submit_plot) . '</a>');
        }
        $toolbar_buttons = implode(' &middot; ', $toolbar);

        if ($active_cat === 0) {
            eval('$content = "' . $templates->get('plotcp_index_empty') . '";');
        } else {
            eval('$content = "' . $templates->get('plotcp_browse_body') . '";');
        }

        eval('$html = "' . $templates->get('plotcp_browse') . '";');

        return $html;
    }

    /**
     * SQL condition (no leading AND) limiting plots to what the current user
     * may see: everyone sees published plots, preview-eligible users see all,
     * logged-in users additionally see their own submissions.
     */
    private static function visibilityCondition(): string
    {
        global $mybb;

        if (Plotcp_Permissions::canViewAnyPreview($mybb->user)) {
            return '1 = 1';
        }
        $uid = (int)$mybb->user['uid'];

        return $uid > 0 ? '(visible = 1 OR submitted_uid = ' . $uid . ')' : 'visible = 1';
    }

    /**
     * Renders the plot detail fragment (info table, team bar, linked scenes)
     * for the right pane / the standalone view.
     */
    public static function renderDetail(array $plot): string
    {
        global $mybb, $db, $lang, $templates, $theme;

        $pid = (int)$plot['pid'];

        $plot_title  = htmlspecialchars_uni((string)$plot['title']);
        $plot_badges = self::badges($plot);

        $category      = Plotcp_Database::getCategory((int)$plot['cid']);
        $category_name = $category ? htmlspecialchars_uni((string)$category['name']) : '&ndash;';

        $plot_groups = self::groupBadges(self::groupNamesForPlot($pid));

        $plot_location = $plot['location'] !== '' ? htmlspecialchars_uni((string)$plot['location']) : '&ndash;';
        $plot_date     = self::formatDateRange($plot);

        // contact persons as profile links
        $plot_contacts = '&ndash;';
        if (!empty($plot['contacts'])) {
            $links = [];
            foreach (Plotcp_Database::getUsersByUids(explode(',', (string)$plot['contacts'])) as $user) {
                $links[] = build_profile_link(
                    format_name(htmlspecialchars_uni($user['username']),
                        $user['usergroup'], $user['displaygroup']),
                    $user['uid']
                );
            }
            if ($links) {
                $plot_contacts = implode(', ', $links);
            }
        }

        $plot_description = self::parseDescription((string)$plot['description']);

        // team button (publish)
        $teambar = '';
        if (Plotcp_Permissions::isTeam($mybb->user)) {
            $team_buttons = self::teamButtons($plot);
            if ($team_buttons !== '') {
                eval('$teambar = "' . $templates->get('plotcp_view_teambar') . '";');
            }
        }

        // linked scenes (threads); silently skip rows whose thread vanished
        $scene_rows = '';
        $altbg = 'trow1';
        $query = $db->query('
            SELECT pt.tid, t.subject, t.uid AS thread_uid, t.visible AS thread_visible
            FROM ' . TABLE_PREFIX . 'plotcp_plot_threads pt
            LEFT JOIN ' . TABLE_PREFIX . 'threads t ON (t.tid = pt.tid)
            WHERE pt.pid = ' . $pid . '
            ORDER BY t.subject ASC
        ');
        $scene_threads = [];
        while ($row = $db->fetch_array($query)) {
            if ($row['subject'] === null || (int)$row['thread_visible'] < 0) {
                continue;
            }
            $scene_threads[] = $row;
        }

        // scene metadata (date, daytime, location) comes from the logbook plugin
        $logbook_scenes = Plotcp_Database::getLogbookScenes(
            array_map(function ($r) { return (int)$r['tid']; }, $scene_threads)
        );

        foreach ($scene_threads as $row) {
            $tid            = (int)$row['tid'];
            $thread_link    = htmlspecialchars_uni(get_thread_link($tid));
            $thread_subject = htmlspecialchars_uni((string)$row['subject']);
            $scene_info     = isset($logbook_scenes[$tid])
                ? self::logbookSceneInfo($logbook_scenes[$tid])
                : '';
            $unlink_form    = Plotcp_Permissions::canLinkThread($mybb->user, ['uid' => (int)$row['thread_uid']])
                ? self::unlinkForm($tid)
                : '';
            eval('$scene_rows .= "' . $templates->get('plotcp_view_scene_bit') . '";');
            $altbg = $altbg === 'trow1' ? 'trow2' : 'trow1';
        }
        if ($scene_rows === '') {
            $scene_rows = '<tr><td class="trow1" colspan="2">'
                . htmlspecialchars_uni($lang->plotcp_view_no_scenes) . '</td></tr>';
        }

        eval('$detail = "' . $templates->get('plotcp_detail') . '";');

        return $detail;
    }

    /**
     * Formats a logbook scene row (participants, location, in-play date,
     * daytime, scene type) for the linked-scene list. Mirrors the meta bar the
     * logbook plugin renders in the forum thread listing
     * (logbook_handle_thread_display): same icons and logbook-thread-meta
     * classes, so the row matches that look – logbook.css is attached globally.
     * Reuses the logbook plugin's own helpers when it is active and degrades to
     * the raw values otherwise. Returns '' when nothing is known.
     */
    private static function logbookSceneInfo(array $scene): string
    {
        global $lang;

        // pull in the logbook language strings for month/daytime names once
        static $lang_loaded = false;
        if (!$lang_loaded && function_exists('logbook_get_month')) {
            $lang->load('logbook', false, true);
            $lang_loaded = true;
        }

        // same inline icons the logbook plugin uses in the thread listing
        $icon_users    = '<svg class="lb-icon" viewBox="0 0 16 16" fill="none"><circle cx="5.5" cy="5" r="2.5" stroke="currentColor" stroke-width="1.3"/><path d="M1 13.5c0-2.49 2.01-4.5 4.5-4.5S10 11.01 10 13.5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/><circle cx="11.5" cy="5" r="2" stroke="currentColor" stroke-width="1.3"/><path d="M13.5 9.5c1.38.5 2.5 1.8 2.5 4" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>';
        $icon_pin      = '<svg class="lb-icon" viewBox="0 0 16 16" fill="none"><path d="M8 1.5C5.52 1.5 3.5 3.52 3.5 6c0 3.75 4.5 8.5 4.5 8.5S12.5 9.75 12.5 6C12.5 3.52 10.48 1.5 8 1.5zM8 7.75A1.75 1.75 0 1 1 8 4.25 1.75 1.75 0 0 1 8 7.75z" fill="currentColor"/></svg>';
        $icon_calendar = '<svg class="lb-icon" viewBox="0 0 16 16" fill="none"><rect x="1.5" y="3" width="13" height="11" rx="1.5" stroke="currentColor" stroke-width="1.3"/><path d="M1.5 7h13" stroke="currentColor" stroke-width="1.3"/><path d="M5 1.5V4M11 1.5V4" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>';
        $icon_clock    = '<svg class="lb-icon" viewBox="0 0 16 16" fill="none"><circle cx="8" cy="8" r="6.5" stroke="currentColor" stroke-width="1.3"/><path d="M8 4.5V8l2.5 2" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>';

        $items = '';

        // participants (already HTML-safe: usernames escaped inside the helper)
        if ($scene['id'] > 0 && function_exists('logbook_get_participant_string')) {
            $members = logbook_get_participant_string((int)$scene['id']);
            if ($members !== '') {
                $items .= '<span class="logbook-thread-meta-item">' . $icon_users . $members . '</span>';
            }
        }

        // location
        if (trim((string)$scene['ort']) !== '') {
            $items .= '<span class="logbook-thread-meta-item">' . $icon_pin
                . htmlspecialchars_uni((string)$scene['ort']) . '</span>';
        }

        // in-play date: only the components that are actually set
        $date = [];
        if ($scene['tag'] > 0) {
            $date[] = (int)$scene['tag'] . '.';
        }
        if ($scene['monat'] > 0) {
            $date[] = function_exists('logbook_get_month')
                ? logbook_get_month((int)$scene['monat'])
                : (string)$scene['monat'];
        }
        if ($scene['jahr'] > 0) {
            $date[] = (string)(int)$scene['jahr'];
        }
        if ($date) {
            $items .= '<span class="logbook-thread-meta-item">' . $icon_calendar
                . htmlspecialchars_uni(implode(' ', $date)) . '</span>';
        }

        // daytime (morning … night); only meaningful with the logbook helper
        if ($scene['time'] > 0 && function_exists('logbook_get_time')) {
            $items .= '<span class="logbook-thread-meta-item">' . $icon_clock
                . htmlspecialchars_uni(logbook_get_time((int)$scene['time'])) . '</span>';
        }

        // scene type badge (already HTML from the logbook helper)
        if (function_exists('logbook_get_scene_type_badge')) {
            $badge = logbook_get_scene_type_badge((int)$scene['scene_type']);
            if ($badge !== '') {
                $items .= '<span class="logbook-thread-meta-item logbook-thread-meta-badge">' . $badge . '</span>';
            }
        }

        if ($items === '') {
            return '';
        }

        return '<span class="logbook-thread-meta">' . $items . '</span>';
    }

    /* ---------------------------------------------------------------------
     * Groups page (join/leave, propose form)
     * ------------------------------------------------------------------ */

    public static function renderGroups(int $selected_gid = 0): string
    {
        global $mybb, $lang, $templates, $theme, $header, $headerinclude, $footer;

        $uid       = (int)$mybb->user['uid'];
        $is_team   = Plotcp_Permissions::isTeam($mybb->user);
        $counts    = Plotcp_Database::getGroupMemberCounts();
        $user_gids = $uid > 0 ? Plotcp_Database::getUserGroupIds($uid) : [];

        $group_rows = '';
        $altbg = 'trow1';
        foreach (Plotcp_Database::getGroups(false) as $gid => $group) {
            $pending = (int)$group['approved'] === 0;

            // pending proposals are only visible to team and their submitter
            if ($pending && !$is_team && (int)$group['submitted_uid'] !== $uid) {
                continue;
            }

            $group_description = htmlspecialchars_uni((string)$group['description']);

            if ($pending) {
                // pending groups have no members yet, so no link
                $group_name = htmlspecialchars_uni((string)$group['name']);
                eval('$group_rows .= "' . $templates->get('plotcp_groups_pending_bit') . '";');
            } else {
                // approved group names link to their member list below
                $group_name = '<a href="plotcp.php?action=groups&amp;gid=' . (int)$gid
                    . '#plotcp_members">' . htmlspecialchars_uni((string)$group['name']) . '</a>';
                $group_row_bg      = ((int)$gid === $selected_gid) ? 'trow_shaded' : $altbg;
                $member_count      = isset($counts[$gid]) ? (int)$counts[$gid] : 0;
                $membership_button = $uid > 0
                    ? self::membershipButton((int)$gid, in_array((int)$gid, $user_gids, true))
                    : '&nbsp;';
                eval('$group_rows .= "' . $templates->get('plotcp_groups_bit') . '";');
            }
            $altbg = $altbg === 'trow1' ? 'trow2' : 'trow1';
        }
        if ($group_rows === '') {
            $group_rows = '<tr><td class="trow1" colspan="4" align="center">'
                . htmlspecialchars_uni($lang->plotcp_no_groups) . '</td></tr>';
        }

        // member list of the selected group (approved groups only; anyone who
        // can see the page may see who is in a group, like the member counts)
        $members_panel = '';
        if ($selected_gid > 0) {
            $sel_group = Plotcp_Database::getGroup($selected_gid);
            if ($sel_group && (int)$sel_group['approved'] === 1) {
                $members_title = $lang->sprintf($lang->plotcp_group_members_title,
                    htmlspecialchars_uni((string)$sel_group['name']));

                $member_rows = '';
                $altbg = 'trow1';
                foreach (Plotcp_Database::getGroupMembers($selected_gid) as $m) {
                    $member_link = build_profile_link(
                        format_name(htmlspecialchars_uni((string)$m['username']),
                            $m['usergroup'], $m['displaygroup']),
                        (int)$m['uid']);
                    $member_joined = my_date($mybb->settings['dateformat'], (int)$m['dateline']);
                    // team members can remove a person from the group
                    $member_controls = $is_team
                        ? self::removeMemberForm($selected_gid, (int)$m['uid'])
                        : '&nbsp;';
                    eval('$member_rows .= "' . $templates->get('plotcp_groups_member_bit') . '";');
                    $altbg = $altbg === 'trow1' ? 'trow2' : 'trow1';
                }
                if ($member_rows === '') {
                    $member_rows = '<tr><td class="trow1" colspan="3">'
                        . htmlspecialchars_uni($lang->plotcp_group_no_members) . '</td></tr>';
                }
                eval('$members_panel = "' . $templates->get('plotcp_groups_members') . '";');
            }
        }

        $propose_form = '';
        if ($uid > 0
            && isset($mybb->settings['plotcp_allow_group_submissions'])
            && (int)$mybb->settings['plotcp_allow_group_submissions'] === 1
        ) {
            eval('$propose_form = "' . $templates->get('plotcp_groups_propose') . '";');
        }

        eval('$html = "' . $templates->get('plotcp_groups') . '";');

        return $html;
    }

    /* ---------------------------------------------------------------------
     * Plot submission form
     * ------------------------------------------------------------------ */

    public static function renderSubmitForm(): string
    {
        global $mybb, $lang, $templates, $theme, $header, $headerinclude, $footer;

        $category_options = '';
        foreach (Plotcp_Database::getCategories() as $cid => $category) {
            $category_options .= '<option value="' . (int)$cid . '">'
                . htmlspecialchars_uni((string)$category['name']) . '</option>';
        }

        $group_checkboxes = '';
        foreach (Plotcp_Database::getGroups(true) as $gid => $group) {
            $group_checkboxes .= '<label><input type="checkbox" name="gids[]" value="' . (int)$gid . '" /> '
                . htmlspecialchars_uni((string)$group['name']) . '</label>';
        }
        if ($group_checkboxes === '') {
            $group_checkboxes = htmlspecialchars_uni($lang->plotcp_no_groups);
        }

        $form_title = $form_location = $form_contacts = $form_description = '';
        $form_start_day = $form_start_month = $form_start_year = '';
        $form_end_day = $form_end_month = $form_end_year = '';

        eval('$html = "' . $templates->get('plotcp_submit_form') . '";');

        return $html;
    }

    /* ---------------------------------------------------------------------
     * Scene box (showthread)
     * ------------------------------------------------------------------ */

    public static function buildSceneBox(): string
    {
        global $mybb, $db, $lang, $templates, $theme, $thread;

        if (!is_array($thread) || empty($thread['tid'])) {
            return '';
        }
        if (!Plotcp_Permissions::isSceneForum((int)$thread['fid'])) {
            return '';
        }

        $lang->load('plotcp');

        $tid = (int)$thread['tid'];
        $thread_id = $tid;

        $link = $db->fetch_array($db->simple_select('plotcp_plot_threads', 'pid', 'tid = ' . $tid));
        if ($link) {
            $plot = Plotcp_Database::getPlot((int)$link['pid']);
            if (!$plot) {
                return '';
            }
            // linked previews stay hidden from users who may not see them
            if ((int)$plot['visible'] === 0 && !Plotcp_Permissions::canViewPreview($mybb->user, $plot)) {
                return '';
            }
            $plot_id     = (int)$plot['pid'];
            $plot_title  = htmlspecialchars_uni((string)$plot['title']);
            $unlink_form = Plotcp_Permissions::canLinkThread($mybb->user, $thread)
                ? ' ' . self::unlinkForm($tid)
                : '';
            eval('$box = "' . $templates->get('plotcp_scene_box') . '";');

            return $box;
        }

        if (!Plotcp_Permissions::canLinkThread($mybb->user, $thread)) {
            return '';
        }

        $plot_options = self::plotOptionsHtml();
        if ($plot_options === '') {
            return '';
        }

        eval('$box = "' . $templates->get('plotcp_scene_box_form') . '";');

        return $box;
    }

    /**
     * Plot select row for the new-thread form ({$plotcp_newthread_select},
     * injected after {$posticons}). Empty outside configured forums; the
     * selection survives previews/error roundtrips via plotcp_pid.
     */
    public static function buildNewthreadSelect(): string
    {
        global $mybb, $lang, $templates, $theme, $fid;

        if ((int)$mybb->user['uid'] <= 0) {
            return '';
        }
        if (!Plotcp_Permissions::isSceneForum((int)$fid)) {
            return '';
        }

        $lang->load('plotcp');

        $plot_options = self::plotOptionsHtml((int)$mybb->get_input('plotcp_pid', MyBB::INPUT_INT));
        if ($plot_options === '') {
            return '';
        }

        eval('$row = "' . $templates->get('plotcp_newthread_select') . '";');

        return $row;
    }

    /**
     * Option list of linkable plots: published for everyone, previews
     * additionally for the team.
     */
    private static function plotOptionsHtml(int $selected = 0): string
    {
        global $mybb, $db, $lang;

        $where = Plotcp_Permissions::isTeam($mybb->user) ? '1=1' : 'visible = 1';

        $plot_options = '';
        $query = $db->simple_select('plotcp_plots', 'pid, title, visible', $where, ['order_by' => 'title']);
        while ($plot = $db->fetch_array($query)) {
            $suffix = (int)$plot['visible'] === 0
                ? ' [' . htmlspecialchars_uni($lang->plotcp_badge_preview) . ']'
                : '';
            $sel = $selected > 0 && (int)$plot['pid'] === $selected ? ' selected="selected"' : '';
            $plot_options .= '<option value="' . (int)$plot['pid'] . '"' . $sel . '>'
                . htmlspecialchars_uni((string)$plot['title']) . $suffix . '</option>';
        }

        return $plot_options;
    }

    /* ---------------------------------------------------------------------
     * Fragments & helpers
     * ------------------------------------------------------------------ */

    /** "12.3.1247 – 2.4.1247" (single day: only the start date). */
    /* ---------------------------------------------------------------------
     * Global template variable (current inplay months)
     * ------------------------------------------------------------------ */

    /**
     * Builds {$plotcp_current_plots}: published plots whose inplay range
     * overlaps the configured current inplay months, rendered through the
     * plotcp_current_* templates. Empty string when there is nothing to show.
     * The month texts of these months come from the rpgcalendar plugin.
     */
    public static function buildCurrentPlots(): string
    {
        global $templates;

        $plots = Plotcp_Database::getPlotsForInplayMonths(Plotcp_Database::currentInplayMonths());
        if (!$plots) {
            return '';
        }

        $plot_items = '';
        foreach ($plots as $p) {
            $plot_link   = 'plotcp.php?action=view&amp;pid=' . (int)$p['pid'];
            $plot_title  = htmlspecialchars_uni((string)$p['title']);
            $plot_badges = self::badges($p);
            $plot_date   = self::formatDateRange($p);
            eval('$plot_items .= "' . $templates->get('plotcp_current_plot_bit') . '";');
        }

        eval('$plots_out = "' . $templates->get('plotcp_current_plots') . '";');

        return $plots_out;
    }

    public static function formatDateRange(array $plot): string
    {
        if ((int)$plot['has_date'] !== 1) {
            return '&ndash;';
        }

        $start = (int)$plot['start_day'] . '.' . (int)$plot['start_month'] . '.' . (int)$plot['start_year'];
        $end   = (int)$plot['end_day'] . '.' . (int)$plot['end_month'] . '.' . (int)$plot['end_year'];

        return $start === $end ? $start : $start . ' &ndash; ' . $end;
    }

    private static function badges(array $plot): string
    {
        global $lang;

        $badges = '';
        if ((int)$plot['visible'] === 0) {
            $badges .= ' <span class="plotcp_badge plotcp_badge--preview">'
                . htmlspecialchars_uni($lang->plotcp_badge_preview) . '</span>';
        }

        return $badges;
    }

    private static function groupBadges(array $names): string
    {
        global $lang;

        if (!$names) {
            return '<span class="smalltext">' . htmlspecialchars_uni($lang->plotcp_view_no_groups) . '</span>';
        }

        $badges = [];
        foreach ($names as $name) {
            $badges[] = '<span class="plotcp_badge plotcp_badge--group">'
                . htmlspecialchars_uni((string)$name) . '</span>';
        }

        return implode(' ', $badges);
    }

    /** @return array pid => group name list (one query for all pids) */
    private static function groupNamesForPlots(array $pids): array
    {
        global $db;

        $pids = array_values(array_unique(array_filter(array_map('intval', $pids),
            function ($pid) { return $pid > 0; })));
        if (!$pids) {
            return [];
        }

        $names = [];
        $query = $db->query('
            SELECT pg.pid, g.name
            FROM ' . TABLE_PREFIX . 'plotcp_plot_groups pg
            INNER JOIN ' . TABLE_PREFIX . 'plotcp_groups g ON (g.gid = pg.gid)
            WHERE pg.pid IN (' . implode(',', $pids) . ')
            ORDER BY g.name ASC
        ');
        while ($row = $db->fetch_array($query)) {
            $names[(int)$row['pid']][] = (string)$row['name'];
        }

        return $names;
    }

    private static function groupNamesForPlot(int $pid): array
    {
        $names = self::groupNamesForPlots([$pid]);

        return isset($names[$pid]) ? $names[$pid] : [];
    }

    private static function parseDescription(string $text): string
    {
        global $parser;

        if (!is_object($parser)) {
            require_once MYBB_ROOT . 'inc/class_parser.php';
            $parser = new postParser();
        }

        // allow_html = 1 is a deliberate choice by the forum admin: plot
        // descriptions may contain raw HTML (including user submissions). See
        // CLAUDE.md / README security note.
        return $parser->parse_message($text, [
            'allow_html'      => 1,
            'allow_mycode'    => 1,
            'allow_smilies'   => 1,
            'allow_imgcode'   => 1,
            'filter_badwords' => 1,
        ]);
    }

    private static function membershipButton(int $gid, bool $is_member): string
    {
        global $mybb, $lang;

        $action = $is_member ? 'leave_group' : 'join_group';
        $label  = $is_member ? $lang->plotcp_btn_leave : $lang->plotcp_btn_join;

        return '<form action="plotcp.php" method="post" class="plotcp_inline_form">'
            . '<input type="hidden" name="action" value="' . $action . '" />'
            . '<input type="hidden" name="gid" value="' . $gid . '" />'
            . '<input type="hidden" name="my_post_key" value="' . $mybb->post_code . '" />'
            . '<input type="submit" class="button plotcp_button_small" value="'
            . htmlspecialchars_uni($label) . '" />'
            . '</form>';
    }

    private static function teamButtons(array $plot): string
    {
        global $mybb, $lang;

        $pid = (int)$plot['pid'];
        $buttons = [];

        // Only unpublished previews get a team action (publish).
        if ((int)$plot['visible'] === 0) {
            $buttons[] = ['publish', $lang->plotcp_btn_publish];
        }

        $html = '';
        foreach ($buttons as $button) {
            $html .= '<form action="plotcp.php" method="post" class="plotcp_inline_form">'
                . '<input type="hidden" name="action" value="' . $button[0] . '" />'
                . '<input type="hidden" name="pid" value="' . $pid . '" />'
                . '<input type="hidden" name="my_post_key" value="' . $mybb->post_code . '" />'
                . '<input type="submit" class="button" value="' . htmlspecialchars_uni($button[1]) . '" />'
                . '</form> ';
        }

        return trim($html);
    }

    private static function unlinkForm(int $tid): string
    {
        global $mybb, $lang;

        return '<form action="plotcp.php" method="post" class="plotcp_inline_form">'
            . '<input type="hidden" name="action" value="unlink_thread" />'
            . '<input type="hidden" name="tid" value="' . $tid . '" />'
            . '<input type="hidden" name="my_post_key" value="' . $mybb->post_code . '" />'
            . '<input type="submit" class="button plotcp_button_small" value="'
            . htmlspecialchars_uni($lang->plotcp_btn_unlink) . '" />'
            . '</form>';
    }

    /** Team-only button to remove a member from a plotting group. */
    private static function removeMemberForm(int $gid, int $uid): string
    {
        global $mybb, $lang;

        return '<form action="plotcp.php" method="post" class="plotcp_inline_form" '
            . 'onsubmit="return confirm(\'' . addslashes($lang->plotcp_confirm_remove_member) . '\');">'
            . '<input type="hidden" name="action" value="remove_group_member" />'
            . '<input type="hidden" name="gid" value="' . $gid . '" />'
            . '<input type="hidden" name="uid" value="' . $uid . '" />'
            . '<input type="hidden" name="my_post_key" value="' . $mybb->post_code . '" />'
            . '<input type="submit" class="button plotcp_button_small" value="'
            . htmlspecialchars_uni($lang->plotcp_btn_remove_member) . '" />'
            . '</form>';
    }
}
