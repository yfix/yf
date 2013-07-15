<?php
class yf_manage_shop__image_upload{

	function _image_upload ($product_id, $product_name) {
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
			$i = module("manage_shop")->_check_filed($img_path, $product_id, $product_name, $i);
			$img_path = $products_images_dir. $mpath. $product_name."_".$product_id."_".$i. module("manage_shop")->FULL_IMG_SUFFIX.".jpg";
			$img_path_thumb = $products_images_dir. $mpath. $product_name."_".$product_id."_".$i. module("manage_shop")->THUMB_SUFFIX.".jpg";
*/
			$img_path = $products_images_dir. $mpath. "product_".$product_id."_".$i. module("manage_shop")->FULL_IMG_SUFFIX.".jpg";
			$i = module("manage_shop")->_check_filed($img_path, $product_id, $product_name, $i);
			$img_path = $products_images_dir. $mpath. "product_".$product_id."_".$i. module("manage_shop")->FULL_IMG_SUFFIX.".jpg";
			$img_path_thumb = $products_images_dir. $mpath. "product_".$product_id."_".$i. module("manage_shop")->THUMB_SUFFIX.".jpg";

			$upload_result = common()->multi_upload_image($img_path, $k);
			if ($upload_result) {
				$resize_result = common()->make_thumb($img_path, $img_path_thumb, module("manage_shop")->THUMB_X, module("manage_shop")->THUMB_Y);
			}
		} 
		return $i;
	}
	
}