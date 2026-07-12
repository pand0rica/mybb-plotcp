# Changelog

All notable changes to this project will be documented in this file.
The format is based on [Keep a Changelog](https://keepachangelog.com/).

## [1.1.0] – Unreleased

### Fixed

- Browse navigation: the active plot was marked with the theme class
  `trow_shaded`, which looks red/deleted in some themes. It now uses a subtle,
  theme-neutral marker (a left accent bar in the current text colour plus bold)
  on the normal row background.
- ACP plot list: the plot title's preview link was double-encoded
  (`&amp;amp;`), so clicking it opened `plotcp.php?action=view&amp;pid=…` and
  showed "the requested plot does not exist". The URL now uses a plain `&` that
  `htmlspecialchars_uni()` encodes exactly once.

### New

- ModCP group review: plotting group proposals can now be approved, edited or
  rejected directly in the Moderator CP (**ModCP → Plot Control Panel →
  Plotting groups**), without ACP access. Access is limited to team groups
  (setting **Team groups**) who are also moderators (`canmodcp`). Rejecting a
  proposal deletes it.
- Board index notice: while group proposals are waiting, team members see a
  hint on the board index linking straight to the ModCP review page.
- The team submission alert for a new group proposal now links to the ModCP
  review page instead of the frontend groups list.
- Group member list: on the frontend groups page a plotting group can be
  clicked to show its members (with join date). Team members can remove a
  member from a group directly there.
- Linked scenes on a plot page now show each scene's details from the
  [Logbook](https://github.com/pand0rica/logbook) plugin – participants,
  location, in-play date, daytime and scene-type badge, in the same layout as
  the logbook meta bar – instead of the forum name. Best effort: without the
  Logbook plugin the scene simply shows its thread link.
- Categories without plots are now shown as tabs on the frontend as well
  (previously only categories that already had plots appeared).
- Global template variable `{$plotcp_current_plots}`: published plots whose
  in-play range overlaps the configured **Current inplay months** (new
  setting). Renders from its own templates (`plotcp_current_plots`,
  `plotcp_current_plot_bit`) so it can be styled, and can be placed in any
  top-level template **or** inside a forum/category description (board index) by
  writing the literal token `{$plotcp_current_plots}` there.

### Changed

- Category descriptions are now long text: the `description` column changed
  from `VARCHAR(255)` to `TEXT` (migrated automatically on re-activation) and
  the ACP field is a textarea. If a category has a description it is shown on
  the frontend between the category tabs and the plot list.
- New setting **Current inplay months** (`plotcp_current_inplay`): one or more
  `year-month` values, comma separated (e.g. `1487-3, 1487-4`), driving
  `{$plotcp_current_plots}`. Empty = variable disabled.
- The stylesheet is now also attached to `index.php` (so
  `{$plotcp_current_plots}` is styled on the board index). The attachment is
  refreshed on re-activation for existing themes too.
- Activation now injects two more template variables (`{$plotcp_group_notice}`
  into `index`, `{$plotcp_modcp_nav}` into `modcp_nav`); both are removed on
  deactivation. Re-activate the plugin after updating so the new injections,
  the new setting, the schema migration and the new templates are applied.

## [1.0.0] – 2026-07-07

### New

- Plot categories managed in the ACP; frontend plot list grouped by category
  with an active/archive switch.
- Frontend uses a master-detail browse view: category tab bar on top, a plot
  navigation on the left grouped by inplay year (newest year and newest plot
  first) and the selected plot on the right. Category tabs and plot selection
  are server-side links (`?cat=…&pid=…`). Tabs and the active navigation entry
  reuse the theme classes `thead`/`tcat`/`trow_shaded`, so everything adapts to
  each MyBB theme with no own colours.
- Plots are organised only as preview vs published; there is no plot archive
  (no archive flag, badge, view or ACP filter). The ACP plot filter is
  All / Previews / Published. (The separate inplay/archive *forum* setting for
  the scene box is unrelated and stays.)
- The inplay date is a required field (it drives the year navigation); the end
  date stays optional (empty = single day).
- Plotting groups with self-service join/leave on the frontend groups page.
- Preview/publish workflow: plots start as previews visible to configured
  groups, the team and the submitter; publishing alerts all members of the
  linked plotting groups (deduplicated – one alert per user even across
  multiple groups).
- Adding a group to an already published plot alerts only the members of the
  new group.
- User submissions: plot submission form (starts as preview) and plotting
  group proposals with team approval; the team is alerted about submissions,
  approved groups are announced to all main accounts (EnhancedAccountSwitcher
  `as_uid = 0`, fallback: all active users).
- MyAlerts integration as optional dependency (three alert types, all
  user-disableable); the plugin works without MyAlerts.
- Scene linking: in configured inplay/archive forums a plot can be chosen
  directly in the new-thread form (dropdown below the post icons) and, in
  existing threads, via a box below the first post (thread starter and team).
  Plot pages list linked scenes; links are cleaned up when threads are deleted.
- Forum listing: threads linked to a plot show the plot name in square
  brackets before the thread title (`[Plot] Subject`). Preview-plot names are
  only shown to users who may see the preview.
- Preview access from the ACP: the plot title in the ACP list links to the
  rendered frontend plot page (and a "View preview" option opens it in a new
  tab), so the team can check how a plot will look before publishing.
- Plot descriptions support MyCode/BBCode **and** raw HTML (deliberate forum
  choice, applies to team and user submissions alike).
- Optional IC date range per plot: proleptic Gregorian calendar, astronomical
  year numbering (year 0 exists, negative years allowed), `start_key`/
  `end_key` sort keys computed in PHP.
- RPG Calendar provider: published dated plots are delivered to the
  `rpgcalendar_collect_events` hook (one indexed query per month render, no
  dependency in either direction).
- ACP tools module with tabs for plots (publish/archive/delete), plot form,
  categories and plotting groups including proposal approval.
- English and German (du) language files for frontend, ACP and MyAlerts.

### Security

- All state-changing actions are POST-only with `verify_post_check()`.
- Alert extra details are stored raw and escaped in the formatter
  (`htmlspecialchars_uni()`) to prevent stored XSS in the alert dropdown.
- Preview plots are hidden everywhere for unauthorized users: list, detail
  page, scene box, scene link dropdown, calendar provider and the forum-listing
  plot prefix.
- Plot titles, locations, contact links, group names and alert contents are
  escaped on output. NOTE: plot **descriptions** intentionally allow raw HTML
  (`allow_html = 1`, forum owner's decision) – descriptions are trusted content
  by design; keep this in mind if the trust model of submissions changes.
