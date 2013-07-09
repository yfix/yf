<?php
class yf_manage_shop_product_add{

	function product_add () {
		if (!empty($_POST)) {
			if (!$_POST["name"]) {
				_re("Product name must be filled");
			}
			if ($_POST["ext_url"]) {
				if (substr($_POST["ext_url"], 0, 7) !== "http://") {
					$_POST["ext_url"] = "http://".$_POST["ext_url"];
				}
			}
			if (!common()->_error_exists()) {
				$sql_array = array(
					"name"				=> _es($_POST["name"]),
					"model"				=> _es($_POST["model"]),
					"url"				=> _es(common()->_propose_url_from_name($_POST["name"])),
					"description"		=> _es($_POST["desc"]),
					"meta_keywords"		=> _es($_POST["meta_keywords"]),
					"meta_desc"			=> _es($_POST["meta_desc"]),
					"external_url"		=> _es($_POST["ext_url"]),
					"quantity"			=> intval($_POST["quantity"]),
					"manufacturer_id"	=> intval($_POST["manufacturer"]),
					"price"				=> floatval(str_replace(",", ".", $_POST["price"])),
					"old_price"			=> floatval(str_replace(",", ".", $_POST["price"])),
					"featured"			=> intval((bool)$_POST["featured"]),
					"currency"			=> "",// TODO
					"add_date"			=> time(),
					"active"			=> 1,
				);
				// Image upload
				if (!empty($_FILES)) {
					$product_id = $_GET["id"];
					$product_name = _es(common()->_propose_url_from_name($_POST["name"]));
					$rez_upload = module("manage_shop")->_image_upload ($product_id, $product_name);
					$sql_array = array(
						"image"	=> 1,
					);
				} 
				db()->INSERT(db('shop_products'), $sql_array);
				foreach ((array)$_POST["category"] as $k => $v){
					$cat_id ["product_id"] = $_GET["id"];
					$cat_id ["category_id"] = $v;
					db()->INSERT(db('shop_product_to_category'), $cat_id);
				}
				$product_id = db()->INSERT_ID();
				module("manage_shop")->_attributes_save($product_id);
				module("manage_shop")->_save_group_prices($product_id);
			}
			return js_redirect("./?object=manage_shop&action=products_manage");
		}
		// 1-st type of assigning attributes
		$fields = module("manage_shop")->_attributes_html(0);
		// 2-nd type of assigning attributes (select boxes)
		// For case when we need just select custom attributes only one value of each
		$all_atts	= module("manage_shop")->_get_attributes();
		foreach ((array)$all_atts as $_attr_id => $_attr_info) {
			$_name_in_form = "single_attr[".$_attr_id."]";
			$_selected = "";
			$single_atts[$_attr_info["title"]] = array(
				"title"			=> _prepare_html($_attr_info["title"]),
				"name_in_form"	=> _prepare_html($_name_in_form),
				"box"			=> common()->select_box($_name_in_form, $_attr_info["value_list"], $_selected, false, 2, "", false),
			);
		}
		// Group prices here
		$group_prices = array();
		$user_groups = main()->get_data("user_groups");
		foreach ((array)module("manage_shop")->_get_group_prices(0) as $_group_id => $_group_price) {
			$group_prices[$_group_id] = array(
				"group_id"		=> intval($_group_id),
				"group_name"	=> _prepare_html($user_groups[$_group_id]),
				"price"			=> $_group_price ? number_format($_group_price, 2, '.', ' ') : "",
			);
		}
		$replace = array(
			"name"				=> "",
			"model"				=> "",
			"desc"				=> "",
			"meta_keywords"		=> "",
			"meta_desc"			=> "",
			"ext_url"			=> "",
			"price"				=> "",
			"old_price"			=> "",
			"quantity"			=> "",
			"dynamic_fields"	=> $fields,
			"single_atts"		=> $single_atts,
			"manufacturer_box"	=> common()->select_box("manufacturer", module("manage_shop")->_man_for_select, $man_id, false, 2),
			"category_box"		=> common()->multi_select("category", module("manage_shop")->_cats_for_select, $cat_id, false, 2, " size=15 ", false),
			"form_action"		=> "./?object=manage_shop&action=product_add",
			"back_url"			=> "./?object=manage_shop&action=products_manage",
			"categories_url"	=> "./?object=category_editor&action=show_items&id=shop_cats",
			"manufacturers_url"	=> "./?object=manage_shop&action=manufacturers",
			"group_prices"		=> !empty($group_prices) ? $group_prices : "",
		);
		foreach ((array)module("manage_shop")->_boxes as $item_name => $v) {
			$replace[$item_name."_box"] = module("manage_shop")->_box($item_name, $SF[$item_name]);
		}
		return tpl()->parse("manage_shop/product_edit", $replace);
	}
	
}