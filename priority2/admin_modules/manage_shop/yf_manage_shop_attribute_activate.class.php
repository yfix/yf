<?php
class yf_manage_shop_attribute_activate{

	function attribute_activate () {
		if ($_GET["id"]){
			$A = db()->query_fetch("SELECT * FROM ".db('shop_product_attributes_info')." WHERE id=".intval($_GET["id"]));
			if ($A["active"] == 1) {
				$active = 0;
			} elseif ($A["active"] == 0) {
				$active = 1;
			}
			db()->UPDATE(db('shop_product_attributes_info'), array("active" => $active), "id='".intval($_GET["id"])."'");
		}
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo ($active ? 1 : 0);
		} else {
			return js_redirect("./?object=manage_shop");
		}
	}
	
}