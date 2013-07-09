<?php
class yf_manage_shop_delete_order{

	function delete_order() {
		$_GET["id"] = intval($_GET["id"]);
		if (!empty($_GET["id"])) {
			$order_info = db()->query_fetch("SELECT * FROM ".db('shop_orders')." WHERE id=".intval($_GET["id"]));
		}
		if (!empty($order_info["id"])) {
			db()->query("DELETE FROM ".db('shop_orders')." WHERE id=".intval($_GET["id"])." LIMIT 1");
			db()->query("DELETE FROM ".db('shop_order_items')." WHERE `order`_id=".intval($_GET["id"]));
		}
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			$_GET["id"];
		} else {
			return js_redirect("./?object=manage_shop&action=show_orders");
		}
	}
	
}