<?php
class yf_manage_dynamic_attributes{

	/*
	* Framework Constructor
	*/
	function _init () {
		$this->_field_type = array(
			"varchar",
			"text",
			"select",
			"check",
			"radio",
		);
		foreach ((array)$this->_field_type as $k => $v) {
			$this->_field_type[$v] = $v;
			unset ($this->_field_type[$k]);	
		}
		$this->_boxes = array(
			"type"	=> 'select_box("type", $this->_field_type, $selected, 0, 2, "", false)',
		);
	}

	/**
	*
	*/
	function show () {
	
		$sql		= "SELECT * FROM ".db('dynamic_fields_categories')."";
		$order_sql	= " ";
		list($add_sql, $pages, $total) = common()->divide_pages($sql);
		$Q = db()->query($sql.$order_sql.$add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			
			$replace2 = array(
				"name"		=> $A["name"],
				"bg_class"	=> !(++$i % 2) ? "bg1" : "bg2",
				"pages"		=> $pages,
				"edit_link"			=> "./?object=".$_GET["object"]."&action=edit_category&id=".$A["id"],
				"delete_link"		=> "./?object=".$_GET["object"]."&action=delete_category&id=".$A["id"],
				"view_link"			=> "./?object=".$_GET["object"]."&action=view_category&id=".$A["id"],
			);
			
			$items .= tpl()->parse($_GET["object"]."/category_item", $replace2);
		}

		$replace = array(
			"add_actegory_link"	=> "./?object=".$_GET["object"]."&action=add_category",
			"pages"				=> $pages,
			"error"				=> _e(),
			"items"				=> $items,
		);
		
		return tpl()->parse($_GET["object"]."/category_main", $replace);
	}
	
	/**
	*
	*/
	function add_category () {
	
		if (main()->is_post()){
			if(empty($_POST["name"])){
				_re(t("Name is required"));
			}
			
			if(!common()->_error_exists()){
				db()->INSERT("dynamic_fields_categories", array(
					"name"		=> _es($_POST["name"]),
				));
			}
			if (main()->USE_SYSTEM_CACHE)	{
				cache()->refresh("dynamic_fields_categories");
			}
			return js_redirect("./?object=".$_GET["object"]);
		}
	
		$replace = array(
			"form_action"		=> "./?object=".$_GET["object"]."&action=".$_GET["action"],
			"name"				=> $_POST["name"],
		);
		
		return tpl()->parse($_GET["object"]."/category_edit", $replace);
	}
	
	/**
	*
	*/
	function edit_category () {
	
		$category_info = db()->query_fetch("SELECT * FROM ".db('dynamic_fields_categories')." WHERE id = ".intval($_GET["id"]));
		
		if(empty($category_info)){
			return _e(t("No category"));
		}
	
		if (main()->is_post()){
			if(empty($_POST["name"])){
				_re(t("Name is required"));
			}
			
			if(!common()->_error_exists()){
				db()->UPDATE("dynamic_fields_categories", array(
					"name"		=> _es($_POST["name"]),
				), "id=".intval($_GET["id"]));
			}
			if (main()->USE_SYSTEM_CACHE)	{
				cache()->refresh("dynamic_fields_categories");
			}
			
			return js_redirect("./?object=".$_GET["object"]);
		}
	
		$replace = array(
			"form_action"		=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$_GET["id"],
			"name"				=> $category_info["name"],
		);
		
		return tpl()->parse($_GET["object"]."/category_edit", $replace);
	}
	
	/**
	*
	*/
	function delete_category () {
		
		$category_info = db()->query_fetch("SELECT * FROM ".db('dynamic_fields_categories')." WHERE id = ".intval($_GET["id"]));
		
		if(empty($category_info)){
			return _e(t("No category"));
		}
		
		db()->query("DELETE FROM ".db('dynamic_fields_categories')." WHERE id=".intval($_GET["id"])." LIMIT 1");

		if (main()->USE_SYSTEM_CACHE)	{
			cache()->refresh("dynamic_fields_categories");
		}
		return js_redirect("./?object=".$_GET["object"]);
	}

	function view_category () {
	
		if(empty($_GET["id"])){
			return _e(t("no id"));
		}
	
		$sql = "SELECT * FROM ".db('dynamic_fields_info')." WHERE category_id = ".intval($_GET["id"])." ORDER BY `order`";
		
		foreach ((array)db()->query_fetch_all($sql) as $A) {
			$replace2 = array(
				"id"			=> intval($A["id"]),
				"bg_class"		=> !(++$i % 2) ? "bg1" : "bg2",
				"name"			=> _prepare_html($A["name"]),
				"type"			=> _prepare_html($A["type"]),
				"value_list"	=> _prepare_html(implode(", ", (array)unserialize($A["value_list"]))),
				"default_value"	=> _prepare_html($A["default_value"]),
				"order"			=> $A["order"],
				"edit_url"		=> "./?object=".$_GET["object"]."&action=edit&id=".$A["id"],
				"delete_url"	=> "./?object=".$_GET["object"]."&action=delete&id=".$A["id"],
				"active_link"   => "./?object=".$_GET["object"]."&action=activate&id=".$A["id"],
				"active"		=> $A["active"],
			);
			$items .= tpl()->parse($_GET["object"]."/item", $replace2); 
		}
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"]."&action=mass_delete",
			"add_url"		=> "./?object=".$_GET["object"]."&action=add&id=".intval($_GET["id"]),
			"items"			=> $items,
		);
		return tpl()->parse($_GET["object"]."/main", $replace); 
	}	

	/*
	* Add field
	*/
	function add () {
	
		if (main()->is_post()){
		
			if(empty($_POST["name"])){
				_re(t("Name is required"));
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
					"category_id"	=> intval($_GET["id"]),
				);
				
				db()->INSERT("dynamic_fields_info", $sql_array); 

				if (main()->USE_SYSTEM_CACHE)	{
					cache()->refresh("dynamic_fields_info");
				}

				return js_redirect("./?object=".$_GET["object"]."&action=view_category&id=".$_GET["id"]);
			
			}
		}
	
		// Show add form here
		$form_fields = array("name","type","value_list","default_value","order", "comment");
		$replace = array_fill_keys($form_fields, "");
		$replace = my_array_merge($replace, array(
			"back_url"		=> "./?object=".$_GET["object"],
			"active"		=> 1,
			"form_action"	=> "./?object=".$_GET["object"]."&action=add&id=".$_GET["id"],
			"error"			=> _e(),
		));
		// Process boxes
		foreach ((array)$this->_boxes as $item_name => $v) {
			$replace[$item_name."_box"] = $this->_box($item_name, "varchar");
		}
		return tpl()->parse($_GET["object"]."/edit_form", $replace);
	}

	/*
	* Edit field
	*/
	function edit () {
	
		if(empty($_GET["id"])){
			return _e(t("no id"));
		}

		$_GET["id"] = intval($_GET["id"]);
		
		$A = db()->query_fetch("SELECT * FROM ".db('dynamic_fields_info')." WHERE id=".$_GET["id"]);
		
		if (main()->is_post()){
		
			if(empty($_POST["name"])){
					_re(t("Name is required"));
			}
			
			if(!common()->_error_exists()){
			
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
				db()->UPDATE("dynamic_fields_info", $sql_array, "id=".$_GET["id"]); 

				if (main()->USE_SYSTEM_CACHE)	{
					cache()->refresh("dynamic_fields_info");
				}

				return js_redirect("./?object=".$_GET["object"]."&action=view_category&id=".$A["category_id"]);
			}
		}
		
		// Show edit form here
		$replace = array(
			"name"			=> _prepare_html($A["name"]),
			"value_list"	=> _prepare_html(implode("\n", (array)unserialize($A["value_list"]))),
			"default_value"	=> _prepare_html($A["default_value"]),
			"order"			=> $A["order"],
			"back_url"		=> "./?object=".$_GET["object"],
			"active"		=> 1,
			"form_action"	=> "./?object=".$_GET["object"]."&action=edit&id=".$A["id"],
			"error"			=> _e(),
		);
		// Process boxes
		foreach ((array)$this->_boxes as $item_name => $v) {
			$replace[$item_name."_box"] = $this->_box($item_name, $A["type"]);
		}
		return tpl()->parse($_GET["object"]."/edit_form", $replace);				
	}

	/*
	* Delete field
	*/
	function delete () {
		$_GET["id"] = intval($_GET["id"]);
		
		$field_info = db()->query_fetch("SELECT * FROM ".db('dynamic_fields_info')." WHERE id = ".intval($_GET["id"]));
		
		if(empty($field_info)){
			return _e(t("no field"));
		}
		
		// Do delete record
		if ($_GET["id"]) {
			db()->query("DELETE FROM ".db('dynamic_fields_info')." WHERE id=".$_GET["id"]);
			db()->query("DELETE FROM ".db('dynamic_fields_values')." WHERE category_id = ".$field_info["category_id"]." AND field_id = ".$_GET["id"]);

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
	* Activate\deactivate fields
	*/
	function activate () {
		$_GET["id"] = intval($_GET["id"]);
		if ($_GET["id"]) {
			list($_active) = db()->query_fetch("SELECT active AS `0` FROM ".db('dynamic_fields_info')." WHERE id=".intval($_GET["id"]));
			if ($_active == 0) {
				$_set_active = 1;
			} else {
				$_set_active = 0;
			}
			$sql_array = array(
				"active" => $_set_active,
			);
			db()->UPDATE("dynamic_fields_info", $sql_array, "id=".intval($_GET["id"]));

			if (main()->USE_SYSTEM_CACHE)	{
				cache()->refresh("dynamic_fields_info");
			}
		}
		// Return user back
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo ($_set_active);
		} else {
			return js_redirect("./?object=".$_GET["object"]);
		}
	}

	function _box ($name = "", $selected = "") {
		if (empty($name) || empty($this->_boxes[$name])) return false;
		else return eval("return common()->".$this->_boxes[$name].";");
	}
	
}
