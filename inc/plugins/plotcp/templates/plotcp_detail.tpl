<table border="0" cellspacing="{$theme['borderwidth']}" cellpadding="{$theme['tablespace']}" class="tborder">
<tr>
<td class="thead" colspan="2"><strong>{$plot_title}</strong> {$plot_badges}</td>
</tr>
<tr>
<td class="trow1" width="22%"><strong>{$lang->plotcp_view_category}</strong></td>
<td class="trow1">{$category_name}</td>
</tr>
<tr>
<td class="trow2"><strong>{$lang->plotcp_view_groups}</strong></td>
<td class="trow2">{$plot_groups}</td>
</tr>
<tr>
<td class="trow1"><strong>{$lang->plotcp_view_location}</strong></td>
<td class="trow1">{$plot_location}</td>
</tr>
<tr>
<td class="trow2"><strong>{$lang->plotcp_view_contacts}</strong></td>
<td class="trow2">{$plot_contacts}</td>
</tr>
<tr>
<td class="trow1"><strong>{$lang->plotcp_view_date}</strong></td>
<td class="trow1">{$plot_date}</td>
</tr>
<tr>
<td class="trow2 plotcp_description" colspan="2">{$plot_description}</td>
</tr>
</table>
{$teambar}
<br />
<table border="0" cellspacing="{$theme['borderwidth']}" cellpadding="{$theme['tablespace']}" class="tborder">
<tr>
<td class="thead" colspan="2"><strong>{$lang->plotcp_view_scenes}</strong></td>
</tr>
{$scene_rows}
</table>
