<?php
/**
 * plotcp – ACP language file (English)
 */

$l['plotcp'] = 'Plot Control Panel';

// Tabs (description is mandatory – rendered below the tab bar)
$l['plotcp_tab_plots'] = 'Plots';
$l['plotcp_tab_add'] = 'Add plot';
$l['plotcp_tab_categories'] = 'Categories';
$l['plotcp_tab_groups'] = 'Plotting groups';
$l['plotcp_tab_desc_plots'] = 'All plots with status, publish, archive and delete controls.';
$l['plotcp_tab_desc_add'] = 'Create a new plot. It is saved as a preview until you publish it.';
$l['plotcp_tab_desc_categories'] = 'Manage the plot categories and their display order.';
$l['plotcp_tab_desc_groups'] = 'Manage plotting groups and approve user proposals.';

// Plot list
$l['plotcp_admin_col_title'] = 'Plot';
$l['plotcp_admin_col_category'] = 'Category';
$l['plotcp_admin_col_status'] = 'Status';
$l['plotcp_admin_col_groups'] = 'Groups';
$l['plotcp_admin_col_date'] = 'Date';
$l['plotcp_admin_col_controls'] = 'Controls';
$l['plotcp_admin_status_preview'] = 'Preview';
$l['plotcp_admin_status_published'] = 'Published';
$l['plotcp_admin_status_archived'] = 'Archived';
$l['plotcp_admin_no_plots'] = 'No plots yet.';
$l['plotcp_admin_filter_all'] = 'All';
$l['plotcp_admin_filter_preview'] = 'Previews';
$l['plotcp_admin_filter_active'] = 'Active';
$l['plotcp_admin_filter_archived'] = 'Archived';

// Controls
$l['plotcp_admin_edit'] = 'Edit';
$l['plotcp_admin_cancel'] = 'Cancel';
$l['plotcp_admin_options'] = 'Options';
$l['plotcp_admin_delete'] = 'Delete';
$l['plotcp_admin_publish'] = 'Publish';
$l['plotcp_admin_archive'] = 'Archive';
$l['plotcp_admin_unarchive'] = 'Restore';
$l['plotcp_admin_approve'] = 'Approve';
$l['plotcp_admin_plot_delete_confirm'] = 'Are you sure you want to delete this plot? Group links and scene links are removed as well.';
$l['plotcp_admin_category_delete_confirm'] = 'Are you sure you want to delete this category?';
$l['plotcp_admin_group_delete_confirm'] = 'Are you sure you want to delete this plotting group? Memberships and plot links are removed as well.';

// Plot form (field keys mirror the frontend so shared validation messages work)
$l['plotcp_admin_add_plot'] = 'Add plot';
$l['plotcp_admin_edit_plot'] = 'Edit plot';
$l['plotcp_admin_save'] = 'Save';
$l['plotcp_field_title'] = 'Plot name';
$l['plotcp_field_category'] = 'Category';
$l['plotcp_field_groups'] = 'Plotting groups';
$l['plotcp_field_groups_desc'] = 'Members of the selected groups are alerted when the plot is published.';
$l['plotcp_field_location'] = 'Location';
$l['plotcp_field_contacts'] = 'Contact persons';
$l['plotcp_field_contacts_desc'] = 'Usernames, separated by commas.';
$l['plotcp_field_description'] = 'Description';
$l['plotcp_field_date'] = 'Plot has a date';
$l['plotcp_field_date_desc'] = 'Optional IC date range (proleptic Gregorian, negative years allowed). Leave the end date empty for single-day plots.';
$l['plotcp_field_start'] = 'Start (day / month / year)';
$l['plotcp_field_end'] = 'End (day / month / year)';

// Categories
$l['plotcp_admin_add_category'] = 'Add category';
$l['plotcp_admin_edit_category'] = 'Edit category';
$l['plotcp_admin_col_name'] = 'Name';
$l['plotcp_admin_col_description'] = 'Description';
$l['plotcp_admin_col_order'] = 'Order';
$l['plotcp_admin_no_categories'] = 'No categories yet. Create at least one category before adding plots.';

// Groups
$l['plotcp_admin_add_group'] = 'Add plotting group';
$l['plotcp_admin_edit_group'] = 'Edit plotting group';
$l['plotcp_admin_col_members'] = 'Members';
$l['plotcp_admin_pending'] = 'Waiting for approval';
$l['plotcp_admin_approved_label'] = 'Approved';
$l['plotcp_admin_submitted_by'] = 'Proposed by';
$l['plotcp_admin_no_groups'] = 'No plotting groups yet.';

// Flash messages
$l['plotcp_admin_plot_saved'] = 'The plot has been saved.';
$l['plotcp_admin_plot_deleted'] = 'The plot has been deleted.';
$l['plotcp_admin_plot_published'] = 'The plot has been published. Group members are being notified.';
$l['plotcp_admin_plot_archived'] = 'The plot has been moved to the archive.';
$l['plotcp_admin_plot_unarchived'] = 'The plot has been restored from the archive.';
$l['plotcp_admin_category_saved'] = 'The category has been saved.';
$l['plotcp_admin_category_deleted'] = 'The category has been deleted.';
$l['plotcp_admin_category_has_plots'] = 'This category still contains plots and cannot be deleted. Move or delete the plots first.';
$l['plotcp_admin_group_saved'] = 'The plotting group has been saved.';
$l['plotcp_admin_group_approved'] = 'The plotting group has been approved. Users are being notified.';
$l['plotcp_admin_group_deleted'] = 'The plotting group has been deleted.';

// Validation errors (shared keys with the frontend language file)
$l['plotcp_error_invalid_plot'] = 'The requested plot does not exist.';
$l['plotcp_error_invalid_group'] = 'The requested plotting group does not exist.';
$l['plotcp_error_invalid_category'] = 'Please select a valid category.';
$l['plotcp_error_title_missing'] = 'Please enter a plot name.';
$l['plotcp_error_group_name_missing'] = 'Please enter a group name.';
$l['plotcp_error_category_name_missing'] = 'Please enter a category name.';
$l['plotcp_error_group_name_exists'] = 'A plotting group with this name already exists.';
$l['plotcp_error_unknown_contacts'] = 'Unknown username(s) in the contact persons field: {1}';
$l['plotcp_error_already_published'] = 'This plot is already published.';
$l['plotcp_error_start_incomplete'] = 'Please fill in the complete start date (day, month and year).';
$l['plotcp_error_start_invalid'] = 'The start date is not a valid calendar date.';
$l['plotcp_error_end_partial'] = 'Please fill in the complete end date or leave it entirely empty.';
$l['plotcp_error_end_invalid'] = 'The end date is not a valid calendar date.';
$l['plotcp_error_end_before_start'] = 'The end date must not be before the start date.';
