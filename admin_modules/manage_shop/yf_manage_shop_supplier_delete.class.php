<?php
class yf_manage_shop_supplier_delete{

	function supplier_delete () {
		$_GET["id"] = intval($_GET["id"]);
		if (!empty($_GET["id"])) {
			$info = db()->query_fetch("SELECT * FROM ".db('shop_suppliers')." WHERE id=".intval($_GET["id"]));
		}
		if (!empty($info["id"])) {
			db()->query("DELETE FROM ".db('shop_suppliers')." WHERE id=".intval($_GET["id"])." LIMIT 1");
		}
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo $_GET["id"];
		} else {
			return js_redirect("./?object=manage_shop&action=suppliers");
		}
	}	
	
}