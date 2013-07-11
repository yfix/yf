<?php
class yf_manage_shop_manufacturers{

	function manufacturers () {
		return common()->table2("SELECT * FROM ".db('shop_manufacturers'))
#			->image("name")
			->text("name")
			->text("url")
			->text("meta_keywords")
			->text("meta_desc")
			->btn_edit("", "./?object=manage_shop&action=manufacturer_edit&id=%d")
			->btn_delete("", "./?object=manage_shop&action=manufacturer_delete&id=%d")
			->footer_link("Add", "./?object=manage_shop&action=manufacturer_add")
			->render();
	}	
	
/*
	function upload_image () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return "Empty ID!";
		}

		module('manage_shop')->_upload_image($_GET["id"]);
		return js_redirect($_SERVER["HTTP_REFERER"]);
	}

	function _upload_image ($man_id, $url) {
		$img_properties = getimagesize($_FILES['image']['tmp_name']);
		if (empty($img_properties) || !$man_id) {
			return false;
		}
		$img_path = module('manage_shop')->manufacturer_img_dir.$url."_".$man_id.module('manage_shop')->FULL_IMG_SUFFIX. ".jpg";
		$thumb_path = module('manage_shop')->manufacturer_img_dir.$url."_".$man_id.module('manage_shop')->THUMB_SUFFIX. ".jpg";
		// Do upload image
		$upload_result = common()->upload_image($img_path);
		if ($upload_result) {
			// Make thumb
			$resize_result = common()->make_thumb($img_path, $thumb_path, module('manage_shop')->THUMB_X, module('manage_shop')->THUMB_Y);
		}
		
		return true;
	}

	function _delete_image ($man_id) {
		$image_files = _class('dir')->scan_dir(module('manage_shop')->manufacturer_img_dir, true, "/".module('manage_shop')->IMG_PREFIX.$man_id."_/img");
		foreach((array)$image_files as $filepath) {
			unlink($filepath);
		}
		return true;
	}

	function delete_image () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return "Empty ID!";
		}

		module('manage_shop')->_delete_image($_GET["id"]);
		return js_redirect($_SERVER["HTTP_REFERER"]);
	}
*/
}