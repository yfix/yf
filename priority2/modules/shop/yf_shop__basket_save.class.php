<?php
class yf_shop__basket_save{
	
	/**
	* Save basket
	*/
	function _basket_save() {
		$basket = &$_SESSION["SHOP_basket"];
		// Save basket contents
		if (!empty($_POST["quantity"]) && !module('shop')->_basket_PROCESSED) {
			$basket = array();
			// Save new data into session
			$products_ids = array();
			foreach ((array)$_POST["quantity"] as $_product_id => $_quantity) {
				$_product_id	= intval($_product_id);
				$_quantity		= intval($_quantity);
				if ($_product_id && $_quantity) {
					$basket[$_product_id] = array(
						"product_id"=> $_product_id,
						"quantity"	=> $_quantity,
						"atts"		=> $_POST["atts"][$_product_id],
					);
				}
			}
			// Prevent double processing
			module('shop')->_basket_PROCESSED = true;
		}
	}
	
}