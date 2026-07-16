<html>
<head>
<title>{$mybb->settings['bbname']} - {$lang->plotcp_edit_title}</title>
{$headerinclude}
</head>
<body>
{$header}
<form action="plotcp.php" method="post">
<input type="hidden" name="action" value="do_edit" />
<input type="hidden" name="pid" value="{$plot_id}" />
<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
<table border="0" cellspacing="{$theme['borderwidth']}" cellpadding="{$theme['tablespace']}" class="tborder">
<tr>
<td class="thead" colspan="2"><strong>{$lang->plotcp_edit_title}</strong></td>
</tr>
<tr>
<td class="trow2 plotcp_form_row" width="22%"><label for="plotcp_title">{$lang->plotcp_field_title}</label></td>
<td class="trow2"><input type="text" class="textbox" id="plotcp_title" name="title" maxlength="200" size="50" value="{$form_title}" /></td>
</tr>
<tr>
<td class="trow1 plotcp_form_row"><label for="plotcp_cid">{$lang->plotcp_field_category}</label></td>
<td class="trow1"><select id="plotcp_cid" name="cid">{$category_options}</select></td>
</tr>
<tr>
<td class="trow2 plotcp_form_row" valign="top"><label>{$lang->plotcp_field_groups}</label></td>
<td class="trow2"><div class="plotcp_group_checkboxes">{$group_checkboxes}</div></td>
</tr>
<tr>
<td class="trow1 plotcp_form_row"><label for="plotcp_location">{$lang->plotcp_field_location}</label></td>
<td class="trow1"><input type="text" class="textbox" id="plotcp_location" name="location" maxlength="200" size="50" value="{$form_location}" /></td>
</tr>
<tr>
<td class="trow2 plotcp_form_row"><label for="plotcp_contacts">{$lang->plotcp_field_contacts}</label><br /><span class="smalltext">{$lang->plotcp_field_contacts_desc}</span></td>
<td class="trow2"><input type="text" class="textbox" id="plotcp_contacts" name="contacts" size="50" value="{$form_contacts}" /></td>
</tr>
<tr>
<td class="trow1 plotcp_form_row" valign="top"><label for="plotcp_description">{$lang->plotcp_field_description}</label></td>
<td class="trow1"><textarea id="plotcp_description" name="description" rows="10" cols="70">{$form_description}</textarea></td>
</tr>
<tr>
<td class="trow2 plotcp_form_row"><label>{$lang->plotcp_field_date} <em>*</em></label><br /><span class="smalltext">{$lang->plotcp_field_date_desc}</span></td>
<td class="trow2">
<div class="plotcp_date_inputs">
{$lang->plotcp_field_start}:
<input type="text" class="textbox" name="start_day" value="{$form_start_day}" /> .
<input type="text" class="textbox" name="start_month" value="{$form_start_month}" /> .
<input type="text" class="textbox plotcp_date_year" name="start_year" value="{$form_start_year}" />
<br />
{$lang->plotcp_field_end}:
<input type="text" class="textbox" name="end_day" value="{$form_end_day}" /> .
<input type="text" class="textbox" name="end_month" value="{$form_end_month}" /> .
<input type="text" class="textbox plotcp_date_year" name="end_year" value="{$form_end_year}" />
</div>
</td>
</tr>
<tr>
<td class="tfoot" colspan="2" align="center"><input type="submit" class="button" value="{$lang->plotcp_btn_save}" /> <a href="plotcp.php?action=view&amp;pid={$plot_id}" class="button">{$lang->plotcp_btn_cancel}</a></td>
</tr>
</table>
</form>
{$footer}
</body>
</html>
