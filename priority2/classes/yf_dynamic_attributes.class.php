<?php
class yf_dynamic_attributes {

	/** example 
	
	$OBJ_DYNAMIC_ATTR = &main()->init_class("dynamic_attributes", "classes/");
	$fields = $OBJ_DYNAMIC_ATTR->_edit_html("shop");
	
	$OBJ_DYNAMIC_ATTR->_save("shop");
	
	$OBJ_DYNAMIC_ATTR->_view("shop");
	
	*/

	/**
	*
	*/
	function _edit ($category) {
		
		if(empty($category)){
			return;
		}

		$attributes = $this->_get_attributes($category);

		if(empty($attributes)){
			return;
		}
	
		if(!is_array($attributes)){
			return;
		}

		foreach ((array)$attributes as $key => $val){
			$fields_ids[$key] = $key;
		}
		
		$category_info = main()->get_data("dynamic_fields_categories");
		$category_id = intval($category_info[$category]);

		if(empty($category_id)){
			return;
		}

		
		$Q = db()->query("SELECT `field_id`,`value` FROM `".db('dynamic_fields_values')."`
		WHERE `category_id` = ".$category_id." AND `object_id` = ".intval($_GET["id"])." AND `field_id` IN(".implode(",", $fields_ids).")");
		while ($A = db()->fetch_assoc($Q)) {
			$fields_values[$A["field_id"]] = $A["value"];
		}
		
		foreach ((array)$attributes as $key => $field) {
			$type	= $field["type"];
			$_field_title = $field["title"];
			$_field_name = "attributes[".$key."]";
			
			
			// Set  default values
			$val	= $field["default_value"];
			
			if (isset($fields_values[$key])) {
				$val = $fields_values[$key];
			}

			if ($type == "varchar") {
				$replace2 = array(
					"name" 		=> $_field_name,
					"value"		=> $val,
				);
				$replace_dynamic[$key] = array(
					"field" 	=> tpl()->parse(__CLASS__ ."/dynamic_item_varchar", $replace2),
					"title"		=> $_field_title,
					"name"		=> $_field_name,
					"value"		=> $val,
				);
				
			} elseif ($type == "text") {
				$replace2 = array(
					"name" 	=> $_field_name,
					"value"	=> $val,
				);
				$replace_dynamic[$key] = array(
					"field" 	=> tpl()->parse(__CLASS__ ."/dynamic_item_text", $replace2),
					"title"		=> $_field_title,
					"name"		=> $_field_name,
					"value"		=> $val,
				);
			} elseif ($type == "select") {
				$replace_dynamic[$key] = array(
					"field" 	=> common()->select_box($_field_name, $field["value_list"], $val, false, 2, "", false),
					"title"		=> $_field_title,
					"name"		=> $_field_name,
					"value"		=> $val,
				);
			} elseif ($type == "check") {
				if(!is_array($val)){
					$val = unserialize($val);
				}
				
				foreach ((array)$field["value_list"] as $item_key => $item_val){
					$checkbox .=  common()->check_box($_field_name."[".$item_key."]", $item_val, isset($val[$item_key])?"1":"0")."<br>";
				}
				
				$replace_dynamic[$key] = array(
					"field" 	=> $checkbox,
					"title"		=> $_field_title,
					"name"		=> $_field_name,
					"value"		=> $val,
				);
			} elseif ($type == "radio") {
				$replace_dynamic[$key] = array(
					"field" 	=> common()->radio_box($_field_name, $field["value_list"], $val, true, 2, "", false),
					"title"		=> $_field_title,
					"name"		=> $_field_name,
					"value"		=> $val,
				);
			}
		}
		
		return $replace_dynamic;
	}
	
	/**
	*
	*/
	function _edit_html ($category) {
	
		$replace = array(
			"fields" => $this->_edit($category),
		);
		
		return tpl()->parse(__CLASS__ ."/dynamic_html", $replace);
	}
	
	/**
	*
	*/
	function _save ($category, $object_id = "") {
	
		if(empty($category)){
			return;
		}
		
		$category_info = main()->get_data("dynamic_fields_categories");
		$category_id = intval($category_info[$category]);
		
		if(empty($category_id)){
			return;
		}
	
		$attributes_values = $_POST["attributes"];
		if(empty($object_id)){
			$object_id = intval($_GET["id"]);
		}
		
		foreach ((array)$attributes_values as $key => $val){
			$fields_ids[$key] = $key;
		}

		$Q = db()->query("SELECT `field_id` FROM `".db('dynamic_fields_values')."` 
		WHERE `category_id` = ".$category_id." AND `object_id` = ".$object_id." AND `field_id` IN(".implode(",", $fields_ids).")");
		while ($A = db()->fetch_assoc($Q)) {
			$field_info[$A["field_id"]] = $A["field_id"];
		}
		
		foreach ((array)$attributes_values as $key => $val) {
		
			if(is_array($val)){
				foreach ((array)$val as $sub_key => $sub_val){
					$val[$sub_key] = $sub_key;
				}
				$val = serialize($val);
			}
			
			if(!in_array($key, (array)$field_info)){
				db()->INSERT("dynamic_fields_values", array(
					"category_id"		=> $category_id,
					"object_id"			=> $object_id,
					"field_id"			=> intval($key),
					"value"				=> _es($val),
				));
			}else{
				db()->UPDATE("dynamic_fields_values", array(
					"value"				=> _es($val),
				), "`object_id` = ".$object_id." AND `object_id` = ".$object_id." AND `field_id` = ".intval($key));
			}
			
		}
	}
	
	/**
	*
	*/
	function _view ($category, $object_id = "") {
	
		if(empty($category)){
			return;
		}
		
		$category_info = main()->get_data("dynamic_fields_categories");
		$category_id = intval($category_info[$category]);
		
		if(empty($category_id)){
			return;
		}
		
		if(empty($object_id)){
			$object_id = $_GET["id"];
		}

		$attributes = $this->_get_attributes($category);
		
		if(empty($attributes)){
			return;
		}
	
		foreach ((array)$attributes as $key => $val){
			$fields_ids[$key] = $key;
		}
		
		$Q = db()->query("SELECT `field_id`,`value` FROM `".db('dynamic_fields_values')."`
		WHERE `category_id` = ".$category_id." AND `object_id` = ".intval($object_id)." AND `field_id` IN(".implode(",", $fields_ids).")");
		while ($A = db()->fetch_assoc($Q)) {
			$fields_values[$A["field_id"]] = $A["value"];
		}
		
		foreach ((array)$attributes as $key => $val){
		
			
			if(($val["type"] == "select") || ($val["type"] == "check") || ($val["type"] == "radio")){

				if($val["type"] == "check"){
					$fields_values[$key] = unserialize($fields_values[$key]);
					
					foreach ((array)$fields_values[$key] as $sub_key => $sub_val){
						$temp_val[$sub_key] = $val["value_list"][$sub_key];
					}
					
					$temp_val = implode("<br>", $temp_val);
					
					$fields_values[$key] = $temp_val;
				}else{
					$fields_values[$key] = $val["value_list"][$fields_values[$key]];
				}
			}


			$field_val[$key] = array(
				"title"		=> $val["title"],
				"values"	=> $fields_values[$key],
				"type"		=> $val["type"],
			);
		}
		
		return $field_val;
	}
	

	/**
	*
	*/
	function _get_attributes ($category) {
		if(empty($category)){
			return;
		}
		
		$category_info = main()->get_data("dynamic_fields_categories");
		$category_id = intval($category_info[$category]);

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
	
	
	
}
