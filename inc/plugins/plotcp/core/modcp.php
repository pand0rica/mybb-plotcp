<?php
/**
 * plotcp – ModCP integration
 *
 * Renders the team-only ModCP page for reviewing plotting group proposals
 * (approve / edit / reject) and builds the two shared fragments injected into
 * core templates: the ModCP navigation entry ({$plotcp_modcp_nav}) and the
 * pending-proposals notice on the board index ({$plotcp_group_notice}).
 *
 * Access is gated twice: MyBB's own modcp.php entry check (canmodcp) plus a
 * Plotcp_Permissions::isTeam() check here, so only plot team members who are
 * also moderators can act.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

class Plotcp_Modcp
{
    /** The single ModCP action this module owns. */
    const ACTION = 'plotcp_groups';

    /* ---------------------------------------------------------------------
     * Dispatch (called from the modcp_start hook for our own action)
     * ------------------------------------------------------------------ */

    public static function handle(): void
    {
        global $mybb, $db, $lang;

        if (!Plotcp_Permissions::isTeam($mybb->user)) {
            error_no_permission();
        }

        $lang->load('plotcp');
        $base = 'modcp.php?action=' . self::ACTION;

        if ($mybb->request_method === 'post') {
            verify_post_check($mybb->get_input('my_post_key'));

            $sub_action = $mybb->get_input('sub_action');
            $gid        = (int)$mybb->get_input('gid', MyBB::INPUT_INT);
            $group      = Plotcp_Database::getGroup($gid);

            if (!$group) {
                error($lang->plotcp_error_invalid_group);
            }

            if ($sub_action === 'approve') {
                if ((int)$group['approved'] === 1) {
                    error($lang->plotcp_error_invalid_group);
                }
                Plotcp_Database::approveGroup($gid);
                // Announce the now-available group to the members (main accounts).
                Plotcp_Alerts::sendNewGroup($group);
                redirect($base, $lang->plotcp_modcp_approved);
            }

            if ($sub_action === 'delete') {
                Plotcp_Database::deleteGroup($gid);
                redirect($base, $lang->plotcp_modcp_deleted);
            }

            if ($sub_action === 'save') {
                $name = trim((string)$mybb->get_input('name'));
                if ($name === '') {
                    error($lang->plotcp_error_group_name_missing);
                }
                if (Plotcp_Database::groupNameExists($name, $gid)) {
                    error($lang->plotcp_error_group_name_exists);
                }
                $db->update_query('plotcp_groups', [
                    'name'          => $db->escape_string($name),
                    'description'   => $db->escape_string(trim((string)$mybb->get_input('description'))),
                    'display_order' => (int)$mybb->get_input('display_order', MyBB::INPUT_INT),
                ], 'gid = ' . $gid);
                redirect($base, $lang->plotcp_modcp_saved);
            }

            error_no_permission();
        }

        // GET: edit form or list overview
        if ($mybb->get_input('do') === 'edit') {
            $html = self::renderEditForm((int)$mybb->get_input('gid', MyBB::INPUT_INT));
        } else {
            $html = self::renderList();
        }

        output_page($html);
        exit;
    }

    /* ---------------------------------------------------------------------
     * Fragments injected into core templates
     * ------------------------------------------------------------------ */

    /**
     * ModCP navigation entry (injected via {$plotcp_modcp_nav} into the
     * modcp_nav template). Shown to team members on every ModCP page, with a
     * pending count when proposals are waiting.
     */
    public static function navItem(): string
    {
        global $lang, $templates, $theme;

        $lang->load('plotcp');

        $count = Plotcp_Database::getPendingGroupCount();
        $plotcp_pending_badge = $count > 0 ? ' (' . (int)$count . ')' : '';

        eval('$nav = "' . $templates->get('plotcp_modcp_nav') . '";');

        return $nav;
    }

    /**
     * Board index notice ({$plotcp_group_notice}). Caller guarantees the user
     * is team and $count > 0.
     */
    public static function buildIndexNotice(int $count): string
    {
        global $lang, $templates, $theme;

        $lang->load('plotcp');

        $notice_text = $lang->sprintf($lang->plotcp_notice_text, (int)$count);

        eval('$notice = "' . $templates->get('plotcp_group_notice') . '";');

        return $notice;
    }

    /* ---------------------------------------------------------------------
     * Views
     * ------------------------------------------------------------------ */

    private static function renderList(): string
    {
        global $mybb, $lang, $templates, $theme, $header, $headerinclude, $footer;

        add_breadcrumb($lang->plotcp_modcp_title, 'modcp.php?action=' . self::ACTION);

        $groups = Plotcp_Database::getGroups(false); // proposals first (approved ASC)
        $counts = Plotcp_Database::getGroupMemberCounts();

        $submitter_uids = [];
        foreach ($groups as $group) {
            if ((int)$group['submitted_uid'] > 0) {
                $submitter_uids[] = (int)$group['submitted_uid'];
            }
        }
        $submitters = Plotcp_Database::getUsersByUids($submitter_uids);

        $group_rows = '';
        $altbg = 'trow1';
        foreach ($groups as $gid => $group) {
            $pending = (int)$group['approved'] === 0;

            $group_name        = htmlspecialchars_uni((string)$group['name']);
            $group_description = (string)$group['description'] !== ''
                ? htmlspecialchars_uni((string)$group['description'])
                : '&ndash;';
            $member_count = isset($counts[$gid]) ? (int)$counts[$gid] : 0;

            if ($pending) {
                $status = '<strong>' . htmlspecialchars_uni($lang->plotcp_modcp_status_pending) . '</strong>';
                if (isset($submitters[(int)$group['submitted_uid']])) {
                    $status .= '<br /><span class="smalltext">'
                        . htmlspecialchars_uni($lang->plotcp_modcp_submitted_by) . ' '
                        . htmlspecialchars_uni($submitters[(int)$group['submitted_uid']]['username'])
                        . '</span>';
                }
            } else {
                $status = htmlspecialchars_uni($lang->plotcp_modcp_status_approved);
            }

            $controls = self::controlButtons($group);

            eval('$group_rows .= "' . $templates->get('plotcp_modcp_group_bit') . '";');
            $altbg = $altbg === 'trow1' ? 'trow2' : 'trow1';
        }

        if ($group_rows === '') {
            $group_rows = '<tr><td class="trow1" colspan="5" align="center">'
                . htmlspecialchars_uni($lang->plotcp_modcp_no_groups) . '</td></tr>';
        }

        eval('$html = "' . $templates->get('plotcp_modcp_groups') . '";');

        return $html;
    }

    private static function renderEditForm(int $gid): string
    {
        global $mybb, $lang, $templates, $theme, $header, $headerinclude, $footer;

        $group = $gid > 0 ? Plotcp_Database::getGroup($gid) : null;
        if (!$group) {
            error($lang->plotcp_error_invalid_group);
        }

        add_breadcrumb($lang->plotcp_modcp_title, 'modcp.php?action=' . self::ACTION);
        add_breadcrumb($lang->plotcp_modcp_edit_group);

        $form_title       = $lang->plotcp_modcp_edit_group;
        $form_name        = htmlspecialchars_uni((string)$group['name']);
        $form_description = htmlspecialchars_uni((string)$group['description']);
        $form_order       = (int)$group['display_order'];
        $group_id         = (int)$group['gid'];

        eval('$html = "' . $templates->get('plotcp_modcp_edit') . '";');

        return $html;
    }

    /* ---------------------------------------------------------------------
     * Helpers
     * ------------------------------------------------------------------ */

    /** Approve/reject (pending) or edit/delete (approved) controls. */
    private static function controlButtons(array $group): string
    {
        global $lang;

        $gid     = (int)$group['gid'];
        $pending = (int)$group['approved'] === 0;
        $base    = 'modcp.php?action=' . self::ACTION;

        $html = '<a href="' . $base . '&amp;do=edit&amp;gid=' . $gid . '" class="button">'
            . htmlspecialchars_uni($lang->plotcp_modcp_btn_edit) . '</a> ';

        if ($pending) {
            $html .= self::postButton('approve', $gid, $lang->plotcp_modcp_btn_approve);
            $html .= self::postButton('delete', $gid, $lang->plotcp_modcp_btn_reject,
                $lang->plotcp_modcp_confirm_reject);
        } else {
            $html .= self::postButton('delete', $gid, $lang->plotcp_modcp_btn_delete,
                $lang->plotcp_modcp_confirm_delete);
        }

        return trim($html);
    }

    private static function postButton(string $sub_action, int $gid, string $label, string $confirm = ''): string
    {
        global $mybb;

        $onsubmit = $confirm !== ''
            ? ' onsubmit="return confirm(\'' . addslashes($confirm) . '\');"'
            : '';

        return '<form action="modcp.php" method="post" class="plotcp_inline_form"' . $onsubmit . '>'
            . '<input type="hidden" name="action" value="' . self::ACTION . '" />'
            . '<input type="hidden" name="sub_action" value="' . htmlspecialchars_uni($sub_action) . '" />'
            . '<input type="hidden" name="gid" value="' . $gid . '" />'
            . '<input type="hidden" name="my_post_key" value="' . $mybb->post_code . '" />'
            . '<input type="submit" class="button" value="' . htmlspecialchars_uni($label) . '" />'
            . '</form> ';
    }
}
