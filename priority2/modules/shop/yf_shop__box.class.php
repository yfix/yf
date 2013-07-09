<?php
class yf_shop__box{

	function _box ($name = "", $selected = "") {
		if (empty($name) || empty(module("shop")->_boxes[$name])) {
			return false;
		} else {
			return eval("return common()->".module("shop")->_boxes[$name].";");
		}
	}
	
}