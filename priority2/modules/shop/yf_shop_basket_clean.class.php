<?php
class yf_shop_basket_clean{

	/**
	* Clean basket contents
	*/
	function basket_clean() {
		$basket = &$_SESSION["SHOP_basket"];
		
		// $_GET["id"] = intval($_GET["id"]);
		$add_sql = "url='"._es($_GET['id']);
		$sql = "SELECT * FROM ".db('shop_products')." WHERE active='1' AND ".$add_sql."'";
		$product_info = db()->query_fetch($sql);
		$_GET["id"] = $product_info["id"];
		// Delete one product from basket
		if ($_GET["id"] && isset($basket[$_GET["id"]])) {
			$basket[$_GET["id"]] = null;
		}
		// Clean all itms from basket
		if (!$_GET["id"] && isset($basket)) {
			$basket = null;
		}
		return js_redirect($_SERVER["HTTP_REFERER"], false); 
	}
	
}