<?php
class yf_shop__hidden_field{

	function _hidden_field($name = "", $value = "") {
		if (is_array($name)) {
			$result = "";
			$func_name = __FUNCTION__;
			foreach ((array)$name as $k => $v) {
				$result .= module("shop")->$func_name($k, $v);
			}
			return $result;
		}
		if (empty($name)) {
			return "";
		}
		return "<input type=\"hidden\" name=\""._prepare_html($name)."\" value=\""._prepare_html($value)."\" />\n";
	}
	
}