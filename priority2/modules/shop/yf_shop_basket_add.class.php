<?php
class yf_shop_basket_add{

	function basket_add() {
		$product = db()->query_fetch("SELECT id FROM ".db('shop_products')." WHERE active = '1' AND ".(is_numeric($_GET["id"]) ? "id=".intval($_GET["id"]) : "url='"._es($_GET['id'])."'"));
		if (!empty($product)) {
			$_GET['id'] = $product['id'];
		}
		$atts = module('shop')->_products_get_attributes($product["id"]);
		if ($_GET["id"]) {
			$_GET["id"] = intval($_GET["id"]);
			$_POST["quantity"][$_GET["id"]] = 1;
		}
		if (!empty($atts) && empty($_POST["atts"])) {
			module('shop')->_basket_is_processed = true;
			return js_redirect("./?object=shop&action=product_details&id=".$_GET["id"]);
		}
		if (!empty($_POST["quantity"]) && !module('shop')->_basket_is_processed) {
			foreach ((array)$_POST["quantity"] as $_product_id => $_quantity) {
				$_product_id	= intval($_product_id);
				$_old_quantity	= (int) module('shop')->_basket_api()->get($_product_id, 'quantity');
				$_quantity		= intval($_quantity) + intval($_old_quantity);
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
		return js_redirect("./?object=shop");
	}
	
}