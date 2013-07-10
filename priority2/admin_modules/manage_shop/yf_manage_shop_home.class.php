<?php
class yf_manage_shop_home{

	function home () {
		$items = module("manage_shop")->statistic();
		$replace = array(
			"items"				=> $items,
			"products_url"		=> "./?object=manage_shop&action=products",
			"manufacturer_url"	=> "./?object=manage_shop&action=manufacturers",
			"categories_url"	=> "./?object=category_editor&action=show_items&id=shop_cats",
			"attributes_url"	=> "./?object=manage_shop&action=attributes", 
			"orders_url"		=> "./?object=manage_shop&action=show_orders",
			"reports_url"		=> "./?object=manage_shop&action=show_reports&id=viewed",
			"settings_url"		=> "./?object=manage_shop&action=show_settings",
		);
		return tpl()->parse("manage_shop/home", $replace);
	}
	
}