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
     * Plot list (grouped by category, active/archive switch)
     * ------------------------------------------------------------------ */

    public static function renderList(string $mode): string
    {
        global $mybb, $db, $lang, $templates, $theme, $header, $headerinclude, $footer;

        $archived = $mode === 'archive' ? 1 : 0;

        // previews: team/preview groups see all, users see their own submissions
        $where = 'archived = ' . $archived;
        if (!Plotcp_Permissions::canViewAnyPreview($mybb->user)) {
            $uid = (int)$mybb->user['uid'];
            $where .= $uid > 0
                ? ' AND (visible = 1 OR submitted_uid = ' . $uid . ')'
                : ' AND visible = 1';
        }

        $plots_by_cid = [];
        $pids = [];
        $query = $db->simple_select('plotcp_plots', '*', $where, ['order_by' => 'title']);
        while ($plot = $db->fetch_array($query)) {
            $plots_by_cid[(int)$plot['cid']][] = $plot;
            $pids[] = (int)$plot['pid'];
        }
        $group_names = self::groupNamesForPlots($pids);

        $category_sections = '';
        foreach (Plotcp_Database::getCategories() as $cid => $category) {
            if (empty($plots_by_cid[$cid])) {
                continue;
            }

            $plot_rows = '';
            $altbg = 'trow1';
            foreach ($plots_by_cid[$cid] as $plot) {
                $pid = (int)$plot['pid'];
                $plot_id       = $pid;
                $plot_title    = htmlspecialchars_uni((string)$plot['title']);
                $plot_badges   = self::badges($plot);
                $plot_groups   = self::groupBadges(isset($group_names[$pid]) ? $group_names[$pid] : []);
                $plot_location = $plot['location'] !== '' ? htmlspecialchars_uni((string)$plot['location']) : '&ndash;';
                $plot_date     = self::formatDateRange($plot);
                eval('$plot_rows .= "' . $templates->get('plotcp_index_plot_bit') . '";');
                $altbg = $altbg === 'trow1' ? 'trow2' : 'trow1';
            }

            $category_name        = htmlspecialchars_uni((string)$category['name']);
            $category_description = $category['description'] !== ''
                ? ' <span class="smalltext">&ndash; ' . htmlspecialchars_uni((string)$category['description']) . '</span>'
                : '';
            eval('$category_sections .= "' . $templates->get('plotcp_index_category') . '";');
        }

        if ($category_sections === '') {
            eval('$category_sections = "' . $templates->get('plotcp_index_empty') . '";');
        }

        // mode switch + toolbar links
        $active_label  = htmlspecialchars_uni($lang->plotcp_mode_active);
        $archive_label = htmlspecialchars_uni($lang->plotcp_mode_archive);
        $mode_switch = $archived === 0
            ? '<strong>' . $active_label . '</strong> | <a href="plotcp.php?mode=archive">' . $archive_label . '</a>'
            : '<a href="plotcp.php">' . $active_label . '</a> | <strong>' . $archive_label . '</strong>';

        $toolbar = ['<a href="plotcp.php?action=groups">' . htmlspecialchars_uni($lang->plotcp_btn_groups) . '</a>'];
        if ((int)$mybb->user['uid'] > 0
            && isset($mybb->settings['plotcp_allow_plot_submissions'])
            && (int)$mybb->settings['plotcp_allow_plot_submissions'] === 1
        ) {
            array_unshift($toolbar,
                '<a href="plotcp.php?action=submit">' . htmlspecialchars_uni($lang->plotcp_btn_submit_plot) . '</a>');
        }
        $toolbar_buttons = implode(' &middot; ', $toolbar);

        eval('$html = "' . $templates->get('plotcp_index') . '";');

        return $html;
    }

    /* ---------------------------------------------------------------------
     * Plot detail page
     * ------------------------------------------------------------------ */

    public static function renderView(array $plot): string
    {
        global $mybb, $db, $lang, $templates, $theme, $header, $headerinclude, $footer;

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

        // team buttons (publish / archive)
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
            SELECT pt.tid, t.subject, t.uid AS thread_uid, t.visible AS thread_visible, f.name AS forum_name
            FROM ' . TABLE_PREFIX . 'plotcp_plot_threads pt
            LEFT JOIN ' . TABLE_PREFIX . 'threads t ON (t.tid = pt.tid)
            LEFT JOIN ' . TABLE_PREFIX . 'forums f ON (f.fid = t.fid)
            WHERE pt.pid = ' . $pid . '
            ORDER BY t.subject ASC
        ');
        while ($row = $db->fetch_array($query)) {
            if ($row['subject'] === null || (int)$row['thread_visible'] < 0) {
                continue;
            }
            $tid            = (int)$row['tid'];
            $thread_link    = htmlspecialchars_uni(get_thread_link($tid));
            $thread_subject = htmlspecialchars_uni((string)$row['subject']);
            $forum_name     = htmlspecialchars_uni((string)$row['forum_name']);
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

        eval('$html = "' . $templates->get('plotcp_view') . '";');

        return $html;
    }

    /* ---------------------------------------------------------------------
     * Groups page (join/leave, propose form)
     * ------------------------------------------------------------------ */

    public static function renderGroups(): string
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

            $group_name        = htmlspecialchars_uni((string)$group['name']);
            $group_description = htmlspecialchars_uni((string)$group['description']);

            if ($pending) {
                eval('$group_rows .= "' . $templates->get('plotcp_groups_pending_bit') . '";');
            } else {
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
        $form_has_date = '';
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
        if ((int)$plot['archived'] === 1) {
            $badges .= ' <span class="plotcp_badge plotcp_badge--archived">'
                . htmlspecialchars_uni($lang->plotcp_badge_archived) . '</span>';
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

        return $parser->parse_message($text, [
            'allow_html'      => 0,
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

        if ((int)$plot['visible'] === 0) {
            $buttons[] = ['publish', $lang->plotcp_btn_publish];
        } elseif ((int)$plot['archived'] === 0) {
            $buttons[] = ['archive', $lang->plotcp_btn_archive];
        } else {
            $buttons[] = ['unarchive', $lang->plotcp_btn_unarchive];
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
}
