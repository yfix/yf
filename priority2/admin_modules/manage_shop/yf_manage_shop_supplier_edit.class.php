<?php
class yf_manage_shop_supplier_edit{

	function supplier_edit () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return "Empty ID!";
		}
		$supplier_info = db()->query_fetch("SELECT * FROM ".db('shop_suppliers')." WHERE id=".$_GET["id"]);
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
				db()->update('shop_suppliers', db()->es($sql_array), "id=".$_GET["id"]);
				if (!empty($_FILES)) {
					$man_id = $_GET["id"];
					$this->_upload_image($man_id, $url);
				} 
			}
			return js_redirect("./?object=manage_shop&action=suppliers");
		}
		$thumb_path = module('manage_shop')->supplier_img_dir.$supplier_info["url"]."_".$supplier_info["id"].module('manage_shop')->THUMB_SUFFIX. ".jpg";
		if (!file_exists($thumb_path)) {
			$thumb_path = "";
		} else {
			$thumb_path = module('manage_shop')->supplier_img_webdir.$supplier_info["url"]."_".$supplier_info["id"].module('manage_shop')->THUMB_SUFFIX. ".jpg";
		}
		$replace = array(
			"name"				=> $supplier_info["name"],
			"sort_order"		=> $supplier_info["sort_order"],
			"desc"				=> $supplier_info["desc"],
			"thumb_path"		=> $thumb_path,
			"delete_image_url"	=> "./?object=manage_shop&action=delete_image&id=".$supplier_info["id"],
			"form_action"		=> "./?object=manage_shop&action=supplier_edit&id=".$supplier_info["id"],
			"back_url"			=> "./?object=manage_shop&action=suppliers",
		);
		return common()->form2($replace)
			->text("name")
			->textarea("desc","Description")
			->text("url")
			->text("meta_keywords")
			->text("meta_desc")
			->integer("sort_order")
			->save_and_back()
			->render();
	}	
}