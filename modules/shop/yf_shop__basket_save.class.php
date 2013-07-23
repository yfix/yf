<?php
class yf_shop__basket_save{
	
	/**
	* Save basket
	*/
	function _basket_save() {
		if (!empty($_POST["quantity"]) && !module('shop')->_basket_is_processed) {
			module('shop')->_basket_api()->clean();
			$products_ids = array();
			foreach ((array)$_POST["quantity"] as $_product_id => $_quantity) {
				$_product_id	= intval($_product_id);
				$_quantity		= intval($_quantity);
				if ($_product_id && $_quantity) {
					module('shop')->_basket_api()->set($_product_id, array(
						"product_id"=> $_product_id,
						"quantity"	=> $_quantity,
						"atts"		=> $_POST["atts"][$_product_id],
					));
				}
			}
			// Prevent double processing
			module('shop')->_basket_is_processed = true;
		}
	}
	
}