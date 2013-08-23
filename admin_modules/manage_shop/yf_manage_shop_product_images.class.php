<?php
class yf_manage_shop_product_images{

	/**
	*/
	function product_image_delete () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return "Empty ID!";
		}
		module("manage_shop")->_product_image_delete($_GET["id"], $_GET["name"], $_GET["key"]);
		return js_redirect($_SERVER["HTTP_REFERER"]);
	}

	/**
	*/
	function _product_image_delete ($id, $name, $k) {
		$dirs = sprintf("%06s",$id);
		$dir2 = substr($dirs,-3,3);
		$dir1 = substr($dirs,-6,3);
		$mpath = $dir1."/".$dir2."/";

		$image_files = _class('dir')->scan_dir(
			module("manage_shop")->products_img_dir. $mpath,
			true,
#			"/".$name."_".$id."_".$k.".+?jpg"."/"
			"/product_".$id."_".$k.".+?jpg"."/"
		);
		foreach((array)$image_files as $filepath) {
			unlink($filepath);
		}

		$image_files = _class('dir')->scan_dir(
			module("manage_shop")->products_img_dir. $mpath, 
			true, 
#			"/".$name."_".$id.".+?.jpg"."/"
			"/product_".$id.".+?.jpg"."/"
		);
		if (!$image_files) {
			$sql_array = array(
				"image"	=> 0,
			);
			db()->UPDATE('shop_products', $sql_array, "id=".$_GET["id"]); 
			common()->admin_wall_add(array('shop product image deleted: '.$_GET['id'], $_GET['id']));
		}
		return true;
	}

	/**
	*/
	function product_image_upload () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return "Empty ID!";
		}
		module("manage_shop")->_product_image_upload($_GET["id"]);
		return js_redirect($_SERVER["HTTP_REFERER"]);
	}

	/**
	*/
	function _product_image_upload ($product_id, $product_name) {
		$products_images_dir = module("manage_shop")->products_img_dir;
		$i = 1;
		$dirs = sprintf("%06s",$product_id);
		$dir2 = substr($dirs,-3,3);
		$dir1 = substr($dirs,-6,3);
		$mpath = $dir1."/".$dir2."/";
		foreach ((array)$_FILES['image'] ['tmp_name'] as $k => $v) {
			$img_properties = getimagesize($v);
			if (empty($img_properties) || !$product_id) {
				return false;
			}
/*
			$img_path = $products_images_dir. $mpath. $product_name."_".$product_id."_".$i. module("manage_shop")->FULL_IMG_SUFFIX.".jpg";
			$i = $this->_product_image_check_id_filled($img_path, $product_id, $product_name, $i);
			$img_path = $products_images_dir. $mpath. $product_name."_".$product_id."_".$i. module("manage_shop")->FULL_IMG_SUFFIX.".jpg";
			$img_path_thumb = $products_images_dir. $mpath. $product_name."_".$product_id."_".$i. module("manage_shop")->THUMB_SUFFIX.".jpg";
*/
			$img_path = $products_images_dir. $mpath. "product_".$product_id."_".$i. module("manage_shop")->FULL_IMG_SUFFIX.".jpg";
			$i = $this->_product_image_check_id_filled($img_path, $product_id, $product_name, $i);
			$img_path = $products_images_dir. $mpath. "product_".$product_id."_".$i. module("manage_shop")->FULL_IMG_SUFFIX.".jpg";
			$img_path_thumb = $products_images_dir. $mpath. "product_".$product_id."_".$i. module("manage_shop")->THUMB_SUFFIX.".jpg";

			$upload_result = common()->multi_upload_image($img_path, $k);
			if ($upload_result) {
				$resize_result = common()->make_thumb($img_path, $img_path_thumb, module("manage_shop")->THUMB_X, module("manage_shop")->THUMB_Y);
			}
		} 
		return $i;
	}

	function _product_image_check_id_filled ($path, $product_id, $product_name, $i) {
		if (file_exists($path)) {
			$i = $i +1;
			$img_path = module("manage_shop")->products_img_dir.$product_name."_".$product_id."_".$i.module("manage_shop")->FULL_IMG_SUFFIX.".jpg";
// TODO: check if this needed at all?
		} 
		return $i;
	}
	
}