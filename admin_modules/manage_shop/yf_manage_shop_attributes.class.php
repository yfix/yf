<?php
class yf_manage_shop_attributes{

	/**
	*/
	function attributes () {
		return common()->table2("SELECT * FROM ".db('shop_product_attributes_info')." ORDER BY `order` ASC")
			->link("category_id", "./?object=category_editor&action=show_items&&id=%d", _class('cats')->_get_items_names("shop_cats"))
			->text("name")
			->text("value_list")
			->text("default_value")
			->btn_edit("", "./?object=manage_shop&action=attribute_edit&id=%d")
			->btn_delete("", "./?object=manage_shop&action=attribute_delete&id=%d")
			->btn_active("", "./?object=manage_shop&action=attribute_activate&id=%d")
			->footer_add("","./?object=manage_shop&action=attribute_add")
		;
	}	

	/**
	*/
	function attribute_add () {
		if ($_POST) {
			if (empty($_POST["name"])) {
				_re(t("Name is required"));
			}
			if (!common()->_error_exists()) {
				$value_list = array();
				foreach ((array)explode("\n", $_POST["value_list"]) as $val){
					$value_list[$val] = $val;
				}
				db()->INSERT("shop_product_attributes_info", db()->es(array(
					"name"			=> $_POST["name"],
					"type"			=> $_POST["type"],
					"value_list"	=> $value_list,
					"default_value"	=> $_POST["default_value"],
					"order"			=> $_POST["order"],
					"category_id"	=> $_POST["category_id"],
				)));
				common()->admin_wall_add(array('shop product attribute added: '.$_POST['name'], db()->insert_id()));
				if (main()->USE_SYSTEM_CACHE) {
					cache()->refresh("shop_product_attributes_info");
				}
				return js_redirect("./?object=manage_shop&action=attributes");
			}
		}
		$form_fields = array("name","type","value_list","default_value","order", "comment");
		$replace = array_fill_keys($form_fields, "");
		$replace = my_array_merge($replace, array(
			"form_action"	=> "./?object=manage_shop&action=".$_GET["action"]."&id=".$_GET["id"],
			"error"			=> _e(),
			"back_url"		=> "./?object=manage_shop&action=attributes",
			"active"		=> 1,
		));
		return common()->form2($replace)
			->text("name")
			->textarea("value_list")
			->select_box("category_id", module('manage_shop')->_cats_for_select, array("selected" => $A["category_id"]))
			->save_and_back();
	}

	/**
	*/
	function attribute_edit () {
		if (empty($_GET["id"])) {
			return _e(t("no id"));
		}
		$_GET["id"] = intval($_GET["id"]);
		$A = db()->query_fetch("SELECT * FROM ".db('shop_product_attributes_info')." WHERE id=".$_GET["id"]);
		if ($_POST) {
			if (empty($_POST["name"])) {
				_re(t("Name is required"));
			}
			if (empty($_POST["value_list"])) {
				_re(t("Values list is required"));
			}
			if (!common()->_error_exists()) {
				$value_list = array();
				foreach ((array)explode("\n", $_POST["value_list"]) as $val) {
					$value_list[$val] = $val;
				}
				db()->UPDATE("shop_product_attributes_info", db()->es(array(
					"name"			=> $_POST["name"],
					"type"			=> $_POST["type"],
					"value_list"	=> implode("\n", $value_list),
					"default_value"	=> $_POST["default_value"],
					"order"			=> $_POST["order"],
					"category_id"	=> $_POST["category_id"],
				)), "id=".$_GET["id"]); 
				common()->admin_wall_add(array('shop product attribute updated: '.$_POST['name'], $_GET['id']));
				if (main()->USE_SYSTEM_CACHE) {
					cache()->refresh("shop_product_attributes_info");
				}
				return js_redirect("./?object=manage_shop&action=attributes");
			}
		}
		$replace = array(
			"form_action"	=> "./?object=manage_shop&action=".$_GET["action"]."&id=".$A["id"],
			"error"			=> _e(),
			"name"			=> $A["name"],
			"value_list"	=> $A["value_list"],
			"default_value"	=> $A["default_value"],
			"order"			=> $A["order"],
			"back_url"		=> "./?object=manage_shop&action=attributes",
			"active"		=> 1,
		);
		return common()->form2($replace)
			->text("name")
			->textarea("value_list")
			->select_box("category_id", module('manage_shop')->_cats_for_select, array("selected" => $A["category_id"]))
			->save_and_back();
	}

	/**
	*/
	function attribute_delete () {
		$_GET["id"] = intval($_GET["id"]);
		$field_info = db()->query_fetch("SELECT * FROM ".db('shop_product_attributes_info')." WHERE id = ".intval($_GET["id"]));
		if (empty($field_info)) {
			return _e(t("no field"));
		}
		if ($_GET["id"]) {
			db()->query("DELETE FROM ".db('shop_product_attributes_info')." WHERE id=".$_GET["id"]);
			db()->query("DELETE FROM ".db('shop_product_attributes_values')." WHERE category_id = ".module('manage_shop')->ATTRIBUTES_CAT_ID." AND field_id = ".$_GET["id"]);
			common()->admin_wall_add(array('shop product attribute deleted: '.$_GET['id'], $_GET['id']));
			if (main()->USE_SYSTEM_CACHE) {
				cache()->refresh("shop_product_attributes_info");
			}
		}
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo $_GET["id"];
		} else {
			return js_redirect($_SERVER["HTTP_REFERER"], 0);
		}
	}

	/**
	*/	
	function attribute_activate () {
		if ($_GET["id"]){
			$info = db()->query_fetch("SELECT * FROM ".db('shop_product_attributes_info')." WHERE id=".intval($_GET["id"]));
			if ($info["active"] == 1) {
				$active = 0;
			} elseif ($info["active"] == 0) {
				$active = 1;
			}
			db()->UPDATE(db('shop_product_attributes_info'), array("active" => $active), "id='".intval($_GET["id"])."'");
			common()->admin_wall_add(array('shop product attribute: '.$_GET['id'].' '.($info['active'] ? 'inactivated' : 'activated'), $_GET['id']));
		}
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo ($active ? 1 : 0);
		} else {
			return js_redirect("./?object=manage_shop");
		}
	}

	/**
	*/
	function _get_attributes ($category_id = 0) {
		if (empty($category_id)) {
			return array();
		}
		$fields_info = main()->get_data("shop_product_attributes_info");
		foreach ((array)$fields_info[$category_id] as $A){
			$attributes[$A["id"]] = array(
				"title"			=> $A["name"],
				"type"			=> $A["type"],
				"value_list"	=> explode("\n", $A["value_list"]),
				"default_value"	=> $A["default_value"],
			);
		}
		return $attributes;
	}

	/**
	*/
	function _get_attributes_values ($category_id = 0, $object_id = 0, $fields_ids = 0) {
		if (empty($category_id) || empty($object_id) || empty($fields_ids)) {
			return array();
		}
		$Q = db()->query(
			"SELECT field_id,value,add_value 
			FROM ".db('shop_product_attributes_values')."
			WHERE category_id = ".intval($category_id)." 
				AND object_id = ".intval($object_id)." 
				AND field_id IN(".implode(",", $fields_ids).")");
		while ($A = db()->fetch_assoc($Q)) {
			$fields_values[$A["field_id"]] = array(
				"is_selected"	=> explode("\n", $A["value"]),
				"value_price"	=> explode("\n", $A["add_value"]),
			);
		}
		return $fields_values;
	}

	/**
	*/
	function _get_products_attributes($products_ids = array()) {
		if (is_numeric($products_ids)) {
			$return_single_id = $products_ids;
			$products_ids = array($products_ids);
		}
		if (empty($products_ids)) {
			return array();
		}
		$fields_info = main()->get_data("shop_product_attributes_info");

		$Q = db()->query("SELECT * FROM ".db('shop_product_attributes_values')." WHERE object_id IN (".implode(",", $products_ids).")");
		while ($A = db()->fetch_assoc($Q)) {
			$_product_id = $A["object_id"];

			$A["value"]		= explode("\n", $A["value"]);
			$A["add_value"] = explode("\n", $A["add_value"]);

			foreach ((array)$A["value"] as $_attr_id => $_dummy) {
				$_price = $A["add_value"][$_attr_id];
				$_item_id = $A["field_id"]."_".$_attr_id;
				$_field_info = $fields_info[$A["field_id"]];
				$_field_info["value_list"] = explode("\n", $_field_info["value_list"]);

				$data[$_product_id][$_item_id] = array(
					"id" 			=> $_item_id,
					"price"			=> $_price,
					"name"			=> $_field_info["name"],
					"value"			=> $_field_info["value_list"][$_attr_id],
					"product_id"	=> $_product_id,
				);
			}
		}
		if ($return_single_id) {
			return $data[$return_single_id];
		}
		return $data;
	}

	/**
	*/
	function _attributes_view ($object_id = 0) {
		return module("manage_shop")->_attributes_html($object_id, true);
	}

	/**
	*/
	function _attributes_html ($object_id = 0, $only_selected = false) {
		$object_id		= $params["object_id"];
		$only_selected	= $params["only_selected"];

		$category_info = main()->get_data("dynamic_fields_categories");
		$category_id = intval(module('manage_shop')->ATTRIBUTES_CAT_ID);

		if (empty($category_id)) {
			return;
		}
		$attributes = module('manage_shop')->_get_attributes($category_id);
		if (empty($attributes) || !is_array($attributes)) {
			return;
		}
		foreach ((array)$attributes as $key => $val){
			$fields_ids[$key] = $key;
		}
		$fields_values = module('manage_shop')->_get_attributes_values ($category_id, $object_id, $fields_ids);
		foreach ((array)$attributes as $_attr_id => $_info) {
			$i++;
			foreach ((array)$_info["value_list"] as $_val_id => $_value) {
				$_item_id = $_attr_id."_".$_val_id;
				$selected_info = $fields_values[$_attr_id];
				if ($only_selected && !$selected_info["is_selected"][$_val_id]) {
					continue;
				}
				$data[$_item_id] = array(
					"bg_class"		=> !($i % 2) ? "bg1" : "bg2",
					"id"			=> $_item_id,
					"attr_checked"	=> intval((bool)$selected_info["is_selected"][$_val_id]),
					"attr_price"	=> _prepare_html($selected_info["value_price"][$_val_id]),
					"attr_name"		=> _prepare_html($_info["title"]),
					"attr_value"	=> _prepare_html($_value),
				);
			}
		}
		return $data;
	}

	/**
	*/
	function _attributes_save ($object_id = 0) {
		if (empty($object_id)) {
			return;
		}
		// 2-nd case of dynamic attributes assignment
		foreach ((array)$_POST["single_attr"] as $_attr_id => $_sel_id) {
			$_item_id = $_attr_id."_".$_sel_id;
			$_POST["attributes_use"][$_item_id] = 1;
		}
		$category_info = main()->get_data("dynamic_fields_categories");
		$category_id = intval(module('manage_shop')->ATTRIBUTES_CAT_ID);
		if (empty($category_id)) {
			return;
		}
		$attributes = module('manage_shop')->_get_attributes($category_id);
		if (empty($attributes) || !is_array($attributes)) {
			return;
		}
		foreach ((array)$attributes as $key => $val){
			$fields_ids[$key] = $key;
		}
		$fields_values = module('manage_shop')->_get_attributes_values ($category_id, $object_id, $fields_ids);
		foreach ((array)$attributes as $_attr_id => $_info) {
			$option_values	= array();
			$value_prices	= array();

			foreach ((array)$_info["value_list"] as $_val_id => $_value) {
				$_item_id = $_attr_id."_".$_val_id;
				if ($_POST["attributes_use"][$_item_id]) {
					$option_values[$_val_id]	= $_POST["attributes_use"][$_item_id];
				}
				$value_prices[$_val_id]		= $_POST["attributes_price"][$_item_id];
			}
			$option_values	= serialize($option_values);
			$value_prices	= serialize($value_prices);
			if (!isset($fields_values[$_attr_id])) {
				db()->INSERT("shop_product_attributes_values", array(
					"category_id"	=> $category_id,
					"object_id"		=> $object_id,
					"field_id"		=> intval($_attr_id),
					"value"			=> _es($option_values),
					"add_value"		=> _es($value_prices),
				));
			} else {
				db()->UPDATE("shop_product_attributes_values", array(
					"value"			=> _es($option_values),
					"add_value"		=> _es($value_prices),
				), "category_id = ".$category_id." AND object_id = ".$object_id." AND field_id = ".intval($_attr_id));
			}
		}
		return true;
	}

}