<?php

return DEBUG_MODE ? function() {

$debug_override = conf('DEBUG_CONSOLE_OVERRIDE');
$debug_use_popup = conf('DEBUG_CONSOLE_POPUP');
$debug_class = 'yf_debug_console_asset';
return array(
	'versions' => array('master' => array(
		'css' => array(
			'content' => '
				#debug_console pre { color: #ccc; background: black; font-weight: bold; font-family: inherit; margin: 0; display: inline-block; width: auto; padding: 2px; border: 0; }
				#debug_console #debug_exec_time { float:left; display:block; padding-left: 20px; padding-right: 20px; }
			',
			'params' => array(
				'class' => $debug_class,
			)
		),
		'jquery' => array(
			'content' => '
				$("table.debug_item a[data-hidden-toggle]", "#debug_console").on("click", function(e){
					e.preventDefault();
					var _this = $(this)
					var _parent = _this.closest("tr")
					var _name = _this.data("hidden-toggle")
					var _hidden = _parent.find("[data-hidden-name=" + _name + "]")
					_hidden.toggle();
				})
				$(".btn-toggle", "#debug_console").on("click", function(e){
					e.preventDefault();
					var _this = $(this)
					var _toggle_what_id = _this.data("hidden-toggle")
					if (_toggle_what_id) {
						$("#" + _toggle_what_id).toggle();
					}
					console.log(_this, _toggle_what_id)
				})
				try {
					$("ul.nav-tabs li a", "#debug_console").on("click", function(){
						$.cookie("debug_tabs_active", $(this).attr("href").substring(1), {path: "/"}); // Remove # at the beginning
					})
				} catch (e) { console.log(e); }
			',
			'params' => array(
				'class' => $debug_class,
			)
		),
	)),
	'require' => array(
		'js' => 'jquery-cookie',
	),
	'add' => array(
		'js'	=> array(
			$debug_override ? 'yf_debug_console_override' : '',
			$debug_use_popup ? 'yf_debug_popup' : '',
		),
		'css'	=> array(
			$debug_override ? 'yf_debug_console_override' : '',
			$debug_use_popup ? 'yf_debug_popup' : '',
		),
	),
);

} : null;