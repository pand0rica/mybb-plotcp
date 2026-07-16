<?php
/**
 * plotcp – Frontend-Sprachdatei (Deutsch, du-Form)
 */

$l['plotcp'] = 'Plots';
$l['plotcp_nav'] = 'Plot Control Panel';

// Listenansicht
$l['plotcp_btn_submit_plot'] = 'Plot einreichen';
$l['plotcp_btn_groups'] = 'Plottinggruppen';
$l['plotcp_no_plots'] = 'In dieser Ansicht gibt es noch keine Plots.';
$l['plotcp_current_plots_empty'] = 'Für den aktuellen Inplay-Zeitraum gibt es derzeit keine Plots.';
$l['plotcp_random_plot_empty'] = 'Aktuell gibt es keinen Plot zum Anzeigen.';
$l['plotcp_badge_preview'] = 'Vorschau';
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
$l['plotcp_btn_edit'] = 'Plot bearbeiten';
$l['plotcp_btn_save'] = 'Änderungen speichern';
$l['plotcp_btn_cancel'] = 'Abbrechen';
$l['plotcp_edit_title'] = 'Plot bearbeiten';
$l['plotcp_plot_updated'] = 'Der Plot wurde aktualisiert.';
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
$l['plotcp_group_members_title'] = 'Mitglieder von {1}';
$l['plotcp_group_member_col'] = 'Mitglied';
$l['plotcp_group_joined_col'] = 'Mitglied seit';
$l['plotcp_group_no_members'] = 'Diese Plottinggruppe hat noch keine Mitglieder.';
$l['plotcp_btn_remove_member'] = 'Entfernen';
$l['plotcp_confirm_remove_member'] = 'Dieses Mitglied wirklich aus der Plottinggruppe entfernen?';
$l['plotcp_member_removed'] = 'Das Mitglied wurde aus der Plottinggruppe entfernt.';

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

// Startseiten-Hinweis (Team, offene Gruppenvorschläge)
$l['plotcp_notice_title'] = 'Plot-Team';
$l['plotcp_notice_text'] = 'Es warten {1} Plottinggruppen-Vorschläge auf Freigabe.';
$l['plotcp_notice_link'] = 'Im ModCP prüfen';

// ModCP – Gruppen-Freigabe
$l['plotcp_modcp_title'] = 'Plottinggruppen freigeben';
$l['plotcp_modcp_nav_title'] = 'Plot Control Panel';
$l['plotcp_modcp_nav_groups'] = 'Plottinggruppen';
$l['plotcp_modcp_col_members'] = 'Mitglieder';
$l['plotcp_modcp_col_status'] = 'Status';
$l['plotcp_modcp_status_pending'] = 'Wartet auf Freigabe';
$l['plotcp_modcp_status_approved'] = 'Freigegeben';
$l['plotcp_modcp_submitted_by'] = 'Vorgeschlagen von';
$l['plotcp_modcp_no_groups'] = 'Es gibt noch keine Plottinggruppen.';
$l['plotcp_modcp_edit_group'] = 'Plottinggruppe bearbeiten';
$l['plotcp_modcp_field_order'] = 'Sortierung';
$l['plotcp_modcp_btn_approve'] = 'Freigeben';
$l['plotcp_modcp_btn_reject'] = 'Ablehnen';
$l['plotcp_modcp_btn_edit'] = 'Bearbeiten';
$l['plotcp_modcp_btn_delete'] = 'Löschen';
$l['plotcp_modcp_btn_save'] = 'Speichern';
$l['plotcp_modcp_btn_cancel'] = 'Abbrechen';
$l['plotcp_modcp_confirm_reject'] = 'Diesen Gruppenvorschlag wirklich ablehnen und löschen?';
$l['plotcp_modcp_confirm_delete'] = 'Diese Plottinggruppe wirklich löschen? Mitgliedschaften und Plot-Verknüpfungen gehen verloren.';
$l['plotcp_modcp_approved'] = 'Die Plottinggruppe wurde freigegeben. Die Mitglieder werden benachrichtigt.';
$l['plotcp_modcp_deleted'] = 'Die Plottinggruppe wurde gelöscht.';
$l['plotcp_modcp_saved'] = 'Die Änderungen wurden gespeichert.';
