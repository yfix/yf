<?php
class yf_manage_shop_product_edit{

	function product_edit () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return "Empty ID!";
		}
		$product_info = db()->query_fetch("SELECT * FROM ".db('shop_products')." WHERE id=".$_GET["id"]);
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
					"cat_id"			=> _es($_POST["cat_id"]),
					"url"				=> _es(common()->_propose_url_from_name($_POST["name"])),
					"description"		=> _es($_POST["desc"]),
					"meta_keywords"		=> _es($_POST["meta_keywords"]),
					"meta_desc"			=> _es($_POST["meta_desc"]),
					"external_url"		=> _es($_POST["ext_url"]),
					"price"				=> number_format($_POST["price"], 2, '.', ''),
					"price_promo"		=> number_format($_POST["price_promo"], 2, '.', ''),
					"price_partner"		=> number_format($_POST["price_partner"], 2, '.', ''),
					"price_raw"			=> number_format($_POST["price_raw"], 2, '.', ''),
					"old_price"			=> number_format($_POST["old_price"], 2, '.', ''),
					"manufacturer_id"	=> intval($_POST["manufacturer"]),
					"supplier_id"		=> intval($_POST["supplier"]),
					"quantity"			=> intval($_POST["quantity"]),
					"featured"			=> intval((bool)$_POST["featured"]),
				);
				// Image upload
				if (!empty($_FILES)) {
					$product_id = $_GET["id"];
					module("manage_shop")->_product_image_upload($product_id);
					$sql_array['image'] = 1;
				} 
				db()->UPDATE(db('shop_products'), $sql_array, "id=".$_GET["id"]);
				
				cache_del("_shop_product_params|_get_params_by_product|".$_GET['id']);
				db()->query("DELETE FROM `".db('shop_products_productparams')."` WHERE `product_id`=".$_GET['id']);
				if ($_POST['productparams'] != '') {
					foreach($_POST['productparams_options_' . $_POST['productparams']] as $v) {
						db()->INSERT("shop_products_productparams",array(
							"product_id" => $_GET['id'],
							"productparam_id" => $_POST['productparams'],
							"value"	=> $v,
						));
					}
				}
				
				common()->admin_wall_add(array('shop product updated: '.$_POST['name'], $_GET['id']));
				db()->query("DELETE FROM  ".db('shop_product_to_category')." WHERE product_id = ".$_GET["id"]);
				foreach ((array)$_POST["category"] as $k => $v){
					$cat_id["product_id"] = $_GET["id"];
					$cat_id["category_id"] = $v;
					db()->INSERT(db('shop_product_to_category'), $cat_id);
				}
				
				db()->query("DELETE FROM " . db('shop_product_related') ."  WHERE product_id = '" . (int)$_GET["id"] . "'");

				if (isset($_POST["product_related"])) {
					foreach ((array)$_POST["product_related"] as $related_id) {
						$related["product_id"] = $_GET["id"];
						$related["related_id"] = $related_id;
						db()->INSERT( db('shop_product_related'), $related);
					}
				}
				module("manage_shop")->_attributes_save($_GET["id"]);
			}
			return js_redirect("./?object=manage_shop&action=products");
		}
		
		$dirs = sprintf("%06s",$product_info["id"]);
		$dir2 = substr($dirs,-3,3);
		$dir1 = substr($dirs,-6,3);
		$mpath = $dir1."/".$dir2."/";
		$image_files = _class('dir')->scan_dir(
			module("manage_shop")->products_img_dir.$mpath, 
			true, 
#				"/".$product_info["url"]."_".$product_info["id"].".+?_small\.jpg"."/"
			"/product_".$product_info["id"].".+?_thumb\.jpg"."/"
		);
#			$reg = "/".$product_info["url"]."_".$product_info["id"]."_(?P<content>[\d]+)_small\.jpg/";
		$reg = "/product_".$product_info["id"]."_(?P<content>[\d]+)_thumb\.jpg/";
		foreach((array)$image_files as $filepath) {
			preg_match($reg, $filepath, $rezult);
			$i =  $rezult["content"];

			$product_image_delete_url ="./?object=manage_shop&action=product_image_delete&id=".$product_info["id"]."&name=".$product_info["url"]."&key=".$i;
#				$thumb_path_temp = module("manage_shop")->products_img_webdir.$mpath. $product_info["url"]."_".$product_info["id"]."_".$i.module("manage_shop")->THUMB_SUFFIX.".jpg";
			$thumb_path_temp = module("manage_shop")->products_img_webdir.$mpath. "product_".$product_info["id"]."_".$i. module("manage_shop")->THUMB_SUFFIX.".jpg";
#				$img_path = module("manage_shop")->products_img_webdir. $mpath. $product_info["url"]."_".$product_info["id"]."_".$i.module("manage_shop")->FULL_IMG_SUFFIX.".jpg";
			$img_path = module("manage_shop")->products_img_webdir. $mpath. "product_".$product_info["id"]."_".$i. module("manage_shop")->FULL_IMG_SUFFIX.".jpg";

			$replace2 = array(
				"img_path" 		=> $img_path,
				"thumb_path"	=> $thumb_path_temp,
				"del_url" 		=> $product_image_delete_url,
				"name"			=> $product_info["url"],
			);
			$items .= tpl()->parse("manage_shop/image_items", $replace2);
		}
	
		// 1-st type of assigning attributes
		$fields = module("manage_shop")->_attributes_html($_GET["id"]);
		// 2-nd type of assigning attributes (select boxes)
		// For case when we need just select custom attributes only one value of each
		$all_atts	= module("manage_shop")->_get_attributes();
		$saved_attrs	= module("manage_shop")->_get_products_attributes($_GET["id"]);
		foreach ((array)$all_atts as $_attr_id => $_attr_info) {
			$_name_in_form = "single_attr[".$_attr_id."]";
			$_selected = "";
			// Try to get selected value
			$_cur_item_prefix = $_attr_id."_";
			foreach ((array)$saved_attrs as $_item_id => $_item_info) {
				if (substr($_item_id, 0, strlen($_cur_item_prefix)) == $_cur_item_prefix) {
					$_selected = substr($_item_id, strlen($_cur_item_prefix));
					break;
				}
			}
			$single_atts[$_attr_info["title"]] = array(
				"title"			=> _prepare_html($_attr_info["title"]),
				"name_in_form"	=> _prepare_html($_name_in_form),
				"box"			=> common()->select_box($_name_in_form, $_attr_info["value_list"], $_selected, false, 2, "", false),
			);
		}
		$sql1 = "SELECT category_id FROM ".db('shop_product_to_category')." WHERE product_id = ". $_GET["id"];
		$products = db()->query($sql1);
		while ($A = db()->fetch_assoc($products)) {
			$cat_id[$A["category_id"]] .= $A["category_id"];
		}	
		$replace = array(
			"name"					=> $product_info["name"],
			"model"					=> $product_info["model"],
			"desc"					=> $product_info["description"],
			"meta_keywords"			=> $product_info["meta_keywords"],
			"meta_desc"				=> $product_info["meta_desc"],
			"price"					=> $product_info["price"],
			"price_promo"			=> $product_info["price_promo"],
			"price_partner"			=> $product_info["price_partner"],
			"price_raw"				=> $product_info["price_raw"],
			"old_price"				=> $product_info["old_price"],
			"quantity"				=> $product_info["quantity"],
			"productparams"			=> _class("manage_shop","admin_modules")->_productparams_container($_GET['id']),
			"dynamic_fields"		=> $fields,
			"single_atts"			=> $single_atts,
			"ext_url"				=> $product_info["external_url"],
			"manufacturer_box"		=> common()->select_box("manufacturer", module("manage_shop")->_man_for_select, $product_info["manufacturer_id"], false, 2),
			"supplier_box"			=> common()->select_box("supplier", module("manage_shop")->_suppliers_for_select, $product_info["supplier_id"], false, 2),
			"category_box"			=> common()->multi_select("category", module("manage_shop")->_cats_for_select, $cat_id, false, 2, " size=5 ", false),
			"cat_id_box"			=> common()->select_box("cat_id", module("manage_shop")->_cats_for_select, $product_info["cat_id"], false, 2),
			"featured_box"			=> module("manage_shop")->_box("featured", $product_info["featured"]),
			"featured"				=> $product_info["featured"],
			"form_action"			=> "./?object=manage_shop&action=product_edit&id=".$product_info["id"],
			"back_url"				=> "./?object=manage_shop&action=products",
			"image"					=> $items,
			"categories_url"		=> "./?object=category_editor&action=show_items&id=shop_cats",
			"manufacturers_url"		=> "./?object=manage_shop&action=manufacturers",
			"suppliers_url"			=> "./?object=manage_shop&action=suppliers",
			"manage_attrs_url"		=> "./?object=manage_shop&action=attributes",
			"group_prices"			=> !empty($group_prices) ? $group_prices : "",
			"link_get_product"		=>  process_url("./?object=manage_shop&action=show_product_by_category&cat_id="),
			"product_related"		=>  module("manage_shop")->related_products($product_info["id"]),
		);
		return tpl()->parse("manage_shop/product_edit", $replace);
	}
	
}