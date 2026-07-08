<html>
<head>
<title>{$mybb->settings['bbname']} - {$lang->plotcp}</title>
{$headerinclude}
</head>
<body>
{$header}
<div class="plotcp_toolbar" style="margin-bottom: 10px;">
	<div class="float_right">{$toolbar_buttons}</div>
	{$mode_switch}
</div>
<br class="clear" />
{$category_tabs}
{$category_sections}
<script type="text/javascript">
(function() {
	var tabs = document.getElementsByClassName('plotcp_tab');
	var secs = document.getElementsByClassName('plotcp_cat_section');
	if (!tabs.length || !secs.length) {
		return;
	}
	function activate(id) {
		var i;
		for (i = 0; i < secs.length; i++) {
			secs[i].style.display = (secs[i].id === id) ? '' : 'none';
		}
		for (i = 0; i < tabs.length; i++) {
			tabs[i].className = (tabs[i].getAttribute('data-target') === id) ? 'plotcp_tab active' : 'plotcp_tab';
		}
	}
	tabs[0].parentNode.style.display = 'block';
	for (var i = 0; i < tabs.length; i++) {
		tabs[i].onclick = function(e) {
			if (e && e.preventDefault) { e.preventDefault(); }
			activate(this.getAttribute('data-target'));
			return false;
		};
	}
	activate(tabs[0].getAttribute('data-target'));
})();
</script>
{$footer}
</body>
</html>
