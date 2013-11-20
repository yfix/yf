<?php
class yf_shop_supplier_panel_images{

	/**
	*/
	function product_image_delete () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return "Empty ID!";
		}
		module("shop_supplier_panel")->_product_image_delete($_GET["id"], $_GET["key"]);
		module("manage_shop")->_product_images_add_revision($_GET['id']);		
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
		foreach((array)$image_files as $filepath) {
			preg_match($reg, $filepath, $rezult);
			$i[] =  $rezult["content"];
		}
		$max_key = max($i);
		if($max_key <= $k){
			return false;
		}
		$img_folder = module("manage_shop")->products_img_dir. $mpath;

		$thumb = $img_folder."/product_".$id."_".$max_key."_thumb.jpg";
		$big = $img_folder."/product_".$id."_".$max_key."_big.jpg";
		$deleted_thumb = $img_folder."/product_".$id."_".$k."_thumb.jpg";
		$deleted_big = $img_folder."/product_".$id."_".$k."_big.jpg";
		if(file_exists($thumb)){
			rename($thumb, $deleted_thumb);
		}
		if(file_exists($big)){
			rename($big, $deleted_big);
		}
		return true;
	}

	/**
	*/
	function set_main_image(){
		$product_info['id'] = intval($_GET['id']);
		$dirs = sprintf('%06s',$product_info['id']);
		$dir2 = substr($dirs,-3,3);
		$dir1 = substr($dirs,-6,3);
		$mpath = $dir1.'/'.$dir2.'/';

		if(!empty($_POST)){
			$img_folder = module("manage_shop")->products_img_dir. $mpath;

			$thumb = $img_folder."product_".$product_info['id']."_".$_POST['main_image']."_thumb.jpg";
			$big = $img_folder."product_".$product_info['id']."_".$_POST['main_image']."_big.jpg";
			$main_thumb = $img_folder."product_".$product_info['id']."_1_thumb.jpg";
			$main_big = $img_folder."product_".$product_info['id']."_1_big.jpg";
			$tmp_thumb = $img_folder."product_tmp".$product_info['id']."_1_thumb.jpg";
			$tmp_big = $img_folder."product_tmp".$product_info['id']."_1_big.jpg";
			if(file_exists($main_thumb)){
				rename($main_thumb, $tmp_thumb);
			}
			if(file_exists($main_big)){
				rename($main_big, $tmp_big);
			}
			if(file_exists($thumb)){
				rename($thumb, $main_thumb);
			}
			if(file_exists($big)){
				rename($big, $main_big);
			}
			if(file_exists($tmp_thumb)){
				rename($tmp_thumb, $thumb);
			}
			if(file_exists($tmp_big)){
				rename($tmp_big, $big);
			}
			module("manage_shop")->_product_images_add_revision($_GET['id']);
		}else{
			$image_files = _class('dir')->scan_dir(
				module('manage_shop')->products_img_dir. $mpath, 
				true, 
				'/product_'.$product_info['id'].'.+?_(thumb)\.jpg'.'/'
			);
			$reg = '/product_'.$product_info['id'].'_(?P<content>[\d]+)_(thumb)\.jpg/';
			if(!$image_files){
				return js_redirect($_SERVER["HTTP_REFERER"]);
			}
			sort($image_files);
			foreach((array)$image_files as $filepath) {
				preg_match($reg, $filepath, $rezult);
				$i =  $rezult['content'];

				$form_action ='./?object='.$_GET['object'].'&action='.$_GET['action'].'&id='.$product_info['id'];

				$thumb_path_temp = module('manage_shop')->products_img_webdir. $mpath. 'product_'.$product_info['id'].'_'.$i. module('manage_shop')->THUMB_SUFFIX.'.jpg';
				$img_path = module('manage_shop')->products_img_webdir. $mpath. 'product_'.$product_info['id'].'_'.$i. module('manage_shop')->FULL_IMG_SUFFIX.'.jpg';

				$items[] = array(
					'img_path' 		=> $img_path,
					'thumb_path'	=> $thumb_path_temp,
					'image_key'		=> $i,
				);
			}
			$replace = array(
				"form_action"=> $form_action,
				"items"		=> $items,
			);	
			return tpl()->parse($_GET['object'].'/image_items', $replace);
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
		$clean_image_url = "uploads/shop/products/{subdir2}/{subdir3}/product_%d_%i.jpg";

		$url = str_replace(array_keys($replace), array_values($replace), $url);
		$clean_image_url = str_replace(array_keys($replace), array_values($replace), $clean_image_url);
		while(file_exists(PROJECT_PATH. str_replace('%i', $i, str_replace('%s','big',$url)))) {
			$i++;
		}
		
		foreach ((array)$_FILES['image'] ['tmp_name'] as $k => $v) {
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
			
			$i++;
		} 
		return $i;
	}	
}