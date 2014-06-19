<?php

_class('core_events')->listen('block.prepend[center_area]', function() {
	if (MAIN_TYPE_ADMIN && main()->is_common_page()) {
		$icons = array(
			'open'		=> 'icon icon-chevron-right fa fa-chevron-right',
			'closed'	=> 'icon icon-chevron-left fa fa-chevron-left',
		);
		$id = 'yf_side_panel_toggler';
		$cookie_name = 'yf_side_panel_hidden';
		$is_hidden = (bool)$_COOKIE[$cookie_name];
		require_js('jquery-cookie');
		jquery('
			var icons = '.json_encode($icons).'
				, id = "'.$id.'"
				, cookie_name = "'.$cookie_name.'"
				, is_hidden = '.(int)$is_hidden.'
				, center_area = $(".center_area")
				, left_area = $(".left_area")
				, toggle_btn = $("#'.$id.'")
				, toggle_icon = toggle_btn.find("i")
			;
			toggle_btn.on("click", function(){
				if (is_hidden) {
					center_area.css({"margin-left":"25%", "margin-right":"1%", "width":"74%"});
					left_area.show("fast");
					toggle_icon.removeClass().addClass(icons["open"]);
					try { $.cookie(cookie_name, 0, {path: "/"}); } catch(e) { }
					is_hidden = false;
				} else {
					left_area.hide("fast");
					center_area.css({"margin-left":"1%", "margin-right":"1%", "width":"98%"});
					toggle_icon.removeClass().addClass(icons["closed"]);
					try { $.cookie(cookie_name, 1, {path: "/"}); } catch(e) { }
					is_hidden = true;
				}
			})
		');
		return '<a class="btn btn-default btn-small" id="'.$id.'" style="position:fixed; top:45px; left:5px;"><i class="'.($is_hidden ? $icons['closed'] : $icons['open']).'"></i></a>';
	}
});
