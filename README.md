# Plot Control Panel (plotcp)

A MyBB 1.8 plugin for RPG forums that manages plots in one central place: plot
categories, a preview/publish workflow with MyAlerts notifications, user
submissions, scene linking for inplay threads and automatic RPG Calendar
integration. Plots can be linked to **plotting groups** when the separate
[Plotting Groups](https://github.com/pand0rica/plottinggroups) plugin is
installed.

## Features

- **Plot categories** (e.g. Plots, User plots, Mini plots, Info, Rumours,
  Blackboard) managed in the ACP; on the frontend each category is a tab, with
  a plot navigation on the left (grouped by inplay year, newest first) and the
  selected plot shown on the right
- **Plotting groups (optional)**: with the separate
  [Plotting Groups](https://github.com/pand0rica/plottinggroups) plugin
  installed, every plot can be linked to any number of groups (badges on the
  plot page, checkboxes in the plot forms) and publishing alerts the members of
  the linked groups. Without that plugin, plotcp simply shows no group features.
- **Preview / publish workflow**: new plots start as previews that only
  configured groups (plus the team and the submitter) can see; publishing
  sends a MyAlerts notification to all members of the linked plotting groups
- **User submissions**: users can submit plots (they start as previews)
- **Scene linking**: in configured inplay/archive forums, the thread starter
  and the team can link a thread to a plot; the plot page lists all linked
  scenes, showing each scene's participants, location and in-play date from the
  [Logbook](https://github.com/pand0rica/logbook) plugin when it is installed
- **Current plots variable**: a global template variable `{$plotcp_current_plots}`
  lists the published plots of the current in-play months (a setting), usable in
  any template **and** inside forum/category descriptions
- **Random plot variable**: a global template variable `{$plotcp_random_plot}`
  spotlights one random published plot, configurable by category and by scope
  (all plots or only the current in-play months)
- **Optional IC date range** per plot with arbitrary years (negative years
  and year 0 included, proleptic Gregorian calendar) – dated plots appear
  automatically in the [RPG Calendar](https://github.com/pand0rica/rpgcalendar)
  month view via its provider hook

## Requirements

- MyBB 1.8.x
- PHP 7.4 or newer (tested up to PHP 8.1)
- **Optional:** [MyAlerts](https://community.mybb.com/mods.php?action=view&pid=avau1249) –
  without it the plugin works normally, just without notifications
- **Optional:** [Plotting Groups](https://github.com/pand0rica/plottinggroups) –
  required for the group features (linking plots to groups, group badges, publish
  alerts to group members); without it plotcp works without any group features
- **Optional:** RPG Calendar (rpgcalendar) ≥ 1.0.0 – dated plots then appear
  in the calendar; without it nothing happens
- **Optional:** EnhancedAccountSwitcher – "new group" alerts then go to main
  accounts only instead of all accounts
- **Optional:** [Logbook](https://github.com/pand0rica/logbook) – linked scenes
  on a plot page then show the scene's participants, location, in-play date and
  daytime; without it a linked scene just shows its thread link

No other dependencies.

## Installation

1. Upload all files to your MyBB root directory (the folder structure of the
   plugin matches the forum structure):
   - `plotcp.php` → forum root
   - `inc/plugins/plotcp.php` and `inc/plugins/plotcp/` → `inc/plugins/`
   - `admin/modules/tools/plotcp.php` → `admin/modules/tools/`
   - `inc/languages/...` → the matching language folders
2. Go to **ACP → Configuration → Plugins** and click **Activate** next to
   "Plot Control Panel".
3. Go to **ACP → Configuration → Settings → Plot Control Panel** and configure
   the plugin (see [Configuration](#configuration)).

## Configuration

| Setting | Meaning |
|---|---|
| **Preview visibility groups** | User groups that can see unpublished plots (previews). Team groups and the submitter of a plot always see it. |
| **Team groups** | Groups that may publish plots and manage scene links on any thread. They also receive plot-submission alerts. |
| **Inplay forums** | Forums in which the plot link box is shown below threads. |
| **Archive forums** | Additional (archived) forums with the plot link box. |
| **Allow plot submissions** | Users may submit new plots via the frontend form. |
| **Current inplay months** | One or more in-play months as `year-month`, comma separated (e.g. `1487-3, 1487-4`). Drives the `{$plotcp_current_plots}` variable. Empty = variable disabled. |
| **Random plot** | Drives the `{$plotcp_random_plot}` variable: `Disabled`, `All published plots`, or `Only plots in the current inplay months`. |
| **Random plot categories** | Checkboxes of your plot categories the random plot may come from. Select none for all categories. |

## Usage

### First steps after activation

1. **Create categories:** ACP → Tools & Maintenance → Plot Control Panel →
   tab **Categories** → enter name, description and order → **Save**. At
   least one category is required before plots can be created.
2. **Plotting groups (optional):** install the
   [Plotting Groups](https://github.com/pand0rica/plottinggroups) plugin to be
   able to link plots to groups. It manages all group creation/approval itself.
3. **Configure settings:** ACP → Configuration → Settings → Plot Control
   Panel → set team groups, preview groups and the inplay/archive forums.

### Managing plots (team)

- **Create:** ACP → Tools → Plot Control Panel → tab **Add plot**. Fill in
  name, category, plotting groups, location, contact persons (usernames,
  comma-separated), description (MyCode/BBCode **and** HTML supported) and the
  optional IC date range. The plot is saved as a **preview**.
- **Preview:** in the ACP plot list the plot title links to the rendered
  frontend page (opens in a new tab), and the options menu has a **View
  preview** entry – so you can check exactly how the plot will look, with all
  formatting, before publishing. Users who submit a plot are taken straight to
  its preview page after submitting.
- **Publish:** in the plot list open the options menu → **Publish** (or use
  the publish button on the plot's frontend page). All members of the linked
  plotting groups receive a MyAlerts notification.
- **Edit:** every plot can be fully edited – in the ACP (options menu →
  **Edit**) or on the frontend via the **Edit** button on the plot page. The
  team may edit any plot; a submitter may edit their own plot while it is still
  a preview. All fields are editable (title, category, groups, location,
  contacts, description, inplay dates).
- **Edit:** if you add another plotting group to an already published plot,
  only the members of the new group are notified – nobody gets a second alert.

![ACP plot list](docs/screenshots/acp-plots.png)

### Plotting groups

Group creation, joining/leaving, proposals and the team review now live in the
separate [Plotting Groups](https://github.com/pand0rica/plottinggroups) plugin.
When it is installed, plotcp lets you link plots to those groups (checkboxes in
the plot forms, badges on the plot page) and alerts the group members on publish.

### Submitting plots (users)

Frontend page `plotcp.php` → **Submit a plot**. The submitted plot starts as
a preview: the submitter and the preview groups can already read it, everyone
else sees it after the team publishes it. The team is notified about new
submissions.

![Submission form](docs/screenshots/submit.png)

### Linking scenes (inplay)

There are two ways to link a scene to a plot in the configured inplay/archive
forums:

- **When creating the thread:** the new-thread form shows a **Plot** dropdown
  (below the post icons). Pick a plot right away and it is linked as soon as
  the scene is posted.
- **In an existing thread:** the thread starter and the team see a **Plot**
  box below the first post. Select a plot and click **Link**, or **Unlink** to
  remove the connection.

A thread can belong to one plot at a time. Published plots are selectable for
everyone; the team can additionally pick preview plots.

Once a thread is linked, its plot appears in square brackets before the thread
title in the forum listing (`[Plot] Thread title`). Preview-plot names stay
hidden from users who may not see the preview.

![Scene box](docs/screenshots/scene-box.png)

### Alerts (MyAlerts)

Every user can control the plugin's notifications under
**User CP → Edit Alert Settings**:

- *A plot is published for one of my plotting groups*
- *A user submits a new plot* (relevant for the team)

(Group-related alerts – new group, proposals, group tags – belong to the
[Plotting Groups](https://github.com/pand0rica/plottinggroups) plugin.)

### RPG Calendar

No configuration needed. If the RPG Calendar plugin is installed, every
**published** plot with a date automatically appears in the month view as a
chip with the CSS class `rpgcalendar_event--plot` and as a row in the
important-events table. Previews are never delivered to the calendar.

### Current plots variable

The global template variable `{$plotcp_current_plots}` lists all published plots
whose in-play range overlaps the **Current inplay months** (set under ACP →
Configuration → Settings → Plot Control Panel, e.g. `1487-3, 1487-4`). When the
setting is empty the variable stays empty and no queries run.

If the months are configured but no plot currently overlaps them, the variable
shows a short notice (styled via `.plotcp_current_plots_empty`) instead of being
empty. It only stays fully empty when no *Current inplay months* are set at all.

You can use it in two ways:

1. **In a template:** place `{$plotcp_current_plots}` in any top-level template
   (e.g. `index`, `header`). It renders from the `plotcp_current_plots` /
   `plotcp_current_plot_bit` templates, so you can restyle it freely.
2. **In a forum or category description:** write the literal token
   `{$plotcp_current_plots}` into a forum's or category's description (ACP →
   Forums & Posts → Forums → edit → *Description*). On the board index the token
   is replaced with the plot list. (For categories, make sure your theme's
   `forumbit_depth1_cat` template actually outputs the description.)

### Random plot variable

The global template variable `{$plotcp_random_plot}` shows one random published
plot. Configure it under ACP → Configuration → Settings → Plot Control Panel:

- **Random plot** – `Disabled` (off), `All published plots`, or `Only plots in
  the current inplay months` (uses the same *Current inplay months* setting).
- **Random plot categories** – tick the categories the random plot may come
  from; leave all unticked for any category. The checkbox list is built
  automatically from your plot categories.

Place `{$plotcp_random_plot}` in a top-level template or a forum/category
description – same two ways as `{$plotcp_current_plots}` above. A different plot
is picked on each page load. If the variable is enabled but nothing matches
(e.g. scope *current inplay months* but no plot falls in the period), a short
notice is shown instead of nothing.

### Category descriptions

A category description may be long text (HTML/BBCode allowed). If a category has
a description, it is shown on the frontend between the category tabs and the
plot list.

### Tips

- **Date rules:** the inplay start date is required (it groups the plots by
  year in the navigation). Leave the end date empty for single-day plots.
  Negative years are fine (astronomical numbering, year 0 exists). February 29
  only exists in leap years – the form tells you if a date is invalid.
- **Deleting a category** is only possible while no plots use it.
- **Deleting a group** removes its memberships and plot links; the plots
  themselves stay.
- Members are collected at publish time: whoever leaves a group before the
  plot is published gets no alert.

## Uninstallation

1. ACP → Configuration → Plugins → **Deactivate** "Plot Control Panel"
   (removes templates, settings, stylesheet and the showthread template
   injection). MyAlerts alert types are kept, so existing alerts stay visible
   after re-activating.
2. Click **Uninstall** to drop the plugin's database tables (categories, plots,
   plot↔group links and plot↔thread links) and to remove the MyAlerts alert
   types together with their alerts. All plot data is deleted
   permanently. Plotting groups themselves belong to the Plotting Groups plugin.
3. Delete the uploaded files.

## Changelog

See [CHANGELOG.md](CHANGELOG.md).
