<?php
class yf_manage_shop__check_filed{

	function _check_filed ($path, $product_id, $product_name, $i) {
		if (file_exists($path)) {
			$i = $i +1;
			$img_path = module("manage_shop")->products_img_dir.$product_name."_".$product_id."_".$i.module("manage_shop")->FULL_IMG_SUFFIX.".jpg";
			module("manage_shop")->_check_filed ($img_path, $product_id, $product_name, $i);
		} 
		return $i;
	}
	
}