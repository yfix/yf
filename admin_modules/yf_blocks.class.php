<?php

/**
* Blocks editor
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_blocks {

	/**
	* Constructor
	*/
	function _init () {
		// Array of select boxes to process
		$this->_boxes = array(
			"active"		=> 'radio_box("active",			$this->_statuses,		$selected, false, 2, "", false)',
			"rule_type"		=> 'radio_box("rule_type",		$this->_rule_types,		$selected, false, 2, "", false)',
			"methods"		=> 'multi_select("methods",		$this->_methods,		$selected, false, 2, " size=20 class=small_for_select ", false)',
			"user_groups"	=> 'multi_select("user_groups",	$this->_groups,			$selected, false, 2, " size=5 class=small_for_select ", false)',
			"themes"		=> 'multi_select("themes",		$this->_themes,			$selected, false, 2, " size=5 class=small_for_select ", false)',
			"locales"		=> 'multi_select("locales",		$this->_locales,		$selected, false, 2, " size=5 class=small_for_select ", false)',
			"site_ids"		=> 'multi_select("site_ids",	$this->_sites,			$selected, false, 2, " size=5 class=small_for_select ", false)',
			"server_ids"	=> 'multi_select("server_ids",	$this->_servers,		$selected, false, 2, " size=5 class=small_for_select ", false)',
		);
		// Array of statuses
		$this->_statuses = array(
			"0" => "<span class='negative'>NO</span>",
			"1" => "<span class='positive'>YES</span>",
		);
		// Array of rule types
		$this->_rule_types = array(
			"DENY"	=> "<span class='negative'>DENY</span>",
			"ALLOW"	=> "<span class='positive'>ALLOW</span>",
		);
		// Get user modules
		$this->_user_modules = main()->_execute("user_modules", "_get_modules");
		// Get user methods groupped by modules
		$this->_user_modules_methods = main()->_execute("user_modules", "_get_methods");
		$this->_user_methods[""] = "-- ALL --";
		// Prepare methods
		foreach ((array)$this->_user_modules_methods as $module_name => $module_methods) {
			$this->_user_methods[$module_name] = $module_name." -> -- ALL --";
			foreach ((array)$module_methods as $method_name) {
				if ($method_name == $module_name) {
					continue;
				}
				$this->_user_methods[$module_name.".".$method_name] = _prepare_html($module_name." -> ".$method_name);
			}
		}
		// Get user groups
		$this->_user_groups[""] = "-- ALL --";
		$Q = db()->query("SELECT `id`,`name` FROM `".db('user_groups')."` WHERE `active`='1'");
		while ($A = db()->fetch_assoc($Q)) {
			$this->_user_groups[$A['id']] = $A['name'];
		}
		// Get admin modules
		$this->_admin_modules = main()->_execute("admin_modules", "_get_modules");
		// Get admin methods groupped by modules
		$this->_admin_modules_methods = main()->_execute("admin_modules", "_get_methods");
		$this->_admin_methods[""] = "-- ALL --";
		// Prepare methods
		foreach ((array)$this->_admin_modules_methods as $module_name => $module_methods) {
			$this->_admin_methods[$module_name] = $module_name." -> -- ALL --";
			foreach ((array)$module_methods as $method_name) {
				if ($method_name == $module_name) {
					continue;
				}
				$this->_admin_methods[$module_name.".".$method_name] = _prepare_html($module_name." -> ".$method_name);
			}
		}
		// Get admin groups
		$this->_admin_groups[""] = "-- ALL --";
		$Q = db()->query("SELECT `id`,`name` FROM `".db('admin_groups')."` WHERE `active`='1'");
		while ($A = db()->fetch_assoc($Q)) {
			$this->_admin_groups[$A['id']] = $A['name'];
		}
		// Get available themes
		$this->_themes = array("" => "-- ALL --");
		if ($this->_admin_modules["template_editor"]) {
			foreach ((array)main()->_execute("template_editor", "_get_themes_names") as $_location => $_themes) {
				foreach ((array)$_themes as $_theme) {
					$this->_themes[$_theme] = $_theme;
				}
			}
		}
		// Get availaible locales
		$this->_locales = my_array_merge(
			array("" => "-- ALL --")
			,$this->_admin_modules["locale_editor"] ? main()->_execute("locale_editor", "_get_locales") : array()
		);
		// Get sites
		$this->_sites = array(
			"" => "-- ALL --",
		);
		$Q = db()->query("SELECT `id`,`name` FROM `".db('sites')."` WHERE `active`='1'");
		while ($A = db()->fetch_assoc($Q)) {
			$this->_sites[$A['id']] = $A['name'];
		}
		// Get servers
		$this->_servers = array(
			"" => "-- ALL --",
		);
		$Q = db()->query("SELECT `id`,`name` FROM `".db('core_servers')."` WHERE `active`='1'");
		while ($A = db()->fetch_assoc($Q)) {
			$this->_servers[$A['id']] = $A['name'];
		}
	}

	/**
	* Default method
	*/
	function show () {
		// Get rules groupped by block_id
		$Q = db()->query("SELECT `block_id`, COUNT(*) AS `num` FROM `".db('block_rules')."` GROUP BY `block_id`");
		while ($A = db()->fetch_assoc($Q)) {
			$num_rules[$A["block_id"]] = $A["num"];
		}
		// Get available blocks from db
		$Q = db()->query("SELECT * FROM `".db('blocks')."`");
		while ($A = db()->fetch_assoc($Q)) {
			$replace2 = array(
				"bg_class"		=> !(++$i % 2) ? "bg1" : "bg2",
				"name"			=> _prepare_html($A["name"]),
				"desc"			=> _prepare_html($A["desc"]),
				"type"			=> _prepare_html($A["type"]),
				"stpl_name"		=> _prepare_html($A["stpl_name"]),
				"method_name"	=> _prepare_html($A["method_name"]),
				"active"		=> intval($A["active"]),
				"num_rules"		=> intval($num_rules[$A["id"]]),
				"edit_link"		=> "./?object=".$_GET["object"]."&action=edit&id=".$A["id"],
				"delete_link"	=> "./?object=".$_GET["object"]."&action=delete&id=".$A["id"],
				"clone_link"	=> "./?object=".$_GET["object"]."&action=clone_block&id=".$A["id"],
				"active_link"	=> "./?object=".$_GET["object"]."&action=activate_block&id=".$A["id"],
				"rules_link"	=> "./?object=".$_GET["object"]."&action=show_rules&id=".$A["id"],
				"export_link"	=> "./?object=".$_GET["object"]."&action=export&id=".$A["id"],
			);
			$items .= tpl()->parse($_GET["object"]."/item", $replace2);
		}
		// Prepare template
		$replace = array(
			"items"			=> $items,
			"form_action"	=> "./?object=".$_GET["object"]."&action=add",
		);
		return tpl()->parse($_GET["object"]."/main", $replace);
	}

	/**
	* Edit block info
	*/
	function add () {
		// Save form
		if (!empty($_POST)) {
			// CHeck block type
			if (empty($_POST["type"]) || !in_array($_POST["type"], array("user","admin"))) {
				_re(t("Wrong block type"));
			}
			// Check block name (it must be non-empty and unique for selected type)
			if (empty($_POST["name"])) {
				_re(t("BLock name can not be empty"));
			}
			if (!common()->_error_exists()) {
				if (db()->query_num_rows("SELECT `id` FROM `".db('blocks')."` WHERE `type`='"._es($_POST["type"])."' AND `name`='"._es($_POST["name"])."'")) {
					_re(t("BLock name already reserved for type=@name", array("@name" => $_POST["type"])));
				}
			}
			// Do save data
			if (!common()->_error_exists()) {
				// Do insert record
				db()->INSERT("blocks", array(
					"name"		=> _es($_POST["name"]),
					"desc"		=> _es($_POST["desc"]),
					"type"		=> _es($_POST["type"] == "admin" ? "admin" : "user"),
					"active"	=> intval($_POST["active"]),
				));
				// Refresh system cache
				if (main()->USE_SYSTEM_CACHE)	{
					cache()->refresh("blocks_names");
				}
				// Return user back
				return js_redirect("./?object=".$_GET["object"]);
			}
		}
		// Prepare data
		$DATA = $_POST;
		// Process template
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"]."&action=".$_GET["action"],
			"for_edit"		=> 0,
			"error_message"	=> _e(),
			"name"			=> _prepare_html($DATA["name"]),
			"desc"			=> _prepare_html($DATA["desc"]),
			"stpl_name"		=> _prepare_html($DATA["stpl_name"]),
			"method_name"	=> _prepare_html($DATA["method_name"]),
			"active"		=> $DATA["active"],
			"active_box"	=> $this->_box("active", $DATA["active"]),
			"back_link"		=> "./?object=".$_GET["object"],
		);
		return tpl()->parse($_GET["object"]."/edit_block", $replace);
	}

	/**
	* Edit block info
	*/
	function edit () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return _e(t("No id!"));
		}
		// Get current block info
		$block_info = db()->query_fetch("SELECT * FROM `".db('blocks')."` WHERE `id`=".intval($_GET["id"]));
		if (empty($block_info["id"])) {
			return _e(t("No such block!"));
		}
		// Prepare data
		$DATA = $block_info;
		foreach ((array)$_POST as $k => $v) {
			if (isset($DATA[$k])) {
				$DATA[$k] = $v;
			}
		}
		// Save form
		if (!empty($_POST)) {
			// Check block name (it must be non-empty and unique for selected type)
			if (empty($_POST["name"])) {
				_re(t("BLock name can not be empty"));
			}
			if (!common()->_error_exists()) {
				if (db()->query_num_rows("SELECT `id` FROM `".db('blocks')."` WHERE `type`='"._es($_POST["type"])."' AND `name`='"._es($_POST["name"])."'")) {
					_re(t("BLock name already reserved for type=@name", array("@name" => $_POST["type"])));
				}
			}
			// Do save data
			if (!common()->_error_exists()) {
				// Do update record
				db()->UPDATE("blocks", array(
					"name"			=> _es($_POST["name"]),
					"desc"			=> _es($_POST["desc"]),
					"stpl_name"		=> _es($_POST["stpl_name"]),
					"method_name"	=> _es($_POST["method_name"]),
					"active"		=> intval($_POST["active"])
				), "`id`=".intval($_GET["id"]));
				// Refresh system cache
				if (main()->USE_SYSTEM_CACHE)	{
					cache()->refresh("blocks_names");
				}
				// Return user back
				return js_redirect("./?object=".$_GET["object"]);
			}
		}
		$methods_for_select = $block_info["type"] == "admin" ? $this->_admin_methods : $this->_user_methods;
		if (isset($methods_for_select[""])) {
			unset($methods_for_select[""]);
		}
		// Process template
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$_GET["id"],
			"for_edit"		=> 1,
			"error_message"	=> _e(),
			"name"			=> _prepare_html($block_info["name"]),
			"desc"			=> _prepare_html($block_info["desc"]),
			"stpl_name"		=> _prepare_html($block_info["stpl_name"]),
			"method_name"	=> _prepare_html($block_info["method_name"]),
			"active"		=> $block_info["active"],
			"active_box"	=> $this->_box("active", $block_info["active"]),
			"methods_box"	=> common()->select_box("methods", $methods_for_select, "", true, 2, "class=small_for_select", false),
			"stpls_box"		=> common()->select_box("stpls", $this->_get_stpls($block_info["type"]), "", true, 2, "class=small_for_select", false),
			"modules_link"	=> "./?object=".$block_info["type"]."_modules",
			"stpls_link"	=> "./?object=template_editor",
			"back_link"		=> "./?object=".$_GET["object"],
		);
		return tpl()->parse($_GET["object"]."/edit_block", $replace);
	}

	/**
	* Get array of templates for the given init type
	*/
	function _get_stpls ($type = "user") {
		return module("template_editor")->_get_stpls_for_type($type);
	}

	/**
	* Delete block and its rules
	*/
	function delete () {
		$_GET["id"] = intval($_GET["id"]);
		// Get current block info
		if (!empty($_GET["id"])) {
			$block_info = db()->query_fetch("SELECT * FROM `".db('blocks')."` WHERE `id`=".intval($_GET["id"]));
		}
		// Do delete block and its rules
		if (!empty($block_info["id"])) {
			db()->query("DELETE FROM `".db('blocks')."` WHERE `id`=".intval($_GET["id"])." LIMIT 1");
			db()->query("DELETE FROM `".db('block_rules')."` WHERE `block_id`=".intval($_GET["id"]));
		}
		// Refresh system cache
		if (main()->USE_SYSTEM_CACHE)	{
			cache()->refresh("blocks_names");
			cache()->refresh("blocks_rules");
		}
		// Return user back
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo $_GET["id"];
		} else {
			return js_redirect("./?object=".$_GET["object"]);
		}
	}

	/**
	* Clone block
	*/
	function clone_block () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return _e(t("No id!"));
		}
		$block_info = db()->query_fetch("SELECT * FROM `".db('blocks')."` WHERE `id`=".intval($_GET["id"]));
		// Prepare SQL
		$sql = $block_info;
		unset($sql["id"]);
		$sql["name"] = $sql["name"]."_clone";
		// Do clone menu record
		db()->INSERT("blocks", $sql);
		$NEW_BLOCK_ID = db()->INSERT_ID();
		// Do clone rules
		$Q = db()->query("SELECT * FROM `".db('block_rules')."` WHERE `block_id`=".intval($_GET["id"]));
		while ($_info = db()->fetch_assoc($Q)) {
			unset($_info["id"]);
			$_info["block_id"] = $NEW_BLOCK_ID;

			db()->INSERT("block_rules", $_info);

			$NEW_ITEM_ID = db()->INSERT_ID();
		}
		// Refresh system cache
		if (main()->USE_SYSTEM_CACHE)	{
			cache()->refresh("blocks_names");
			cache()->refresh("blocks_rules");
		}
		return js_redirect("./?object=".$_GET["object"]);
	}

	/**
	* Change block activity status
	*/
	function activate_block () {
		$_GET["id"] = intval($_GET["id"]);
		// Get current rule info
		if (!empty($_GET["id"])) {
			$block_info = db()->query_fetch("SELECT * FROM `".db('blocks')."` WHERE `id`=".intval($_GET["id"]));
		}
		// Change activity
		if (!empty($block_info["id"])) {
			db()->UPDATE("blocks", array("active" => (int)!$block_info["active"]), "`id`=".intval($_GET["id"]));
		}
		// Refresh system cache
		if (main()->USE_SYSTEM_CACHE)	{
			cache()->refresh("blocks_names");
			cache()->refresh("blocks_rules");
		}
		// Return user back
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo ($block_info["active"] ? 0 : 1);
		} else {
			return js_redirect("./?object=".$_GET["object"]);
		}
	}

	/**
	* Rules list for given block id
	*/
	function show_rules () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return _e(t("No id!"));
		}
		// Get current block info
		$block_info = db()->query_fetch("SELECT * FROM `".db('blocks')."` WHERE `id`=".intval($_GET["id"]));
		if (empty($block_info["id"])) {
			return _e(t("No such block!"));
		}
		// Switch between arrays for admin or user
		if ($block_info["type"] == "admin") {
			$this->_groups	= &$this->_admin_groups;
			$this->_methods = &$this->_admin_methods;
		} else {
			$this->_groups	= &$this->_user_groups;
			$this->_methods = &$this->_user_methods;
		}
		// Get rules for the current block
		$Q = db()->query("SELECT * FROM `".db('block_rules')."` WHERE `block_id`=".intval($_GET["id"]));
		while ($A = db()->fetch_assoc($Q)) {
			// Process template
			$replace2 = array(
				"bg_class"		=> !(++$i % 2) ? "bg1" : "bg2",
				"rule_type"		=> _prepare_html($A["rule_type"]),
				"user_groups"	=> $this->_multi_db_to_show($A["user_groups"],	$this->_groups),
				"methods"		=> $this->_multi_db_to_show($A["methods"],		$this->_methods),
				"themes"		=> $this->_multi_db_to_show($A["themes"],		$this->_themes),
				"locales"		=> $this->_multi_db_to_show($A["locales"],		$this->_locales),
				"site_ids"		=> $this->_multi_db_to_show($A["site_ids"],		$this->_sites),
				"server_ids"	=> $this->_multi_db_to_show($A["server_ids"],	$this->_servers),
				"active"		=> intval($A["active"]),
				"order"			=> intval($A["order"]),
				"edit_link"		=> "./?object=".$_GET["object"]."&action=edit_rule&id=".$A["id"],
				"delete_link"	=> "./?object=".$_GET["object"]."&action=delete_rule&id=".$A["id"],
				"clone_link"	=> "./?object=".$_GET["object"]."&action=clone_rule&id=".$A["id"],
				"active_link"	=> $block_info["name"] == "center_area" && $block_info["type"] == "admin" ? "" : "./?object=".$_GET["object"]."&action=activate_rule&id=".$A["id"],
			);
			$items .= tpl()->parse($_GET["object"]."/rules_item", $replace2);
		}
		// Process template
		$replace = array(
			"items"			=> $items,
			"block_name"	=> $block_info["name"],
			"block_type"	=> $block_info["type"] == "admin" ? "admin" : "user",
			"add_rule_link"	=> "./?object=".$_GET["object"]."&action=add_rule&id=".$_GET["id"],
			"back_link"		=> "./?object=".$_GET["object"]."&action=show",
			"modules_link"	=> "./?object=".($block_info["type"] == "admin" ? "admin_modules" : "user_modules"),
			"groups_link"	=> "./?object=".($block_info["type"] == "admin" ? "admin_groups" : "user_groups"),
//			"themes_link"	=> "./?object=design_manager",
			"themes_link"	=> "./?object=template_editor",
			"locales_link"	=> "./?object=locale_editor",
			"sites_link"	=> "./?object=db_parser&table=sys_sites",
			"servers_link"	=> "./?object=db_parser&table=sys_core_servers",
		);
		return tpl()->parse($_GET["object"]."/rules_main", $replace);
	}

	/**
	* Add rule form
	*/
	function add_rule () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return _e(t("No id!"));
		}
		// Get current block info
		$block_info = db()->query_fetch("SELECT * FROM `".db('blocks')."` WHERE `id`=".intval($_GET["id"]));
		if (empty($block_info["id"])) {
			return _e(t("No such block!"));
		}
		// Switch between arrays for admin or user
		if ($block_info["type"] == "admin") {
			$this->_groups	= &$this->_admin_groups;
			$this->_methods = &$this->_admin_methods;
		} else {
			$this->_groups	= &$this->_user_groups;
			$this->_methods = &$this->_user_methods;
		}
		// Save form
		if (!empty($_POST)) {
			// Do save data
			if (!common()->_error_exists()) {
				// Do insert record
				db()->INSERT("block_rules", array(
					"block_id"		=> intval($_GET["id"]),
					"rule_type"		=> $_POST["rule_type"] == "ALLOW" ? "ALLOW" : "DENY",

					"methods"		=> _es($this->_multi_html_to_db($_POST["methods"])),
					"user_groups"	=> _es($this->_multi_html_to_db($_POST["user_groups"])),
					"themes"		=> _es($this->_multi_html_to_db($_POST["themes"])),
					"locales"		=> _es($this->_multi_html_to_db($_POST["locales"])),
					"site_ids"		=> _es($this->_multi_html_to_db($_POST["site_ids"])),
					"server_ids"	=> _es($this->_multi_html_to_db($_POST["server_ids"])),

					"order"			=> intval($_POST["order"]),
					"active"		=> intval($_POST["active"]),
				));
				// Refresh system cache
				if (main()->USE_SYSTEM_CACHE)	{
					cache()->refresh("blocks_rules");
				}
				// Return user back
				return js_redirect("./?object=".$_GET["object"]."&action=show_rules&id=".$block_info["id"]);
			}
		}
		// Prepare data
		$DATA = $_POST;

		foreach (array("methods", "user_groups", "themes", "locales", "site_ids", "server_ids") as $k) {
			$DATA[$k] = $this->_multi_db_to_html($DATA[$k]);
		}

		// Show template contents
		$replace = array(
			"form_action"		=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$_GET["id"],
			"for_edit"			=> 0,
			"error_message"		=> _e(),
			"block_name"		=> _prepare_html($block_info["name"]),
			"order"				=> intval($DATA["order"]),
			"rule_type"			=> $DATA["rule_type"],
			"rule_type_box"		=> $this->_box("rule_type",		"DENY"),
			"methods_box"		=> $this->_box("methods",		"-- ALL --"),
			"user_groups_box"	=> $this->_box("user_groups",	array(""=>"-- ALL --")),
			"themes_box"		=> $this->_box("themes",		array(""=>"-- ALL --")),
			"locales_box"		=> $this->_box("locales",		array(""=>"-- ALL --")),
			"site_ids_box"		=> $this->_box("site_ids",		array(""=>"-- ALL --")),
			"server_ids_box"	=> $this->_box("server_ids",	array(""=>"-- ALL --")),
			"active_box"		=> $this->_box("active", 		$DATA["active"]),
			"active"			=> $DATA["active"],
			"back_link"			=> "./?object=".$_GET["object"]."&action=show_rules&id=".intval($block_info["id"]),
			"modules_link"		=> "./?object=".($block_info["type"] == "admin" ? "admin_modules" : "user_modules"),
			"groups_link"		=> "./?object=".($block_info["type"] == "admin" ? "admin_groups" : "user_groups"),
			"themes_link"		=> "./?object=template_editor",
			"locales_link"		=> "./?object=locale_editor",
			"sites_link"		=> "./?object=db_parser&table=sys_sites",
			"servers_link"		=> "./?object=db_parser&table=sys_core_servers",
		);
		return tpl()->parse($_GET["object"]."/rule_edit_form", $replace);
	}

	/**
	* Edit rule form
	*/
	function edit_rule () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return _e(t("No id!"));
		}
		// Get current rule info
		$rule_info = db()->query_fetch("SELECT * FROM `".db('block_rules')."` WHERE `id`=".intval($_GET["id"]));
		if (empty($rule_info["id"])) {
			return _e(t("No such rule!"));
		}
		// Get current block info
		$block_info = db()->query_fetch("SELECT * FROM `".db('blocks')."` WHERE `id`=".intval($rule_info["block_id"]));
		if (empty($block_info["id"])) {
			return _e(t("No such block!"));
		}
		// Switch between arrays for admin or user
		if ($block_info["type"] == "admin") {
			$this->_groups	= &$this->_admin_groups;
			$this->_methods = &$this->_admin_methods;
		} else {
			$this->_groups	= &$this->_user_groups;
			$this->_methods = &$this->_user_methods;
		}
		// Save form
		if (!empty($_POST)) {
			// Do save data
			if (!common()->_error_exists()) {
				// Do update record
				db()->UPDATE("block_rules", array(
					"rule_type"		=> $_POST["rule_type"] == "ALLOW" ? "ALLOW" : "DENY",
					"methods"		=> _es($this->_multi_html_to_db($_POST["methods"])),
					"user_groups"	=> _es($this->_multi_html_to_db($_POST["user_groups"])),
					"themes"		=> _es($this->_multi_html_to_db($_POST["themes"])),
					"locales"		=> _es($this->_multi_html_to_db($_POST["locales"])),
					"site_ids"		=> _es($this->_multi_html_to_db($_POST["site_ids"])),
					"server_ids"	=> _es($this->_multi_html_to_db($_POST["server_ids"])),
					"order"			=> intval($_POST["order"]),
					"active"		=> intval($_POST["active"]),
				), "`id`=".intval($_GET["id"]));
				// Refresh system cache
				if (main()->USE_SYSTEM_CACHE)	{
					cache()->refresh("blocks_rules");
				}
				// Return user back
				return js_redirect("./?object=".$_GET["object"]."&action=show_rules&id=".$block_info["id"]);
			}
		}
		// Prepare data
		$DATA = $rule_info;
		foreach ((array)$_POST as $k => $v) {
			if (isset($DATA[$k])) {
				$DATA[$k] = $v;
			}
		}
		foreach (array("methods", "user_groups", "themes", "locales", "site_ids", "server_ids") as $k) {
			$DATA[$k] = $this->_multi_db_to_html($DATA[$k]);
		}
		// Process template
		$replace = array(
			"form_action"		=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$_GET["id"],
			"for_edit"			=> 1,
			"error_message"		=> _e(),
			"block_name"		=> _prepare_html($block_info["name"]),
			"order"				=> intval($DATA["order"]),
			"rule_type"			=> $DATA["rule_type"],
			"rule_type_box"		=> $this->_box("rule_type",		$DATA["rule_type"]),
			"methods_box"		=> $this->_box("methods",		$DATA["methods"]),
			"user_groups_box"	=> $this->_box("user_groups",	$DATA["user_groups"]),
			"themes_box"		=> $this->_box("themes",		$DATA["themes"]),
			"locales_box"		=> $this->_box("locales",		$DATA["locales"]),
			"site_ids_box"		=> $this->_box("site_ids",		$DATA["site_ids"]),
			"server_ids_box"	=> $this->_box("server_ids",	$DATA["server_ids"]),
			"active_box"		=> $this->_box("active", 		$DATA["active"]),
			"active"			=> $DATA["active"],
			"back_link"			=> "./?object=".$_GET["object"]."&action=show_rules&id=".intval($block_info["id"]),
			"modules_link"		=> "./?object=".($block_info["type"] == "admin" ? "admin_modules" : "user_modules"),
			"groups_link"		=> "./?object=".($block_info["type"] == "admin" ? "admin_groups" : "user_groups"),
			"themes_link"		=> "./?object=template_editor",
			"locales_link"		=> "./?object=locale_editor",
			"sites_link"		=> "./?object=db_parser&table=sys_sites",
			"servers_link"		=> "./?object=db_parser&table=sys_core_servers",
		);
		return tpl()->parse($_GET["object"]."/rule_edit_form", $replace);
	}

	/**
	* Delete single rule
	*/
	function delete_rule () {
		$_GET["id"] = intval($_GET["id"]);
		// Get current rule info
		if (!empty($_GET["id"])) {
			$rule_info = db()->query_fetch("SELECT * FROM `".db('block_rules')."` WHERE `id`=".intval($_GET["id"]));
		}
		// Get current block info
		if (!empty($rule_info["id"])) {
			$block_info = db()->query_fetch("SELECT * FROM `".db('blocks')."` WHERE `id`=".intval($rule_info["block_id"]));
		}
		// Do delete rule
		if (!empty($block_info["id"])) {
			db()->query("DELETE FROM `".db('block_rules')."` WHERE `id`=".intval($_GET["id"])." LIMIT 1");
		}
		// Refresh system cache
		if (main()->USE_SYSTEM_CACHE)	{
			cache()->refresh("blocks_rules");
		}
		// Return user back
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo $_GET["id"];
		} else {
			return js_redirect("./?object=".$_GET["object"]."&action=show_rules&id=".$block_info["id"]);
		}
	}

	/**
	* Clone block rule
	*/
	function clone_rule () {
		$_GET["id"] = intval($_GET["id"]);
		// Get current rule info
		if (!empty($_GET["id"])) {
			$rule_info = db()->query_fetch("SELECT * FROM `".db('block_rules')."` WHERE `id`=".intval($_GET["id"]));
		}
		// Get current block info
		if (!empty($rule_info["id"])) {
			$block_info = db()->query_fetch("SELECT * FROM `".db('blocks')."` WHERE `id`=".intval($rule_info["block_id"]));
		}
		if (!$block_info) {
			return _e("No such rule or block");
		}
		// Prepare SQL
		$sql = $rule_info;
		unset($sql["id"]);
		// Do clone menu record
		db()->INSERT("block_rules", $sql);
		$NEW_RULE_ID = db()->INSERT_ID();
		// Refresh system cache
		if (main()->USE_SYSTEM_CACHE)	{
			cache()->refresh("blocks_names");
			cache()->refresh("blocks_rules");
		}
		return js_redirect("./?object=".$_GET["object"]."&action=show_rules&id=".$block_info["id"]);
	}

	/**
	* Change rule activity status
	*/
	function activate_rule () {
		$_GET["id"] = intval($_GET["id"]);
		// Get current rule info
		if (!empty($_GET["id"])) {
			$rule_info = db()->query_fetch("SELECT * FROM `".db('block_rules')."` WHERE `id`=".intval($_GET["id"]));
		}
		// Get current block info
		if (!empty($rule_info["id"])) {
			$block_info = db()->query_fetch("SELECT * FROM `".db('blocks')."` WHERE `id`=".intval($rule_info["block_id"]));
		}
		// Change rule activity
		if (!empty($block_info["id"])) {
			db()->UPDATE("block_rules", array("active" => (int)!$rule_info["active"]), "`id`=".intval($_GET["id"]));
		}
		// Refresh system cache
		if (main()->USE_SYSTEM_CACHE)	{
			cache()->refresh("blocks_rules");
		}
		// Return user back
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo ($rule_info["active"] ? 0 : 1);
		} else {
			return js_redirect("./?object=".$_GET["object"]."&action=show_rules&id=".$block_info["id"]);
		}
	}

	/**
	* Export blocks items
	*/
	function export() {
		// If no ID set - mean that simply export all blocks with rules
		$_GET["id"] = intval($_GET["id"]);
		// Get current block info
		if ($_GET["id"]) {
			$block_info = db()->query_fetch("SELECT * FROM `".db('blocks')."` WHERE `id`=".intval($_GET["id"]));
		}
		// Prepare db export params
		$params = array(
			"single_table"	=> "",
			"tables"		=> array(db('blocks'), db('block_rules')),
			"full_inserts"	=> 1,
			"ext_inserts"	=> 1,
			"export_type"	=> "insert",
			"silent_mode"	=> true,
		);
		if ($block_info["id"]) {
			$params["where"] = array(
				db('blocks')		=> "`id`=".intval($block_info["id"]),
				db('block_rules')	=> "`block_id`=".intval($block_info["id"]),
			);
		}
		$EXPORTED_SQL = module("db_manager")->export($params);
		// Pretty show result
		$replace = array(
			"sql_text"	=> _prepare_html($EXPORTED_SQL, 0),
			"back_link"	=> "./?object=".$_GET["object"],
		);
		return tpl()->parse("db_manager/export_text_result", $replace);
	}

	/**
	* Cleanup methods for save them in db
	*/
	function _cleanup_methods_for_save ($methods_array = array()) {
		if (!is_array($methods_array) || empty($methods_array)) {
			return false;
		}
		// Prepare array
		sort($methods_array);
		$cur_top_level_methods	= array();
		$methods_for_save		= array();
		// Try to compact rules
		foreach ((array)$methods_array as $method_full_name) {
			// Verify method name
			if (empty($method_full_name) || !isset($this->_methods[$method_full_name])) {
				continue;
			}
			// Add top level method ("-- ALL --" methods for module)
			if (false === strpos($method_full_name, ".")) {
				$cur_top_level_methods[$method_full_name] = $method_full_name;
			}
			// Skip methods if top level is set
			if ((false !== strpos($method_full_name, ".")) && isset($cur_top_level_methods[substr($method_full_name, 0, strrpos($method_full_name, "."))])) {
				continue;
			}
			// Do add method for save
			$methods_for_save[$method_full_name] = $method_full_name;
		}
		ksort($methods_for_save);
		// Cleanup methods
		$methods_array	= implode(",", (array)$methods_for_save);
		return str_replace(array(" ","\t","\r","\n"), "", $methods_array);
	}

	/**
	*
	*/
	function _multi_html_to_db($input = array()) {
		if (is_array($input)) {
			$input = ",".implode(",", $input).",";
		}
		return (string)str_replace(array(" ","\t","\r","\n",",,"), "", $input);
	}

	/**
	*
	*/
	function _multi_db_to_html($input = "") {
		if (!is_array($input)) {
			$input	= explode(",",str_replace(array(" ","\t","\r","\n",",,"), "", $input));
		}
		$output = array();
		foreach ((array)$input as $v) {
			if ($v) {
				$output[$v] = $v;
			}
		}
		return (array)$output;
	}

	/**
	*
	*/
	function _multi_db_to_show($input = "", $names = array()) {
		$output = array();
		if (is_array($input)) {
			$input = ",".implode(",", $input).",";
		}
		foreach (explode(",",trim($input,",")) as $k => $v) {
			if (empty($names[$v])) {
				continue;
			}
			$output[$v] = $names[$v];
		}
		$output = implode("<br />\n", $output);
		if (empty($output)) {
			$output	= "-- ALL --";
		}
		return $output;
	}

	/**
	* Process custom box
	*/
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
		);
		return $menu;	
	}

	/**
	* Page header hook
	*/
	function _show_header() {
		$pheader = t("Blocks editor");
		// Default subheader get from action name
		$subheader = _ucwords(str_replace("_", " ", $_GET["action"]));

		// Array of replacements
		$cases = array (
			//$_GET["action"] => {string to replace}
			"show"					=> "All blocks list",
			"show_rules"			=> "",
			"add_rule"				=> "",
			"edit_rule"				=> "",
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
