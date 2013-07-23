<?php
class yf_manage_shop_product_activate{

	function product_activate () {
		if ($_GET["id"]){
			$A = db()->query_fetch("SELECT * FROM ".db('shop_products')." WHERE id=".intval($_GET["id"]));
			if ($A["active"] == 1) {
				$active = 0;
			} elseif ($A["active"] == 0) {
				$active = 1;
			}
			db()->UPDATE(db('shop_products'), array("active" => $active), "id='".intval($_GET["id"])."'");
		}
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo ($active ? 1 : 0);
		} else {
			return js_redirect("./?object=manage_shop");
		}
	}
	
}