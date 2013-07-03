<?php

/**
* Manage shop sub module
*/
class yf_manage_shop_atts {

	/**
	* Manage attributes
	*/
	function manage_attributes () {
		$sql = "SELECT * FROM `".db('dynamic_fields_info')."` WHERE `category_id` = ".intval(module('manage_shop')->ATTRIBUTES_CAT_ID)." ORDER BY `order`";
		foreach ((array)db()->query_fetch_all($sql) as $A) {
			$values =  (array)unserialize($A["value_list"]);

			$items[$A["id"]] = array(
				"id"			=> intval($A["id"]),
				"name"			=> _prepare_html($A["name"]),
				"type"			=> _prepare_html($A["type"]),
				"value_list"	=> nl2br(_prepare_html(implode("\n", $values))),
				"default_value"	=> _prepare_html($A["default_value"]),
				"order"			=> $A["order"],
				"edit_url"		=> "./?object=manage_shop&action=edit_attribute&id=".$A["id"],
				"delete_url"	=> "./?object=manage_shop&action=delete_attribute&id=".$A["id"],
				"active_link"   => "./?object=manage_shop&action=activate_attribute&id=".$A["id"],
				"active"		=> $A["active"],
			);
		}
		$replace = array(
			"add_url"		=> "./?object=manage_shop&action=add_attribute",
			"items"			=> $items,
		);
		return tpl()->parse("manage_shop/attributes_main", $replace); 
	}	

	/**
	* 
	*/
	function add_attribute () {
	
		if(isset($_POST["go"])){
		
			if(empty($_POST["name"])){
				common()->_raise_error(t("Name is required"));
			}
			
			if(!common()->_error_exists()){
			
				$value_list	= explode("\n", $_POST["value_list"]);
				$i=0;
				foreach ((array)$value_list as $val){
					$i++;
					$value_list_temp[$i] = $val;
				}
				$value_list = serialize($value_list_temp);
			
				$sql_array = array(
					"name"			=> _es($_POST["name"]),
					"type"			=> $_POST["type"],
					"value_list"	=> $value_list,
					"default_value"	=> _es($_POST["default_value"]),
					"order"			=> $_POST["order"],
					"category_id"	=> intval(module('manage_shop')->ATTRIBUTES_CAT_ID),
				);
				
				db()->INSERT("dynamic_fields_info", $sql_array); 

				if (main()->USE_SYSTEM_CACHE)	{
					cache()->refresh("dynamic_fields_info");
				}

				return js_redirect("./?object=manage_shop&action=manage_attributes");
			
			}
		}
	
		// Show add form here
		$form_fields = array("name","type","value_list","default_value","order", "comment");
		$replace = array_fill_keys($form_fields, "");
		$replace = my_array_merge($replace, array(
			"back_url"		=> "./?object=manage_shop&action=manage_attributes",
			"active"		=> 1,
			"form_action"	=> "./?object=manage_shop&action=".$_GET["action"]."&id=".$_GET["id"],
			"error"			=> _e(),
		));
		return tpl()->parse("manage_shop/attributes_edit", $replace);
	}

	/**
	* 
	*/
	function edit_attribute () {
	
		if(empty($_GET["id"])){
			return _e(t("no id"));
		}

		$_GET["id"] = intval($_GET["id"]);
		
		$A = db()->query_fetch("SELECT * FROM `".db('dynamic_fields_info')."` WHERE `id`=".$_GET["id"]);
		
		if(isset($_POST["go"])){
		
			if (empty($_POST["name"])) {
				common()->_raise_error(t("Name is required"));
			}
			
			if (!common()->_error_exists()) {
			
				$value_list	= explode("\n", $_POST["value_list"]);
				$i=0;
				foreach ((array)$value_list as $val){
					$i++;
					$value_list_temp[$i] = $val;
				}
				$value_list = serialize($value_list_temp);
			
				// Save data
				$sql_array = array(
					"name"			=> _es($_POST["name"]),
					"type"			=> $_POST["type"],
					"value_list"	=> $value_list,
					"default_value"	=> _es($_POST["default_value"]),
					"order"			=> $_POST["order"],
				);
				db()->UPDATE("dynamic_fields_info", $sql_array, "`id`=".$_GET["id"]); 

				if (main()->USE_SYSTEM_CACHE)	{
					cache()->refresh("dynamic_fields_info");
				}

				return js_redirect("./?object=manage_shop&action=manage_attributes");
			}
		}
		
		// Show edit form here
		$replace = array(
			"name"			=> _prepare_html($A["name"]),
			"value_list"	=> _prepare_html(implode("\n", (array)unserialize($A["value_list"]))),
			"default_value"	=> _prepare_html($A["default_value"]),
			"order"			=> $A["order"],
			"back_url"		=> "./?object=manage_shop&action=manage_attributes",
			"active"		=> 1,
			"form_action"	=> "./?object=manage_shop&action=".$_GET["action"]."&id=".$A["id"],
			"error"			=> _e(),
		);
		return tpl()->parse("manage_shop/attributes_edit", $replace);
	}

	/**
	* 
	*/
	function delete_attribute () {
		$_GET["id"] = intval($_GET["id"]);
		
		$field_info = db()->query_fetch("SELECT * FROM `".db('dynamic_fields_info')."` WHERE `id` = ".intval($_GET["id"]));
		
		if(empty($field_info)){
			return _e(t("no field"));
		}
		
		// Do delete record
		if ($_GET["id"]) {
			db()->query("DELETE FROM `".db('dynamic_fields_info')."` WHERE `id`=".$_GET["id"]);
			db()->query("DELETE FROM `".db('dynamic_fields_values')."` WHERE `category_id` = ".module('manage_shop')->ATTRIBUTES_CAT_ID." AND `field_id` = ".$_GET["id"]);

			if (main()->USE_SYSTEM_CACHE)	{
				cache()->refresh("dynamic_fields_info");
			}
		}
		// Return user back
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo $_GET["id"];
		} else {
			return js_redirect($_SERVER["HTTP_REFERER"], 0);
		}
	}

	/**
	* Html for edit form
	*/
	function _attributes_html ($params = array()) {
		$object_id		= $params["object_id"];
		$only_selected	= $params["only_selected"];

		$category_info = main()->get_data("dynamic_fields_categories");
		$category_id = intval(module('manage_shop')->ATTRIBUTES_CAT_ID);

		if(empty($category_id)){
			return;
		}

		$attributes = $this->_get_attributes($category_id);

		if(empty($attributes) || !is_array($attributes)){
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
	* 
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
		
		if(empty($category_id)){
			return;
		}

		$attributes = $this->_get_attributes($category_id);

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
				db()->INSERT("dynamic_fields_values", array(
					"category_id"	=> $category_id,
					"object_id"		=> $object_id,
					"field_id"		=> intval($_attr_id),
					"value"			=> _es($option_values),
					"add_value"		=> _es($value_prices),
				));
			} else {
				db()->UPDATE("dynamic_fields_values", array(
					"value"			=> _es($option_values),
					"add_value"		=> _es($value_prices),
				), "`category_id` = ".$category_id." AND `object_id` = ".$object_id." AND `field_id` = ".intval($_attr_id));
			}
		}
	}

	/**
	*
	*/
	function _get_attributes ($category_id = 0) {
		if (empty($category_id)) {
			$category_id = module('manage_shop')->ATTRIBUTES_CAT_ID;
		}
		if (empty($category_id)) {
			return array();
		}

		$fields_info = main()->get_data("dynamic_fields_info");
		
		foreach ((array)$fields_info[$category_id] as $A){
			$attributes[$A["id"]] = array(
				"title"			=> $A["name"],
				"type"			=> $A["type"],
				"value_list"	=> unserialize($A["value_list"]),
				"default_value"	=> $A["default_value"],
			);
		}
		return $attributes;
	}

	/**
	* Get products attributes
	*/
	function _get_products_attributes($products_ids = array()) {
		if (is_numeric($products_ids)) {
			$return_single_id = $products_ids;
			$products_ids = array($products_ids);
		}
		if (empty($products_ids)) {
			return array();
		}

		$fields_info = main()->get_data("dynamic_fields_info");

		$Q = db()->query("SELECT * FROM `".db('dynamic_fields_values')."` WHERE `category_id`=1 AND `object_id` IN (".implode(",", $products_ids).")");
		while ($A = db()->fetch_assoc($Q)) {
			$_product_id = $A["object_id"];

			$A["value"]		= strlen($A["value"]) ? unserialize($A["value"]) : array();
			$A["add_value"] = strlen($A["add_value"]) ? unserialize($A["add_value"]) : array();

			foreach ((array)$A["value"] as $_attr_id => $_dummy) {
				$_price = $A["add_value"][$_attr_id];
				$_item_id = $A["field_id"]."_".$_attr_id;
				$_field_info = $fields_info[module('manage_shop')->ATTRIBUTES_CAT_ID][$A["field_id"]];
				$_field_info["value_list"] = strlen($_field_info["value_list"]) ? unserialize($_field_info["value_list"]) : array();

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
	*
	*/
	function _get_attributes_values ($params = array()) {
		$category_id	= $params["category_id"];
		$object_id		= $params["object_id"];
		$fields_ids		= $params["fields_ids"];

		if (empty($category_id) || empty($object_id) || empty($fields_ids)) {
			return array();
		}
		$Q = db()->query(
			"SELECT `field_id`,`value`,`add_value` 
			FROM `".db('dynamic_fields_values')."`
			WHERE `category_id` = ".$category_id." 
				AND `object_id` = ".intval($object_id)." 
				AND `field_id` IN(".implode(",", $fields_ids).")");
		while ($A = db()->fetch_assoc($Q)) {
			$fields_values[$A["field_id"]] = array(
				"is_selected"	=> (array)unserialize($A["value"]),
				"value_price"	=> (array)unserialize($A["add_value"]),
			);
		}
		return $fields_values;
	}
}
