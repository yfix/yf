<?php
class yf_shop__categories_show{

	function _categories_show() {
		$shop_cats = array();
		foreach ((array)module("shop")->_shop_cats_for_select as $_cat_id => $_cat_name) {
			if (!$_cat_name) {
				continue;
			}
			$shop_cats[_prepare_html($_cat_name)] = process_url("./?object=shop&action=show&id=".(module("shop")->_shop_cats_all[$_cat_id]['url']));
		}
		if (empty($shop_cats)) {
			$shop_cats = "";
		}
		return tpl()->parse("shop/cats_block", array(
			"shop_cats" => $shop_cats
		));
	}
	
}