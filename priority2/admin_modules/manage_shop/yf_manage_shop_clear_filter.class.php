<?php
class yf_manage_shop_clear_filter{

	function clear_filter ($silent = false) {
/*
		if (is_array($_SESSION[module("manage_shop")->_filter_name])) {
			foreach ((array)$_SESSION[module("manage_shop")->_filter_name] as $name) {
				unset($_SESSION[module("manage_shop")->_filter_name]);
			}
		}
		if (!$silent) {
			js_redirect("./?object=manage_shop&action=products_manage"._add_get());
		}
*/
	}

}