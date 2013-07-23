<?php
class yf_manage_shop_product_clone{

	function product_clone () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return "Empty ID!";
		}
		$info = db()->query_fetch("SELECT * FROM ".db('shop_products')." WHERE id=".intval($_GET["id"]));
		if (empty($info["id"])) {
			return _e(t("No such product!"));
		}
		$sql = $info;
		$old_product_id = $sql["id"];
		unset($sql["id"]);
		$sql["name"] = "Clone ".$sql["name"];
		$sql["active"] = 0;

		db()->insert('shop_products', $sql);
		$new_product_id = db()->insert_id();
/*
		db()->query("DELETE FROM ".db('shop_product_attributes_values')." WHERE object_id=".$_GET["id"]);
		db()->query("DELETE FROM ".db('shop_group_options')." WHERE product_id=".$_GET["id"]);		
*/
// TODO: clone product attributes
		if ($sql['image'] && $new_product_id) {
			$dirs = sprintf("%06s", $old_product_id);
			$dir2 = substr($dirs, -3, 3);
			$dir1 = substr($dirs, -6, 3);
			$m_path = $dir1."/".$dir2."/";
			$old_images = _class('dir')->scan_dir(
				module("manage_shop")->products_img_dir. $m_path,
				true,
				"/product_".$old_product_id."_.+?\.jpg/"
			);
			foreach((array)$old_images as $old_image_path) {
				$nd = sprintf("%06s", $new_product_id);
				$nd2 = substr($nd, -3, 3);
				$nd1 = substr($nd, -6, 3);
				$n_path = $nd1."/".$nd2."/";
				$new_image_path = str_replace("/product_".$old_product_id."_", "/product_".$new_product_id."_", str_replace($m_path, $n_path, $old_image_path));
				$new_dir = dirname($new_image_path);
				if (!file_exists($new_dir)) {
					mkdir($new_dir, 0777, true);
				}
				copy($old_image_path, $new_image_path);
			}
		}
		return js_redirect("./?object=manage_shopaction=products");
	}
	
}