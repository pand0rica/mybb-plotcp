# Changelog

All notable changes to this project will be documented in this file.
The format is based on [Keep a Changelog](https://keepachangelog.com/).

## [1.0.0] – 2026-07-07

### New

- Plot categories managed in the ACP; frontend plot list grouped by category
  with an active/archive switch.
- Frontend uses a master-detail browse view: category tab bar on top, a plot
  navigation on the left grouped by inplay year (newest year and newest plot
  first) and the selected plot on the right. Category tabs and plot selection
  are server-side links (`?cat=…&pid=…`). Tabs and the active navigation entry
  reuse the theme classes `thead`/`tcat`/`trow_shaded`, so everything adapts to
  each MyBB theme with no own colours. There is no active/archive split on the
  frontend – archived plots are listed in their category like any other, marked
  only by the "archived" badge. (The ACP keeps its preview/active/archived
  filter for team management.)
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
