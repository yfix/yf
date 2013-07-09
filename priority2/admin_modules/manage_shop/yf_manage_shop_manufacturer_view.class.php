<?php
class yf_manage_shop_manufacturer_view{

	function manufacturer_view () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return "Empty ID!";
		}
		$manufacturer_info = db()->query_fetch("SELECT * FROM ".db('shop_manufacturers')." WHERE id=".$_GET["id"]);
		$img_path = module('manage_shop')->manufacturer_img_dir.$manufacturer_info["url"]."_".$manufacturer_info["id"].module('manage_shop')->FULL_IMG_SUFFIX. ".jpg";
		if (!file_exists($img_path)) {
			$img_path = "";
		} else {
			$img_path = module('manage_shop')->manufacturer_img_webdir.$manufacturer_info["url"]."_".$manufacturer_info["id"].module('manage_shop')->FULL_IMG_SUFFIX. ".jpg";
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
			"desc"				=> _prepare_html($manufacturer_info["desc"]),
			"thumb_path"		=> $thumb_path,
			"img_path"			=> $img_path,
			"delete_image_url"	=> "./?object=manage_shop&action=delete_image&id=".$manufacturer_info["id"],
			"form_action"		=> "./?object=manage_shop&action=manufacturer_edit&id=".$manufacturer_info["id"],
			"back_url"			=> "./?object=manage_shop&action=manufacturers",
		);
		return tpl()->parse("manage_shop/manufacturer_view", $replace);
	}	
	
}