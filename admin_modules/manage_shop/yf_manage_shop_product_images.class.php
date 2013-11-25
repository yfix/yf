<?php
class yf_manage_shop_product_images{

	/**
	*/
	function product_image_delete () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return "Empty ID!";
		}
		if (empty($_GET["key"])) {
			return "Empty image key!";
		}
		$A = db()->get_all("SELECT * FROM `".db('shop_product_images')."` WHERE `product_id`=".intval($_GET['id'])." && `id`=".intval($_GET['key']));
		if (count($A) == 0){
			 return "Image not found";
		}
		module("manage_shop")->_product_image_delete($_GET["id"], $_GET["key"]);
		module("manage_shop")->_product_images_add_revision($_GET['id']);
		return js_redirect($_SERVER["HTTP_REFERER"]);
	}

	/**
	*/
	function set_main_image(){
		$product_id = intval($_GET['id']);
		if(!empty($_POST)){
			db()->query("UPDATE `".db('shop_product_images')."` SET `is_default`='0' WHERE `product_id`=".$product_id);
			db()->query("UPDATE `".db('shop_product_images')."` SET `is_default`='1' WHERE `id`=".$_POST['main_image']);
			module("manage_shop")->_product_images_add_revision($_GET['id']);
		}else{
			$images = common()->shop_get_images($product_id);
			if(!$images){
				return js_redirect($_SERVER["HTTP_REFERER"]);
			}
			$base_url = WEB_PATH;
			$media_host = ( defined( 'MEDIA_HOST' ) ? MEDIA_HOST : false );
			if( !empty( $media_host ) ) { $base_url = '//' . $media_host . '/'; }		
			foreach((array)$images as $A) {
				$items[] = array(
					'img_path' 		=> $base_url . $A['big'],
					'thumb_path'	=> $base_url . $A['thumb'],
					'image_key'		=> $A['id'],
				);
			}
			$form_action ='./?object='.$_GET['object'].'&action='.$_GET['action'].'&id='.$product_id;
			$replace = array(
				"form_action"=> $form_action,
				"items"		=> $items,
			);	
			return tpl()->parse($_GET['object'].'/set_image_items', $replace);
		}
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
			"/product_".$id."_".$k.".+?jpg"."/"
		);
		foreach((array)$image_files as $filepath) {
			unlink($filepath);
		}
		db()->query("DELETE FROM `".db('shop_product_images')."` WHERE `product_id`=".intval($_GET['id'])." AND `id`=".intval($_GET['key']));
		common()->admin_wall_add(array('shop product image deleted: '.$_GET['id'], $_GET['id']));
		
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
		
		$d = sprintf("%09s", $product_id);
		$replace = array(
			'{subdir1}' => substr($d, 0, -6),
			'{subdir2}' => substr($d, -6, 3),
			'{subdir3}' => substr($d, -3, 3),
			'%d'        => $product_id,
		);
		$url = "uploads/shop/products/{subdir2}/{subdir3}/product_%d_%i_%s.jpg";
		$clean_image_url = "uploads/shop/products/{subdir2}/{subdir3}/product_%d_%i.jpg";

		$url = str_replace(array_keys($replace), array_values($replace), $url);
		$clean_image_url = str_replace(array_keys($replace), array_values($replace), $clean_image_url);
		
		foreach ((array)$_FILES['image'] ['tmp_name'] as $v) {
			db()->insert(db('shop_product_images'), array(
				'product_id' => $product_id,
				'date_uploaded' => $_SERVER['REQUEST_TIME'],
			));
			$i = db()->insert_id();
			
			$img_properties = getimagesize($v);
			if (empty($img_properties) || !$product_id) {
				return false;
			}
			$img_path = PROJECT_PATH.str_replace('%i', $i, $clean_image_url);
			$img_path_big = PROJECT_PATH. str_replace('%i', $i, str_replace('%s','big',$url));
			$img_path_thumb = PROJECT_PATH. str_replace('%i', $i, str_replace('%s','thumb',$url));
			$watermark_path = PROJECT_PATH.SITE_WATERMARK_FILE;

			common()->make_thumb($v, $img_path, module("manage_shop")->BIG_X, module("manage_shop")->BIG_Y);
			common()->make_thumb($v, $img_path_thumb, module("manage_shop")->THUMB_X, module("manage_shop")->THUMB_Y, $watermark_path);
			common()->make_thumb($v, $img_path_big, module("manage_shop")->BIG_X, module("manage_shop")->BIG_Y, $watermark_path); 
			
			$A = db()->query_fetch("SELECT COUNT(*) AS `cnt` FROM `".db('shop_product_images')."` WHERE `product_id`='".$product_id."' AND is_default='1'");
			if ($A['cnt'] == 0) {
				$A = db()->query_fetch("SELECT `id` FROM `".db('shop_product_images')."` WHERE `product_id`='".$product_id."' ORDER BY `id`");
				db()->query("UPDATE `".db('shop_product_images')."` SET `is_default`='1' WHERE `id`=".$A['id']);
			}			
		} 
		return $i;
	}	

}