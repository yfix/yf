<?php
class yf_shop_clean_cart{

	/**
	* Clean cart contents
	*/
	function clean_cart() {
		$cart = &$_SESSION["SHOP_CART"];
		
		// $_GET["id"] = intval($_GET["id"]);
		$add_sql = "`url`='"._es($_GET['id']);
		$sql = "SELECT * FROM `".db('shop_products')."` WHERE `active`='1' AND ".$add_sql."'";
		$product_info = db()->query_fetch($sql);
		$_GET["id"] = $product_info["id"];
		// Delete one product from cart
		if ($_GET["id"] && isset($cart[$_GET["id"]])) {
			$cart[$_GET["id"]] = null;
		}
		// Clean all itms from cart
		if (!$_GET["id"] && isset($cart)) {
			$cart = null;
		}
		return js_redirect($_SERVER["HTTP_REFERER"], false); 
	}
	
}