<?php
class yf_manage_shop__box{

	function _box ($name = "", $selected = "") {
		if (empty($name) || empty(module("manage_shop")->_boxes[$name])) {
			return false;
		} else {
			return eval("return common()->".module("manage_shop")->_boxes[$name].";");
		}
	}
	
}