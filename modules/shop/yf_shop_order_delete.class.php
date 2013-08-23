<?php
class yf_shop_order_delete{

	/**
	* Delete order
	*/
	function _order_delete() {
		$_GET["id"] = intval($_GET["id"]);
		// Get current info
		if (!empty($_GET["id"])) {
			$order_info = db()->query_fetch("SELECT * FROM ".db('shop_orders')." WHERE id=".intval($_GET["id"]));
		}
		// Do delete order
		if (!empty($order_info["id"])) {
			db()->query("DELETE FROM ".db('shop_orders')." WHERE id=".intval($_GET["id"])." LIMIT 1");
			db()->query("DELETE FROM ".db('shop_order_items')." WHERE `order_id`=".intval($_GET["id"]));
		}
		// Return user back
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo $_GET["id"];
		} else {
			return js_redirect("./?object=shop&action=orders");
		}
	}
	
}