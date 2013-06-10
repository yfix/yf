<?php

//-----------------------------------------------------------------------------
// Class to handle user dynamic info fields
class yf_user_dynamic_info {

	/** @var @conf_skip */
	public $AVAIL_DISPLAY_ON = array(
		"user_info",
		"register",
		"user_profile",
		"compact_info",
	);

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

	/*
	* Default method
	*/
	function show () {
		$sql = "SELECT * FROM `".db('user_data_info_fields')."` ORDER BY `order`";
		foreach ((array)db()->query_fetch_all($sql) as $A) {
			$replace2 = array(
				"id"			=> intval($A["id"]),
				"bg_class"		=> !(++$i % 2) ? "bg1" : "bg2",
				"name"			=> _prepare_html($A["name"]),
				"type"			=> _prepare_html($A["type"]),
				"value_list"	=> $A["value_list"],
				"default_value"	=> _prepare_html($A["default_value"]),
				"order"			=> $A["order"],
				"comment"		=> _prepare_html($A["comment"]),
				"edit_url"		=> "./?object=".$_GET["object"]."&action=edit&id=".$A["id"],
				"delete_url"	=> "./?object=".$_GET["object"]."&action=delete&id=".$A["id"],
				"active_link"   => "./?object=".$_GET["object"]."&action=activate&id=".$A["id"],
				"active"		=> $A["active"],
			);
			$items .= tpl()->parse($_GET["object"]."/item", $replace2); 
		}
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"]."&action=mass_delete",
			"add_url"		=> "./?object=".$_GET["object"]."&action=add",
			"items"			=> $items,
		);
		return tpl()->parse($_GET["object"]."/main", $replace); 
	}

	/*
	* Add field
	*/
	function add () {
		if(!empty($_POST) && isset($_POST["name"])) {
			$sql_array = array(
				"name"			=> _es($_POST["name"]),
				"type"			=> $_POST["type"],
				"value_list"	=> _es($_POST["value_list"]),
				"default_value"	=> _es($_POST["default_value"]),
				"order"			=> $_POST["order"],
				"comment"		=> _es($_POST["comment"]),
//				"active"		=> $_POST["active"],
			);
			db()->INSERT("user_data_info_fields", $sql_array); 

			$this->_refresh_cache();

			return js_redirect("./?object=".$_GET["object"]);
		}
		// Show add form here
		$form_fields = array("name","type","value_list","default_value","order", "comment");
		$replace = array_fill_keys($form_fields, "");
		$replace = my_array_merge($replace, array(
			"back_url"		=> "./?object=".$_GET["object"],
			"active"		=> 1,
			"form_action"	=> "./?object=".$_GET["object"]."&action=add",
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
		$_GET["id"] = intval($_GET["id"]);
		$A = db()->query_fetch("SELECT * FROM `".db('user_data_info_fields')."` WHERE `id`=".$_GET["id"]);
		// Save data
		if(!empty($_POST) && isset($_POST["name"]) && isset($_GET["id"])) {
			$sql_array = array(
				"name"			=> _es($_POST["name"]),
				"type"			=> $_POST["type"],
				"value_list"	=> _es($_POST["value_list"]),
				"default_value"	=> _es($_POST["default_value"]),
				"order"			=> $_POST["order"],
				"comment"		=> _es($_POST["comment"]),
			);
			db()->UPDATE("user_data_info_fields", $sql_array, "`id`=".$_GET["id"]); 

			$this->_refresh_cache();

			return js_redirect("./?object=".$_GET["object"]);
		}
		// Show edit form here
		$replace = array(
			"name"			=> _prepare_html($A["name"]),
			"value_list"	=> _prepare_html($A["value_list"]),
			"default_value"	=> _prepare_html($A["default_value"]),
			"order"			=> $A["order"],
			"comment"		=> _prepare_html($A["comment"]),
			"back_url"		=> "./?object=".$_GET["object"],
			"active"		=> 1,
			"form_action"	=> "./?object=".$_GET["object"]."&action=edit&id=".$A["id"],
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
		// Do delete record
		if ($_GET["id"]) {
			db()->query("DELETE FROM `".db('user_data_info_fields')."` WHERE `id`=".$_GET["id"]);
			db()->query("DELETE FROM `".db('user_data_info_values')."` WHERE `field_id` =".$_GET["id"]);

			$this->_refresh_cache();
		}
		// Return user back
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo $_GET["id"];
		} else {
			return js_redirect($_SERVER["HTTP_REFERER"], 0);
		}
	}

	/*
	* Do delete record (mass method)
	*/
	function mass_delete () {
		$ids_to_delete = array();
		// Prepare ids to delete
		foreach ((array)$_POST["items_to_delete"] as $_cur_id) {
			if (empty($_cur_id)) {
				continue;
			}
			$ids_to_delete[$_cur_id] = $_cur_id;
		}
		// Do delete ids
		if (!empty($ids_to_delete)) {
			db()->query("DELETE FROM `".db('user_data_info_fields')."` WHERE `id` IN(".implode(",",$ids_to_delete).")");
			db()->query("DELETE FROM `".db('user_data_info_values')."` WHERE `field_id` IN(".implode(",",$ids_to_delete).")");

			$this->_refresh_cache();
		}
		// Return user back
		return js_redirect($_SERVER["HTTP_REFERER"], 0);
	}

	/**
	* Activate\deactivate fields
	*/
	function activate () {
		$_GET["id"] = intval($_GET["id"]);
		if ($_GET["id"]) {
			list($_active) = db()->query_fetch("SELECT `active` AS `0` FROM `".db('user_data_info_fields')."` WHERE `id`=".intval($_GET["id"]));
			if ($_active == 0) {
				$_set_active = 1;
			} else {
				$_set_active = 0;
			}
			$sql_array = array(
				"active" => $_set_active,
			);
			db()->UPDATE("user_data_info_fields", $sql_array, "`id`=".intval($_GET["id"]));

			$this->_refresh_cache();
		}
		// Return user back
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo ($_set_active);
		} else {
			return js_redirect("./?object=".$_GET["object"]);
		}
	}

	/**
	* Do refresh system cache related to this module
	*/
	function _refresh_cache () {
		if (!main()->USE_SYSTEM_CACHE) {
			return false;
		}
		cache()->refresh("user_dynamic_fields");
		cache()->refresh("dynamic_fields");
		cache()->refresh("fields_map_simple");
		cache()->refresh("fields_map_dynamic");
	}

	//-----------------------------------------------------------------------------
	// Process custom box
	function _box ($name = "", $selected = "") {
		if (empty($name) || empty($this->_boxes[$name])) return false;
		else return eval("return common()->".$this->_boxes[$name].";");
	}

	/**
	* Quick menu auto create
	*/
	function _quick_menu () {
		$menu = array(
			array(
				"name"	=> ucfirst($_GET["object"])." main",
				"url"	=> "./?object=".$_GET["object"],
			),
			array(
				"name"	=> "Add field",
				"url"	=> "./?object=".$_GET["object"]."&action=add",
			),
			array(
				"name"	=> "",
				"url"	=> "./?object=".$_GET["object"],
			),
		);
		return $menu;	
	}

	/**
	* Page header hook
	*/
	function _show_header() {
		$pheader = t("User info data constructor");
		// Default subheader get from action name
		$subheader = _ucwords(str_replace("_", " ", $_GET["action"]));

		// Array of replacements
		$cases = array (
			//$_GET["action"] => {string to replace}
			"show"					=> "",
		);			 		
		if (isset($cases[$_GET["action"]])) {
			// Rewrite default subheader
			$subheader = $cases[$_GET["action"]];
		}

		return array(
			"header"	=> $pheader,
			"subheader"	=> $subheader ? _prepare_html($subheader) : "",
		);
	}
}
