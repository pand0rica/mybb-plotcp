<?php
/**
 * plotcp – ACP-Sprachdatei (Deutsch, du-Form)
 */

$l['plotcp'] = 'Plot Control Panel';

// Tabs (description ist Pflicht – wird unter der Tab-Leiste angezeigt)
$l['plotcp_tab_plots'] = 'Plots';
$l['plotcp_tab_add'] = 'Plot hinzufügen';
$l['plotcp_tab_categories'] = 'Kategorien';
$l['plotcp_tab_groups'] = 'Plottinggruppen';
$l['plotcp_tab_desc_plots'] = 'Alle Plots mit Status sowie Buttons zum Veröffentlichen und Löschen.';
$l['plotcp_tab_desc_add'] = 'Neuen Plot anlegen. Er wird als Vorschau gespeichert, bis du ihn veröffentlichst.';
$l['plotcp_tab_desc_categories'] = 'Plot-Kategorien und ihre Sortierung verwalten.';
$l['plotcp_tab_desc_groups'] = 'Plottinggruppen verwalten und User-Vorschläge freigeben.';

// Plot-Liste
$l['plotcp_admin_col_title'] = 'Plot';
$l['plotcp_admin_col_category'] = 'Kategorie';
$l['plotcp_admin_col_status'] = 'Status';
$l['plotcp_admin_col_groups'] = 'Gruppen';
$l['plotcp_admin_col_date'] = 'Datum';
$l['plotcp_admin_col_controls'] = 'Aktionen';
$l['plotcp_admin_status_preview'] = 'Vorschau';
$l['plotcp_admin_status_published'] = 'Veröffentlicht';
$l['plotcp_admin_no_plots'] = 'Noch keine Plots vorhanden.';
$l['plotcp_admin_filter_all'] = 'Alle';
$l['plotcp_admin_filter_preview'] = 'Vorschauen';
$l['plotcp_admin_filter_published'] = 'Veröffentlicht';

// Aktionen
$l['plotcp_admin_edit'] = 'Bearbeiten';
$l['plotcp_admin_preview'] = 'Vorschau ansehen';
$l['plotcp_admin_cancel'] = 'Abbrechen';
$l['plotcp_admin_options'] = 'Optionen';
$l['plotcp_admin_delete'] = 'Löschen';
$l['plotcp_admin_publish'] = 'Veröffentlichen';
$l['plotcp_admin_approve'] = 'Freigeben';
$l['plotcp_admin_plot_delete_confirm'] = 'Willst du diesen Plot wirklich löschen? Gruppen- und Szenen-Verknüpfungen werden ebenfalls entfernt.';
$l['plotcp_admin_category_delete_confirm'] = 'Willst du diese Kategorie wirklich löschen?';
$l['plotcp_admin_group_delete_confirm'] = 'Willst du diese Plottinggruppe wirklich löschen? Mitgliedschaften und Plot-Verknüpfungen werden ebenfalls entfernt.';

// Plot-Formular (Feld-Keys identisch zum Frontend, damit geteilte Validierungsmeldungen funktionieren)
$l['plotcp_admin_add_plot'] = 'Plot hinzufügen';
$l['plotcp_admin_edit_plot'] = 'Plot bearbeiten';
$l['plotcp_admin_save'] = 'Speichern';
$l['plotcp_field_title'] = 'Plotname';
$l['plotcp_field_category'] = 'Kategorie';
$l['plotcp_field_groups'] = 'Plottinggruppen';
$l['plotcp_field_groups_desc'] = 'Mitglieder der gewählten Gruppen werden beim Veröffentlichen benachrichtigt.';
$l['plotcp_field_location'] = 'Ort';
$l['plotcp_field_contacts'] = 'Ansprechpartner';
$l['plotcp_field_contacts_desc'] = 'Benutzernamen, durch Kommas getrennt.';
$l['plotcp_field_description'] = 'Beschreibung';
$l['plotcp_field_date'] = 'Inplay-Datum';
$l['plotcp_field_date_desc'] = 'Pflichtfeld – bestimmt die Einsortierung nach Jahr (proleptisch gregorianisch, negative Jahre erlaubt). Lass das Enddatum leer für eintägige Plots.';
$l['plotcp_field_start'] = 'Beginn (Tag / Monat / Jahr)';
$l['plotcp_field_end'] = 'Ende (Tag / Monat / Jahr)';

// Kategorien
$l['plotcp_admin_add_category'] = 'Kategorie hinzufügen';
$l['plotcp_admin_edit_category'] = 'Kategorie bearbeiten';
$l['plotcp_admin_category_desc_hint'] = 'Wird auf der Plot-Seite zwischen den Kategorie-Tabs und der Plot-Liste angezeigt. HTML und MyCode sind erlaubt. Darf lang sein.';
$l['plotcp_admin_col_id'] = 'ID';
$l['plotcp_admin_col_name'] = 'Name';
$l['plotcp_admin_col_description'] = 'Beschreibung';
$l['plotcp_admin_col_order'] = 'Sortierung';
$l['plotcp_admin_no_categories'] = 'Noch keine Kategorien vorhanden. Lege mindestens eine Kategorie an, bevor du Plots erstellst.';

// Gruppen
$l['plotcp_admin_add_group'] = 'Plottinggruppe hinzufügen';
$l['plotcp_admin_edit_group'] = 'Plottinggruppe bearbeiten';
$l['plotcp_admin_col_members'] = 'Mitglieder';
$l['plotcp_admin_pending'] = 'Wartet auf Freigabe';
$l['plotcp_admin_approved_label'] = 'Freigegeben';
$l['plotcp_admin_submitted_by'] = 'Vorgeschlagen von';
$l['plotcp_admin_no_groups'] = 'Noch keine Plottinggruppen vorhanden.';

// Flash-Meldungen
$l['plotcp_admin_plot_saved'] = 'Der Plot wurde gespeichert.';
$l['plotcp_admin_plot_deleted'] = 'Der Plot wurde gelöscht.';
$l['plotcp_admin_plot_published'] = 'Der Plot wurde veröffentlicht. Die Gruppen-Mitglieder werden benachrichtigt.';
$l['plotcp_admin_category_saved'] = 'Die Kategorie wurde gespeichert.';
$l['plotcp_admin_category_deleted'] = 'Die Kategorie wurde gelöscht.';
$l['plotcp_admin_category_has_plots'] = 'Diese Kategorie enthält noch Plots und kann nicht gelöscht werden. Verschiebe oder lösche zuerst die Plots.';
$l['plotcp_admin_group_saved'] = 'Die Plottinggruppe wurde gespeichert.';
$l['plotcp_admin_group_approved'] = 'Die Plottinggruppe wurde freigegeben. Die User werden benachrichtigt.';
$l['plotcp_admin_group_deleted'] = 'Die Plottinggruppe wurde gelöscht.';

// Validierungsfehler (Keys identisch zur Frontend-Sprachdatei)
$l['plotcp_error_invalid_plot'] = 'Den angeforderten Plot gibt es nicht.';
$l['plotcp_error_invalid_group'] = 'Die angeforderte Plottinggruppe gibt es nicht.';
$l['plotcp_error_invalid_category'] = 'Bitte wähle eine gültige Kategorie.';
$l['plotcp_error_title_missing'] = 'Bitte gib einen Plotnamen ein.';
$l['plotcp_error_group_name_missing'] = 'Bitte gib einen Gruppennamen ein.';
$l['plotcp_error_category_name_missing'] = 'Bitte gib einen Kategorienamen ein.';
$l['plotcp_error_group_name_exists'] = 'Eine Plottinggruppe mit diesem Namen existiert bereits.';
$l['plotcp_error_unknown_contacts'] = 'Unbekannte Benutzernamen im Feld Ansprechpartner: {1}';
$l['plotcp_error_already_published'] = 'Dieser Plot ist bereits veröffentlicht.';
$l['plotcp_error_start_incomplete'] = 'Bitte fülle das Startdatum vollständig aus (Tag, Monat und Jahr).';
$l['plotcp_error_start_invalid'] = 'Das Startdatum ist kein gültiges Kalenderdatum.';
$l['plotcp_error_end_partial'] = 'Bitte fülle das Enddatum vollständig aus oder lass es komplett leer.';
$l['plotcp_error_end_invalid'] = 'Das Enddatum ist kein gültiges Kalenderdatum.';
$l['plotcp_error_end_before_start'] = 'Das Enddatum darf nicht vor dem Startdatum liegen.';
