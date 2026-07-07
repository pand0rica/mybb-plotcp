<?php
/**
 * plotcp – MyAlerts-Sprachdatei (Deutsch, du-Form)
 *
 * myalerts_setting_* beschriftet die Abo-Checkboxen im UCP,
 * myalerts_plotcp_* sind die Alert-Textvorlagen des Formatters
 * (Platzhalter werden dort escaped).
 */

// UCP-Benachrichtigungseinstellungen
$l['myalerts_setting_plotcp_plot_published'] = 'Ein Plot für eine meiner Plottinggruppen wird veröffentlicht';
$l['myalerts_setting_plotcp_new_group'] = 'Eine neue Plottinggruppe wird angelegt';
$l['myalerts_setting_plotcp_submission_team'] = 'Ein User reicht einen neuen Plot oder eine neue Plottinggruppe ein (Team)';

// Alert-Texte
$l['myalerts_plotcp_plot_published'] = 'Für eine deiner Plottinggruppen wurde ein neuer Plot veröffentlicht: <a href="{link}">{plot_title}</a>';
$l['myalerts_plotcp_new_group'] = 'Es gibt eine neue Plottinggruppe: <a href="{link}">{group_name}</a>';
$l['myalerts_plotcp_submission_team_plot'] = '{from_user} hat einen neuen Plot eingereicht: <a href="{link}">{title}</a>';
$l['myalerts_plotcp_submission_team_group'] = '{from_user} hat eine neue Plottinggruppe vorgeschlagen: <a href="{link}">{title}</a>';
