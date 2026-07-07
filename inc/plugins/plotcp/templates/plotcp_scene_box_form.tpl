<table border="0" cellspacing="{$theme['borderwidth']}" cellpadding="{$theme['tablespace']}" class="tborder plotcp_scene_box">
<tr>
<td class="thead"><strong>{$lang->plotcp_scene_box_title}</strong></td>
</tr>
<tr>
<td class="trow1 plotcp_scene_box_inner">
<form action="plotcp.php" method="post" class="plotcp_inline_form">
<input type="hidden" name="action" value="link_thread" />
<input type="hidden" name="tid" value="{$thread_id}" />
<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
{$lang->plotcp_scene_link_label}
<select name="pid">
<option value="0">{$lang->plotcp_scene_select_default}</option>
{$plot_options}
</select>
<input type="submit" class="button plotcp_button_small" value="{$lang->plotcp_btn_link}" />
</form>
</td>
</tr>
</table>
