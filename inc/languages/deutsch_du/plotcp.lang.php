<?php
/**
 * plotcp – Frontend-Sprachdatei (Deutsch, du-Form)
 */

$l['plotcp'] = 'Plots';
$l['plotcp_nav'] = 'Plot Control Panel';

// Listenansicht
$l['plotcp_mode_active'] = 'Aktive Plots';
$l['plotcp_mode_archive'] = 'Archiv';
$l['plotcp_btn_submit_plot'] = 'Plot einreichen';
$l['plotcp_btn_groups'] = 'Plottinggruppen';
$l['plotcp_no_plots'] = 'In dieser Ansicht gibt es noch keine Plots.';
$l['plotcp_badge_preview'] = 'Vorschau';
$l['plotcp_badge_archived'] = 'Archiviert';
$l['plotcp_col_plot'] = 'Plot';
$l['plotcp_col_groups'] = 'Plottinggruppen';
$l['plotcp_col_date'] = 'Datum';
$l['plotcp_col_location'] = 'Ort';

// Detailansicht
$l['plotcp_view_category'] = 'Kategorie';
$l['plotcp_view_groups'] = 'Plottinggruppen';
$l['plotcp_view_location'] = 'Ort';
$l['plotcp_view_contacts'] = 'Ansprechpartner';
$l['plotcp_view_date'] = 'Datum';
$l['plotcp_view_scenes'] = 'Verknüpfte Szenen';
$l['plotcp_view_no_scenes'] = 'Mit diesem Plot wurden noch keine Szenen verknüpft.';
$l['plotcp_view_no_groups'] = 'Keine Plottinggruppen verknüpft.';
$l['plotcp_btn_publish'] = 'Plot veröffentlichen';
$l['plotcp_btn_archive'] = 'Ins Archiv verschieben';
$l['plotcp_btn_unarchive'] = 'Aus dem Archiv holen';
$l['plotcp_submitted_by'] = 'Eingereicht von';

// Gruppen-Seite
$l['plotcp_groups_title'] = 'Plottinggruppen';
$l['plotcp_groups_members'] = 'Mitglieder';
$l['plotcp_btn_join'] = 'Beitreten';
$l['plotcp_btn_leave'] = 'Verlassen';
$l['plotcp_group_propose'] = 'Neue Plottinggruppe vorschlagen';
$l['plotcp_group_name'] = 'Gruppenname';
$l['plotcp_group_description'] = 'Beschreibung';
$l['plotcp_btn_propose'] = 'Gruppe vorschlagen';
$l['plotcp_group_pending_note'] = 'Wartet auf Freigabe durch das Team';
$l['plotcp_no_groups'] = 'Es gibt noch keine Plottinggruppen.';

// Einreichen-Formular
$l['plotcp_submit_title'] = 'Plot einreichen';
$l['plotcp_submit_note'] = 'Dein Plot wird zunächst als Vorschau gespeichert und ist für alle sichtbar, sobald das Team ihn veröffentlicht.';
$l['plotcp_field_title'] = 'Plotname';
$l['plotcp_field_category'] = 'Kategorie';
$l['plotcp_field_groups'] = 'Plottinggruppen';
$l['plotcp_field_location'] = 'Ort';
$l['plotcp_field_contacts'] = 'Ansprechpartner';
$l['plotcp_field_contacts_desc'] = 'Benutzernamen, durch Kommas getrennt.';
$l['plotcp_field_description'] = 'Beschreibung';
$l['plotcp_field_date'] = 'Inplay-Datum';
$l['plotcp_field_date_desc'] = 'Pflichtfeld – bestimmt die Einsortierung nach Jahr. Lass das Enddatum leer für eintägige Plots. Negative Jahre sind erlaubt (Jahr 0 existiert).';
$l['plotcp_field_start'] = 'Beginn (Tag / Monat / Jahr)';
$l['plotcp_field_end'] = 'Ende (Tag / Monat / Jahr)';
$l['plotcp_btn_submit'] = 'Plot einreichen';

// Thread-Erstellen-Formular (Szenen-Erstellung)
$l['plotcp_newthread_label'] = 'Plot';
$l['plotcp_newthread_desc'] = 'Optional: diese Szene mit einem Plot verknüpfen.';

// Szenen-Box (showthread)
$l['plotcp_scene_box_title'] = 'Plot';
$l['plotcp_scene_linked'] = 'Diese Szene gehört zum Plot:';
$l['plotcp_scene_link_label'] = 'Diese Szene mit einem Plot verknüpfen:';
$l['plotcp_scene_select_default'] = 'Plot auswählen...';
$l['plotcp_btn_link'] = 'Verknüpfen';
$l['plotcp_btn_unlink'] = 'Lösen';

// Erfolgsmeldungen (Redirects)
$l['plotcp_published'] = 'Der Plot wurde veröffentlicht. Die Gruppen-Mitglieder werden benachrichtigt.';
$l['plotcp_archived'] = 'Der Plot wurde ins Archiv verschoben.';
$l['plotcp_unarchived'] = 'Der Plot wurde aus dem Archiv geholt.';
$l['plotcp_joined'] = 'Du bist der Plottinggruppe beigetreten.';
$l['plotcp_left'] = 'Du hast die Plottinggruppe verlassen.';
$l['plotcp_plot_submitted'] = 'Danke! Dein Plot wurde eingereicht und wartet jetzt als Vorschau.';
$l['plotcp_group_submitted'] = 'Danke! Dein Gruppenvorschlag wurde eingereicht und wartet auf die Freigabe durch das Team.';
$l['plotcp_thread_linked'] = 'Die Szene wurde mit dem Plot verknüpft.';
$l['plotcp_thread_unlinked'] = 'Die Verknüpfung der Szene wurde gelöst.';

// Fehler
$l['plotcp_error_invalid_plot'] = 'Den angeforderten Plot gibt es nicht.';
$l['plotcp_error_invalid_group'] = 'Die angeforderte Plottinggruppe gibt es nicht.';
$l['plotcp_error_invalid_category'] = 'Bitte wähle eine gültige Kategorie.';
$l['plotcp_error_title_missing'] = 'Bitte gib einen Plotnamen ein.';
$l['plotcp_error_group_name_missing'] = 'Bitte gib einen Gruppennamen ein.';
$l['plotcp_error_group_name_exists'] = 'Eine Plottinggruppe mit diesem Namen existiert bereits.';
$l['plotcp_error_unknown_contacts'] = 'Unbekannte Benutzernamen im Feld Ansprechpartner: {1}';
$l['plotcp_error_submissions_disabled'] = 'Plot-Einreichungen sind derzeit deaktiviert.';
$l['plotcp_error_group_submissions_disabled'] = 'Gruppenvorschläge sind derzeit deaktiviert.';
$l['plotcp_error_already_linked'] = 'Dieser Thread ist bereits mit einem Plot verknüpft.';
$l['plotcp_error_not_scene_forum'] = 'Szenen in diesem Forum können nicht mit Plots verknüpft werden.';
$l['plotcp_error_already_published'] = 'Dieser Plot ist bereits veröffentlicht.';

// Datums-Validierungsfehler (Keys identisch im ACP-Modul)
$l['plotcp_error_start_incomplete'] = 'Bitte fülle das Startdatum vollständig aus (Tag, Monat und Jahr).';
$l['plotcp_error_start_invalid'] = 'Das Startdatum ist kein gültiges Kalenderdatum.';
$l['plotcp_error_end_partial'] = 'Bitte fülle das Enddatum vollständig aus oder lass es komplett leer.';
$l['plotcp_error_end_invalid'] = 'Das Enddatum ist kein gültiges Kalenderdatum.';
$l['plotcp_error_end_before_start'] = 'Das Enddatum darf nicht vor dem Startdatum liegen.';
