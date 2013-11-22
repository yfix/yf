<?php
class yf_shop_contentm_panel_images{

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
}