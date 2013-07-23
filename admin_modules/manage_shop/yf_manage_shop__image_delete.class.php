<?php
class yf_manage_shop__image_delete{

	function _image_delete ($id, $name, $k) {
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
		}
		return true;
	}
	
}