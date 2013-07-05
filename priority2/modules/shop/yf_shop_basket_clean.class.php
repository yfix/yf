<?php
class yf_shop_basket_clean{

	/**
	* Clean basket contents
	*/
	function basket_clean() {
		$add_sql = "url='"._es($_GET['id']);
		$sql = "SELECT * FROM ".db('shop_products')." WHERE active='1' AND ".$add_sql."'";
		$product_info = db()->query_fetch($sql);
		$_GET["id"] = $product_info["id"];
		if ($_GET["id"] && isset($basket[$_GET["id"]])) {
			module('shop')->_basket_api()->del($_GET["id"]);
		}
		if (!$_GET["id"] && isset($basket)) {
			module('shop')->_basket_api()->clean();
		}
		return js_redirect($_SERVER["HTTP_REFERER"], false); 
	}
	
}