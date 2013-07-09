<?php
class yf_manage_shop_manufacturers{

	function manufacturers () {
		$sql = "SELECT * FROM ".db('shop_manufacturer')."";
		$filter_sql = module('manage_shop')->USE_FILTER ? module('manage_shop')->_create_filter_sql() : "";
		$sql .= strlen($filter_sql) ? " WHERE 1=1 ". $filter_sql : " ORDER BY name ASC ";
		list($add_sql, $pages, $total) = common()->divide_pages($sql);
		$orders_info = db()->query_fetch_all($sql.$add_sql);

		if (!empty($orders_info)) {
			foreach ((array)$orders_info as $v){
				$user_ids[] = $v["user_id"];
			}
			$user_infos = user($user_ids);
		}

		foreach ((array)$orders_info as $v){
			$items[] = array(
				"order_id"			=> $v["id"],
				"name"				=> $v["name"],
				"sort_order"		=> $v["sort_order"],
				"view_url"			=> "./?object=manage_shop&action=manufacturer_view&id=".$v["id"],
				"delete_url"		=> "./?object=manage_shop&action=manufacturer_delete&id=".$v["id"],
				"edit_url"			=> "./?object=manage_shop&action=manufacturer_edit&id=".$v["id"],
			);
		}
		$replace = array(
			"items"		=> (array)$items,
			"pages"		=> $pages,
			"total"		=> intval($total),
			"filter"	=> module('manage_shop')->USE_FILTER ? module('manage_shop')->_show_filter() : "",
			"add_url"	=> "./?object=manage_shop&action=manufacturer_add",
		);
		return tpl()->parse("manage_shop/manufacturer_main", $replace); 
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