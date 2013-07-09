<?php
class yf_manage_shop_save_filter{

	function save_filter ($silent = false) {
/*
		if (is_array(module("manage_shop")->_fields_in_filter)) {
			foreach ((array)module("manage_shop")->_fields_in_filter as $name){
				$_SESSION[module("manage_shop")->_filter_name][$name] = $_POST[$name];
			}
		}
		if (!$silent) {
			js_redirect($_SERVER["HTTP_REFERER"]);
		}
*/
	}
	
}