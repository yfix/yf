<?php
class yf_shop__save_cart_all{
	
	/**
	* Save cart
	*/
	function _save_cart_all() {
		$cart = &$_SESSION["SHOP_CART"];
		// Save cart contents
		if (!empty($_POST["quantity"]) && !module('shop')->_CART_PROCESSED) {
			$cart = array();
			// Save new data into session
			$products_ids = array();
			foreach ((array)$_POST["quantity"] as $_product_id => $_quantity) {
				$_product_id	= intval($_product_id);
				$_quantity		= intval($_quantity);
				if ($_product_id && $_quantity) {
					$cart[$_product_id] = array(
						"product_id"=> $_product_id,
						"quantity"	=> $_quantity,
						"atts"		=> $_POST["atts"][$_product_id],
					);
				}
			}
			// Prevent double processing
			module('shop')->_CART_PROCESSED = true;
		}
	}
	
}