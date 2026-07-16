<?php
/**
 * plotcp – frontend language file (English)
 */

$l['plotcp'] = 'Plots';
$l['plotcp_nav'] = 'Plot Control Panel';

// List view
$l['plotcp_btn_submit_plot'] = 'Submit a plot';
$l['plotcp_btn_groups'] = 'Plotting groups';
$l['plotcp_no_plots'] = 'There are no plots in this view yet.';
$l['plotcp_current_plots_empty'] = 'There are no plots in the current inplay period right now.';
$l['plotcp_badge_preview'] = 'Preview';
$l['plotcp_col_plot'] = 'Plot';
$l['plotcp_col_groups'] = 'Plotting groups';
$l['plotcp_col_date'] = 'Date';
$l['plotcp_col_location'] = 'Location';

// Detail view
$l['plotcp_view_category'] = 'Category';
$l['plotcp_view_groups'] = 'Plotting groups';
$l['plotcp_view_location'] = 'Location';
$l['plotcp_view_contacts'] = 'Contact persons';
$l['plotcp_view_date'] = 'Date';
$l['plotcp_view_scenes'] = 'Linked scenes';
$l['plotcp_view_no_scenes'] = 'No scenes have been linked to this plot yet.';
$l['plotcp_view_no_groups'] = 'No plotting groups linked.';
$l['plotcp_btn_publish'] = 'Publish plot';
$l['plotcp_btn_edit'] = 'Edit plot';
$l['plotcp_btn_save'] = 'Save changes';
$l['plotcp_btn_cancel'] = 'Cancel';
$l['plotcp_edit_title'] = 'Edit plot';
$l['plotcp_plot_updated'] = 'The plot has been updated.';
$l['plotcp_submitted_by'] = 'Submitted by';

// Groups page
$l['plotcp_groups_title'] = 'Plotting groups';
$l['plotcp_groups_members'] = 'Members';
$l['plotcp_btn_join'] = 'Join';
$l['plotcp_btn_leave'] = 'Leave';
$l['plotcp_group_propose'] = 'Propose a new plotting group';
$l['plotcp_group_name'] = 'Group name';
$l['plotcp_group_description'] = 'Description';
$l['plotcp_btn_propose'] = 'Propose group';
$l['plotcp_group_pending_note'] = 'Waiting for team approval';
$l['plotcp_no_groups'] = 'There are no plotting groups yet.';
$l['plotcp_group_members_title'] = 'Members of {1}';
$l['plotcp_group_member_col'] = 'Member';
$l['plotcp_group_joined_col'] = 'Member since';
$l['plotcp_group_no_members'] = 'This plotting group has no members yet.';
$l['plotcp_btn_remove_member'] = 'Remove';
$l['plotcp_confirm_remove_member'] = 'Really remove this member from the plotting group?';
$l['plotcp_member_removed'] = 'The member has been removed from the plotting group.';

// Submission form
$l['plotcp_submit_title'] = 'Submit a plot';
$l['plotcp_submit_note'] = 'Your plot is saved as a preview first and becomes visible for everyone once the team publishes it.';
$l['plotcp_field_title'] = 'Plot name';
$l['plotcp_field_category'] = 'Category';
$l['plotcp_field_groups'] = 'Plotting groups';
$l['plotcp_field_location'] = 'Location';
$l['plotcp_field_contacts'] = 'Contact persons';
$l['plotcp_field_contacts_desc'] = 'Usernames, separated by commas.';
$l['plotcp_field_description'] = 'Description';
$l['plotcp_field_date'] = 'Inplay date';
$l['plotcp_field_date_desc'] = 'Required – determines the year grouping. Leave the end date empty for single-day plots. Negative years are allowed (year 0 exists).';
$l['plotcp_field_start'] = 'Start (day / month / year)';
$l['plotcp_field_end'] = 'End (day / month / year)';
$l['plotcp_btn_submit'] = 'Submit plot';

// New thread form (scene creation)
$l['plotcp_newthread_label'] = 'Plot';
$l['plotcp_newthread_desc'] = 'Optional: link this scene to a plot.';

// Scene box (showthread)
$l['plotcp_scene_box_title'] = 'Plot';
$l['plotcp_scene_linked'] = 'This scene belongs to the plot:';
$l['plotcp_scene_link_label'] = 'Link this scene to a plot:';
$l['plotcp_scene_select_default'] = 'Select a plot...';
$l['plotcp_btn_link'] = 'Link';
$l['plotcp_btn_unlink'] = 'Unlink';

// Success messages (redirects)
$l['plotcp_published'] = 'The plot has been published. Group members are being notified.';
$l['plotcp_joined'] = 'You joined the plotting group.';
$l['plotcp_left'] = 'You left the plotting group.';
$l['plotcp_plot_submitted'] = 'Thank you! Your plot has been submitted and is now waiting as a preview.';
$l['plotcp_group_submitted'] = 'Thank you! Your group proposal has been submitted and is waiting for team approval.';
$l['plotcp_thread_linked'] = 'The scene has been linked to the plot.';
$l['plotcp_thread_unlinked'] = 'The scene has been unlinked from the plot.';

// Errors
$l['plotcp_error_invalid_plot'] = 'The requested plot does not exist.';
$l['plotcp_error_invalid_group'] = 'The requested plotting group does not exist.';
$l['plotcp_error_invalid_category'] = 'Please select a valid category.';
$l['plotcp_error_title_missing'] = 'Please enter a plot name.';
$l['plotcp_error_group_name_missing'] = 'Please enter a group name.';
$l['plotcp_error_group_name_exists'] = 'A plotting group with this name already exists.';
$l['plotcp_error_unknown_contacts'] = 'Unknown username(s) in the contact persons field: {1}';
$l['plotcp_error_submissions_disabled'] = 'Plot submissions are currently disabled.';
$l['plotcp_error_group_submissions_disabled'] = 'Group proposals are currently disabled.';
$l['plotcp_error_already_linked'] = 'This thread is already linked to a plot.';
$l['plotcp_error_not_scene_forum'] = 'Scenes in this forum cannot be linked to plots.';
$l['plotcp_error_already_published'] = 'This plot is already published.';

// Date validation errors (keys shared with the ACP module)
$l['plotcp_error_start_incomplete'] = 'Please fill in the complete start date (day, month and year).';
$l['plotcp_error_start_invalid'] = 'The start date is not a valid calendar date.';
$l['plotcp_error_end_partial'] = 'Please fill in the complete end date or leave it entirely empty.';
$l['plotcp_error_end_invalid'] = 'The end date is not a valid calendar date.';
$l['plotcp_error_end_before_start'] = 'The end date must not be before the start date.';

// Board index notice (team, pending group proposals)
$l['plotcp_notice_title'] = 'Plotting Team';
$l['plotcp_notice_text'] = 'There are {1} plotting group proposal(s) waiting for approval.';
$l['plotcp_notice_link'] = 'Review in the ModCP';

// ModCP – group review
$l['plotcp_modcp_title'] = 'Approve plotting groups';
$l['plotcp_modcp_nav_title'] = 'Plot Control Panel';
$l['plotcp_modcp_nav_groups'] = 'Plotting groups';
$l['plotcp_modcp_col_members'] = 'Members';
$l['plotcp_modcp_col_status'] = 'Status';
$l['plotcp_modcp_status_pending'] = 'Awaiting approval';
$l['plotcp_modcp_status_approved'] = 'Approved';
$l['plotcp_modcp_submitted_by'] = 'Proposed by';
$l['plotcp_modcp_no_groups'] = 'There are no plotting groups yet.';
$l['plotcp_modcp_edit_group'] = 'Edit plotting group';
$l['plotcp_modcp_field_order'] = 'Display order';
$l['plotcp_modcp_btn_approve'] = 'Approve';
$l['plotcp_modcp_btn_reject'] = 'Reject';
$l['plotcp_modcp_btn_edit'] = 'Edit';
$l['plotcp_modcp_btn_delete'] = 'Delete';
$l['plotcp_modcp_btn_save'] = 'Save';
$l['plotcp_modcp_btn_cancel'] = 'Cancel';
$l['plotcp_modcp_confirm_reject'] = 'Really reject and delete this group proposal?';
$l['plotcp_modcp_confirm_delete'] = 'Really delete this plotting group? Memberships and plot links will be lost.';
$l['plotcp_modcp_approved'] = 'The plotting group has been approved. Its members are being notified.';
$l['plotcp_modcp_deleted'] = 'The plotting group has been deleted.';
$l['plotcp_modcp_saved'] = 'The changes have been saved.';
