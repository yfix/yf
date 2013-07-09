<?php
class yf_manage_shop_manufacturer_add{

	function manufacturer_add () {
		if (!empty($_POST)) {
			if (!$_POST["name"]) {
				_re("Product name must be filled");
			}
			if (!common()->_error_exists()) {
				// Save data
				$url = ;
				$sql_array = array(
					"name"			=> $_POST["name"],
					"url"			=> common()->_propose_url_from_name($_POST["name"]),
					"desc"			=> $_POST["desc"],
					"sort_order"	=> intval($_POST["featured"]),
				);
				db()->insert(db('shop_manufacturer'), db()->es($sql_array));
				if (!empty($_FILES)) {
					$man_id = $_GET["id"];
					module('manage_shop')->_upload_image ($man_id, $url);
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
			"name"				=> "",
			"sort_order"		=> "",
			"desc"				=> "",
			"thumb_path"		=> "",
			"delete_image_url"	=> "./?object=manage_shop&action=delete_image&id=".$manufacturer_info["id"],
			"form_action"		=> "./?object=manage_shop&action=manufacturer_add",
			"back_url"			=> "./?object=manage_shop&action=manufacturers",
		);
		return tpl()->parse("manage_shop/manufacturer_edit", $replace);
	}	
	
}