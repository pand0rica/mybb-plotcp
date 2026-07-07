<?php
/**
 * plotcp – MyAlerts language file (English)
 *
 * myalerts_setting_* keys label the UCP alert subscription checkboxes,
 * myalerts_plotcp_* keys are the alert message templates used by the
 * formatter (placeholders are escaped there).
 */

// UCP alert settings (Settings -> Notifications)
$l['myalerts_setting_plotcp_plot_published'] = 'A plot is published for one of my plotting groups';
$l['myalerts_setting_plotcp_new_group'] = 'A new plotting group is created';
$l['myalerts_setting_plotcp_submission_team'] = 'A user submits a new plot or plotting group (team)';

// Alert messages
$l['myalerts_plotcp_plot_published'] = 'A new plot was published for one of your plotting groups: <a href="{link}">{plot_title}</a>';
$l['myalerts_plotcp_new_group'] = 'A new plotting group is available: <a href="{link}">{group_name}</a>';
$l['myalerts_plotcp_submission_team_plot'] = '{from_user} submitted a new plot: <a href="{link}">{title}</a>';
$l['myalerts_plotcp_submission_team_group'] = '{from_user} proposed a new plotting group: <a href="{link}">{title}</a>';
