<?php
class yf_manage_shop_manufacturer_edit{

	function manufacturer_edit () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return "Empty ID!";
		}
		$manufacturer_info = db()->query_fetch("SELECT * FROM ".db('shop_manufacturer')." WHERE id=".$_GET["id"]);
		if (!empty($_POST)) {
			if (!$_POST["name"]) {
				_re("Product name must be filled");
			}
			if (!common()->_error_exists()) {
				$sql_array = array(
					"name"		=> $_POST["name"],
					"url"		=> common()->_propose_url_from_name($_POST["name"]),
					"desc"		=> $_POST["desc"],
					"sort_order"=> intval($_POST["featured"]),
				);
				db()->UPDATE(db('shop_manufacturer'), db()->es($sql_array), "id=".$_GET["id"]);
				if (!empty($_FILES)) {
					$man_id = $_GET["id"];
					module('manage_shop')->_upload_image($man_id, $url);
				} 
			}
			return js_redirect("./?object=manage_shop&action=manufacturers");
		}
		$thumb_path = module('manage_shop')->manufacturer_img_dir.$manufacturer_info["url"]."_".$manufacturer_info["id"].module('manage_shop')->THUMB_SUFFIX. ".jpg";
		if (!file_exists($thumb_path)) {
			$thumb_path = "";
		} else {
			$thumb_path = module('manage_shop')->manufacturer_img_webdir.$manufacturer_info["url"]."_".$manufacturer_info["id"].module('manage_shop')->THUMB_SUFFIX. ".jpg";
		}
		$replace = array(
			"name"				=> $manufacturer_info["name"],
			"sort_order"		=> $manufacturer_info["sort_order"],
			"desc"				=> $manufacturer_info["desc"],
			"thumb_path"		=> $thumb_path,
			"delete_image_url"	=> "./?object=manage_shop&action=delete_image&id=".$manufacturer_info["id"],
			"form_action"		=> "./?object=manage_shop&action=manufacturer_edit&id=".$manufacturer_info["id"],
			"back_url"			=> "./?object=manage_shop&action=manufacturers",
		);
		return tpl()->parse("manage_shop/manufacturer_edit", $replace);
	}	
	
}