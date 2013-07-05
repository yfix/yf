<?php
class yf_shop_basket_add{

	function basket_add() {
		$basket = &$_SESSION["SHOP_basket"];

		$A = db()->query_fetch("SELECT id FROM ".db('shop_products')." WHERE active = '1' AND ".(is_numeric($_GET["id"]) ? "id=".intval($_GET["id"]) : "url='"._es($_GET['id'])."'"));

		if (!empty($A)) {
			$_GET['id'] = $A['id'];
		}
		$atts = module('shop')->_get_products_attributes($A["id"]);
		// Save basket contents
		if ($_GET["id"]) {
			$_GET["id"] = intval($_GET["id"]);
			$_POST["quantity"][$_GET["id"]] = 1;
		}
		// Display 
		if (!empty($atts) && empty($_POST["atts"])) {
			module('shop')->_basket_PROCESSED = true;
			return js_redirect("./?object=shop&action=product_details&id=".$_GET["id"]);
		}
		// Do save
		if (!empty($_POST["quantity"]) && !module('shop')->_basket_PROCESSED) {
			// Save new data into session
			foreach ((array)$_POST["quantity"] as $_product_id => $_quantity) {
				$_product_id	= intval($_product_id);
				$_old_quantity	= isset($basket[$_product_id]) ? $basket[$_product_id]["quantity"] : 0;
				$_quantity		= intval($_quantity) + intval($_old_quantity);
				if ($_product_id && $_quantity) {
					$basket[$_product_id] = array(
						"product_id"=> $_product_id,
						"quantity"	=> $_quantity,
						"atts"			=> $_POST["atts"][$_product_id],
					);
				}
			}
			// Prevent double processing
			module('shop')->_basket_PROCESSED = true;
		}
		return js_redirect("./?object=shop");
	}
	
}