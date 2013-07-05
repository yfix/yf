<?php
class yf_shop__site_title{

	function _site_title($title) {
		$title = module("shop")->COMPANY_INFO["company_title"] ? module("shop")->COMPANY_INFO["company_title"] : module("shop")->COMPANY_INFO["company_name"];
		$subtitle = "";
		if (in_array($_GET["action"], array("show","products_show")) && $_GET["id"]) {
			$subtitle .= module("shop")->_shop_cats[$_GET["id"]];
		} elseif (in_array($_GET["action"], array("product_details")) /* && $_GET["id"] */) {
			$man = module("shop")->_manufacturer[module("shop")->_product_info["manufacturer_id"]]["name"] ;
			$subtitle .= module("shop")->_product_info["name"]." - ". $man;
		}
		if ($subtitle) {
			$title = $subtitle ." - ". $title;
		}
		return $title;
	}
	
}