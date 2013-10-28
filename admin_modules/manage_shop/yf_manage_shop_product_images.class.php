<?php
class yf_manage_shop_product_images{

	/**
	*/
	function product_image_delete () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return "Empty ID!";
		}
		module("manage_shop")->_product_image_delete($_GET["id"], $_GET["key"]);
		return js_redirect($_SERVER["HTTP_REFERER"]);
	}
	
	/**
	*/
	function _product_images_rename($id, $k, $mpath){
		$image_files = _class('dir')->scan_dir(
			module("manage_shop")->products_img_dir.$mpath, 
			true, 
			"/product_".$id.".+?_thumb\.jpg"."/"
		);
		$reg = "/product_".$id."_(?P<content>[\d]+)_thumb\.jpg/";
		sort($image_files);
print_r($image_files);
exit;
/*
		foreach((array)$image_files as $filepath) {
			preg_match($reg, $filepath, $rezult);
			$i =  $rezult["content"];

			$product_image_delete_url ="./?object=manage_shop&action=product_image_delete&id=".$product_info["id"]."&key=".$i;
*/		
	}

	/**
	*/
	function _product_image_delete ($id, $k) {
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
		$this->_product_images_rename($id, $k, $mpath);
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
	function _product_image_upload ($product_id) {
		$products_images_dir = module("manage_shop")->products_img_dir;
		$i = 1;
		
		$d = sprintf("%09s", $product_id);
		$replace = array(
			'{subdir1}' => substr($d, 0, -6),
			'{subdir2}' => substr($d, -6, 3),
			'{subdir3}' => substr($d, -3, 3),
			'%d'        => $product_id,
		);
		$url = "uploads/shop/products/{subdir2}/{subdir3}/product_%d_%i_%s.jpg";

		$url = str_replace(array_keys($replace), array_values($replace), $url);
		while(file_exists(PROJECT_PATH. str_replace('%i', $i, str_replace('%s','big',$url)))) {
			$i++;
		}
		
		foreach ((array)$_FILES['image'] ['tmp_name'] as $k => $v) {
			$img_properties = getimagesize($v);
			if (empty($img_properties) || !$product_id) {
				return false;
			}
			$img_path = PROJECT_PATH. str_replace('%i', $i, str_replace('%s','big',$url));
			$img_path_thumb = PROJECT_PATH. str_replace('%i', $i, str_replace('%s','thumb',$url));

			common()->make_thumb($v, $img_path_thumb, module("manage_shop")->THUMB_X, module("manage_shop")->THUMB_Y);
			common()->make_thumb($v, $img_path, module("manage_shop")->BIG_X, module("manage_shop")->BIG_Y);
			
			$i++;
		} 
		return $i;
	}	
}