# plotcp – Plugin Context

## Purpose

MyBB 1.8 plugin: central plot management for RPG forums. Plot categories,
plotting groups (self-service membership), preview→publish workflow with
MyAlerts notifications, user submissions (plots + group proposals with team
approval), scene linking for inplay threads, RPG Calendar provider.

## Architecture

Class/core split (like rpgcalendar/Blacklist):

- `inc/plugins/plotcp.php` – lifecycle + hook registration only; helpers
  `plotcp_load_core()` and `plotcp_alert_codes()` (single source of the three
  alert type codes)
- `inc/plugins/plotcp/core/installer.php` – `Plotcp_Installer`: tables,
  settings (idempotent upgrade path), templates from `.tpl` files, stylesheet
  in all themes, alert types, four reversible core-template injections
  (`{$plotcp_scene_box}` after `{$pollbox}` in showthread,
  `{$plotcp_newthread_select}` after `{$posticons}` in newthread,
  `{$plotcp_group_notice}` after `{$header}` in index,
  `{$plotcp_modcp_nav}` before the final `</table>` in modcp_nav; each removal
  runs before its injection → idempotent)
- `inc/plugins/plotcp/core/modcp.php` – `Plotcp_Modcp`: team-only ModCP page
  for reviewing group proposals (approve/edit/reject; reject = delete) plus the
  two injected fragments (`navItem()` → `{$plotcp_modcp_nav}`,
  `buildIndexNotice()` → `{$plotcp_group_notice}`). Driven by the `modcp_start`
  hook (sets nav for every ModCP page of a team member; renders + `exit`s for
  `action=plotcp_groups`) and `index_start` (notice when proposals are
  pending). Access = MyBB `canmodcp` gate ∩ `Plotcp_Permissions::isTeam`
  (team = moderators). Uses only core theme classes, so no stylesheet attach is
  needed on index/modcp. The ACP module keeps the same actions for admins.
- `inc/plugins/plotcp/core/database.php` – `Plotcp_Database`: all CRUD,
  calendar math + date validation, group diff (`setPlotGroups`), alert
  recipient queries (group members, team, main accounts). Also: Logbook scene
  lookup (`getLogbookScenes`, best effort via `table_exists('scene')`), single
  group's member list (`getGroupMembers`), current-inplay parsing
  (`currentInplayMonths` from the `plotcp_current_inplay` setting) + overlap
  query (`getPlotsForInplayMonths`, `visible = 1` only)
- `inc/plugins/plotcp/core/permissions.php` – `Plotcp_Permissions`: isTeam,
  canViewPreview (team ∪ preview_groups ∪ submitter), canEditPlot
  (team any plot ∪ submitter of own plot while visible = 0), canLinkThread
  (starter ∨ team), isSceneForum; always checks usergroup AND additionalgroups
- `inc/plugins/plotcp/core/alerts.php` – `Plotcp_Alerts`: best-effort MyAlerts
  (class_exists guards + try/catch). IMPORTANT: creates MyAlerts manager
  instances itself when missing and calls `commit()` after sending – MyAlerts
  only queues in `addAlert()` and flushes at end of web requests, never in CLI
- `inc/plugins/plotcp/core/frontend.php` – `Plotcp_Frontend`: all frontend
  rendering incl. scene box; page skeletons in templates, small fragments
  (badges, buttons, options) built in PHP with escaping. The main browse view
  (`renderBrowse`) is a master-detail layout: server-side category tabs, left
  plot nav grouped by `start_year` DESC + `start_key` DESC (newest first),
  right pane = `renderDetail($plot)`. Both the default list and `action=view`
  route through `renderBrowse`. Colours come from `thead`/`tcat` theme classes;
  the active plot in the nav uses a neutral marker (`plotcp_nav_active` = bold,
  plus a `»` prefix), NOT `trow_shaded` (which reads as deleted/moderated in
  some themes). Categories
  are shown as tabs even without plots; a category's (long, TEXT) description is
  rendered between the tabs and the plot list. `renderDetail` lists linked
  scenes with their Logbook meta bar (participants/location/date/daytime/type,
  same icons/classes as logbook's `forumdisplay_thread_end`). `renderGroups(gid)`
  shows a clicked group's member list (team gets a per-member remove button →
  `remove_group_member` POST). `buildCurrentPlots()` renders the
  `{$plotcp_current_plots}` global (see hooks below). `renderEditForm($plot)` is
  the frontend full-edit form (pre-filled; posts to `do_edit`), gated by
  `canEditPlot`; the edit button sits in the plot detail action bar next to the
  team publish button.
- `inc/plugins/plotcp/myalerts_formatter.php` – one formatter per alert code;
  escapes EVERY extra-details value on render (extra details are stored raw)
- `inc/plugins/plotcp/calendar_provider.php` – `rpgcalendar_collect_events`
  handler: one indexed query, titles raw, `visible = 1` only (published plots)
- `forumdisplay_thread` hook (`plotcp_handle_forumdisplay_thread` in the main
  file) – prepends `[Plot] ` to `$thread['subject']` for linked threads. Runs
  before MyBB escapes the subject (line ~1092), so the plot title stays raw
  here; the tid→plot map is built once per page from `$threadcache`. Preview
  names only shown to viewers who pass `canViewPreview()`.
- `global_intermediate` (`plotcp_handle_globals`) – builds `{$plotcp_current_plots}`
  (published plots overlapping the `plotcp_current_inplay` months); empty (no
  query) while the setting is empty. NOT `global_start` – theme/template cache
  is not loaded there yet. Usable in templates AND in forum/category
  descriptions via a two-phase swap: `build_forumbits_forum` replaces the
  literal token `{$plotcp_current_plots}` in a description with a sanitiser-proof
  HTML-comment sentinel (`<!--PLOTCP_CURRENT_PLOTS-->`, before MyBB's `&` fixup
  in functions_forumlist), and `pre_output_page` swaps that sentinel for the
  built value once the page is assembled. Both handlers are strpos-guarded.
- `plotcp.php` (MyBB root) – frontend entry, action dispatch, all POST handlers
- `admin/modules/tools/plotcp.php` – ACP module (loads `$lang->load('plotcp')`
  itself; tabs via `output_nav_tabs` with description keys)

## Tables

`plotcp_categories`, `plotcp_groups` (`approved` 0 = pending proposal),
`plotcp_plots` (`visible` 0 = preview, 1 = published – no archive flag; date
columns + `start_key`/`end_key` = y*10000+m*100+d computed in PHP, index
`(end_key, start_key)`), `plotcp_plot_groups` (UNIQUE pid,gid),
`plotcp_group_members` (UNIQUE gid,uid), `plotcp_plot_threads` (UNIQUE tid –
one plot per thread).

## Alert rules (do not change lightly)

- `plotcp_plot_published`: on publish to DISTINCT members of linked groups;
  when groups are added to a published plot only `members(added) −
  members(kept)` – no double alerts.
- `plotcp_new_group`: on approval to all main accounts (`as_uid = 0` if the
  column exists, else all active users; banned/awaiting excluded).
- `plotcp_submission_team`: to team group members; `kind` (plot|group) picks
  the language string in the formatter.
- Extra details raw in DB, escaping ONLY in the formatter. Calendar titles
  raw too (rpgcalendar escapes on render).

## Description formatting

Plot descriptions are rendered with `postParser` using `allow_html = 1` AND
`allow_mycode = 1` (`Plotcp_Frontend::parseDescription`). This is a deliberate
decision by the forum owner (raw HTML allowed, incl. user submissions). If the
submission trust model ever tightens, gate HTML per author here. Everything
else (titles, locations, group names, contacts, alert values) stays escaped.

## Calendar conventions

Identical to rpgcalendar: proleptic Gregorian, astronomical year numbering
(year 0 exists, negative years allowed), NO mktime/DateTime anywhere. End
date empty ⇒ end = start. Validation rejects (no silent clamping/swapping):
incomplete start, partial end, invalid calendar dates, end < start. The inplay
date is REQUIRED (both forms always call `validateDates(true, …)`) because the
browse navigation groups plots by year; `has_date` therefore is 1 for every
new plot. The column stays for the calendar provider filter and legacy rows.

## What NOT to change

- Never patch core files; the only core-template touch is the reversible
  showthread injection via the installer.
- `start_key`/`end_key` are computed in PHP, never by the DB.
- Providers deliver to rpgcalendar raw and over-delivering is fine; malformed
  entries are discarded silently by the consumer – keep delivering clean data
  anyway (a dropped plot is invisible, not an error).
- Do not remove the `commit()` call in `Plotcp_Alerts::send()`.
- The `{$plotcp_current_plots}` forum-description injection MUST stay two-phase
  (token→sentinel in `build_forumbits_forum`, sentinel→value in
  `pre_output_page`). Injecting the rendered HTML directly into a description
  runs it through the forumlist `&` sanitiser, which double-encodes every named
  entity (`&amp;`, `&ndash;`, links) – the sentinel (no `&`) passes through
  untouched, and the real value is injected after the sanitiser.

## Testing

No local MyBB. Deploy via `tar cf - plotcp.php inc admin tests | ssh <host>
"cd <testforum>/httpdocs && tar xf -"`, lint with `php -l` over SSH.
`tests/cli-plugin.php install|uninstall|activate|status` (lifecycle incl.
cleanup proof and injection idempotency), `tests/cli-selftest.php` (calendar
math, date validation, group diff, alert dedupe – 32 checks). tar pipe and
ssh-heredoc cannot be combined (both want stdin) – deploy first, then run.
`tests/` is not part of a release zip. ACP click paths are tested manually.

**NEVER run `cli-plugin.php uninstall` on the shared test forum** – it drops
all six tables and wipes the user's real plots/categories/groups/settings.
The test forum holds live configuration. For lifecycle/cleanup proofs use a
throwaway install only. On the shared forum use `activate` (idempotent) at
most; use `SELFTEST`-prefixed fixtures and delete them individually.
