# Changelog

All notable changes to this project will be documented in this file.
The format is based on [Keep a Changelog](https://keepachangelog.com/).

## [1.0.0] – 2026-07-07

### New

- Plot categories managed in the ACP; frontend plot list grouped by category
  with an active/archive switch.
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
- Scene linking: thread starter and team can link threads in configured
  inplay/archive forums to a plot via a box below the thread; plot pages list
  linked scenes. Links are cleaned up when threads are deleted.
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
- Plot descriptions are parsed with MyCode only (`allow_html = 0`).
- Preview plots are hidden everywhere for unauthorized users: list, detail
  page, scene box, scene link dropdown and calendar provider.
