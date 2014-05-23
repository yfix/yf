<?php
class yf_shop__get_select_attributes{

	function _get_select_attributes($atts = array()) {
		if (empty($atts)) {
			return array();
		}
		// Group by attribute name
		$_atts_by_name = array();
		foreach ((array)$atts as $_info) {
			$_atts_products_ids[$_info["name"]] = $_info["product_id"];
			$_price_text = " (".($_info["price"] < 0 ? "-" : "+"). module("shop")->_format_price(abs($_info["price"])).")";
			$_atts_by_name[$_info["name"]][$_info["value"]] = $_info["value"]. ($_info["price"] ? $_price_text : "");
		}
		$result = array();
		foreach ((array)$_atts_by_name as $_name => $_info) {
			$_product_id = $_atts_products_ids[$_name];
			$_box = "";
			$_box_name = "atts[".intval($_product_id)."][".$_name."]";
			if (count($_info) > 1) {
				$_box = common()->select_box($_box_name, $_info, $selected, false, 2, "", false);
			} else {
				$_box = current($_info)."\n<input type=\"hidden\" name=\"".$_box_name."\" value=\""._prepare_html(current($_info))."\" />";
			}
			$result[$_name] = array(
				"name"	=> _prepare_html($_name),
				"box"	=> $_box,
			);
		}
		return $result;
	}
	
}