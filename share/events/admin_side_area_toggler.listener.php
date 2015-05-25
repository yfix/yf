<?php

MAIN_TYPE_ADMIN && events()->listen('block.prepend[center_area]', function() {
	if (!is_common_page()) {
		return ;
	}
	$icons = array(
		'open'		=> 'icon icon-chevron-right fa fa-chevron-right',
		'closed'	=> 'icon icon-chevron-left fa fa-chevron-left',
	);
	$id = 'yf_side_panel_toggler';
	$cookie_name = 'yf_side_panel_hidden';
	$is_hidden = (bool)$_COOKIE[$cookie_name];

	$jquery = '
		var icons = '.json_encode($icons).'
			, id = "'.$id.'"
			, cookie_name = "'.$cookie_name.'"
			, is_hidden = '.(int)$is_hidden.'
			, center_area = $(".center_area")
			, side_area = $(".left_area")
			, toggle_btn = $("#'.$id.'")
			, toggle_icon = toggle_btn.find("i")
		;
		function side_area_close() {
			side_area.hide("fast");
			center_area.addClass("center_area_wide").removeClass("center_area_narrow");
			toggle_icon.removeClass().addClass(icons["closed"]);
			is_hidden = true;
		}
		function side_area_open() {
			center_area.removeClass("center_area_wide").addClass("center_area_narrow");
			side_area.show("fast");
			toggle_icon.removeClass().addClass(icons["open"]);
			is_hidden = false;
		}
		is_hidden && side_area_close(); // init
		toggle_btn.on("click", function() {
			if (is_hidden) {
				side_area_open()
			} else {
				side_area_close()
			}
			try {
				$.cookie(cookie_name, is_hidden ? 1 : 0, {path: "/"});
			} catch(e) { }
		})
	';

	$css = '
		.center_area_wide { margin-left:1%; width:98%; }
		.center_area_narrow { margin-left: 25% !important; width: 74% !important; }
	';
	if ($is_hidden) {
		$css .= '
			.center_area { margin-left:1%; width:98%; }
			.left_area { display: none; }
		';
	}

	jquery($jquery);
	js('jquery-cookie');
	css($css);

	$body .= '<a class="btn btn-default btn-small" id="'.$id.'" style="position:fixed; top:45px; left:5px;">
		<i class="'.($is_hidden ? $icons['closed'] : $icons['open']).'"></i></a>'
		.($is_hidden ? '<style type="text/css">.left_area {display:none;}</style>' : '');
	return $body;
});
