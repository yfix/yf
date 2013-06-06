<?php

/**
* Custom replace tags and rules editor
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_custom_replace {

	/**
	* Constructor (PHP 4.x)
	*/
	function yf_custom_replace () {
		return $this->__construct();
	}

	/**
	* Constructor (PHP 5.x)
	*/
	function __construct () {
		$this->_std_trigger = array(
			"1" => "<span class='positive'>YES</span>",
			"0" => "<span class='negative'>NO</span>",
		);
		// Array of select boxes to process
		$this->_boxes = array(
			"active"		=> 'radio_box("active",			$this->_std_trigger,	$selected, false, 2, "", false)',
			"eval_code"		=> 'radio_box("eval_code",		$this->_std_trigger,	$selected, false, 2, "", false)',
			"sites"			=> 'select_box("site_id",		$this->_sites_names,	$selected, false, 2, "", false)',
			"langs"			=> 'select_box("language",		$this->_langs,			$selected, false, 2, "", false)',
			"words"			=> 'select_box("words",			$this->_words,			$selected, false, 1, "", false)',
			"methods"		=> 'multi_select("methods",		$this->_user_methods,	$selected, false, 2, " size=10 class=small_for_select ", false)',
			"user_groups"	=> 'multi_select("user_groups",	$this->_user_groups,	$selected, false, 2, " size=7 ", false)',
		);
		// Array of statuses
		$this->_statuses = array(
			"0" => "<span class='negative'>NO</span>",
			"1" => "<span class='positive'>YES</span>",
		);
		// Get user modules
		$this->_user_modules = main()->_execute("user_modules", "_get_modules");
		// Get sites infos
		$this->_sites_names[""] = "-- ALL --";
		$Q = db()->query("SELECT * FROM `".db('sites')."` WHERE `active`='1' ORDER BY `id` ASC");
		while ($A = db()->fetch_assoc($Q)) $this->_sites_names[$A["id"]] = $A["name"];
		// Get langs
		$this->_langs[""] = "-- ALL --";
		foreach ((array)conf('languages') as $lang_info) {
			$this->_langs[$lang_info["locale"]] = $lang_info["name"];
		}
		// Get available replacement words
		$this->_words = main()->get_data("custom_replace_words");
		foreach ((array)$this->_words as $k => $v) $this->_words[$k] = $k;
		// Get user modules
		$this->_user_modules = main()->_execute("user_modules", "_get_modules");
		// Get user methods groupped by modules
		$this->_user_modules_methods = main()->_execute("user_modules", "_get_methods");
		$this->_user_methods[""] = "-- ALL --";
		// Prepare methods
		foreach ((array)$this->_user_modules_methods as $module_name => $module_methods) {
			$this->_user_methods[$module_name] = $module_name." -> -- ALL --";
			foreach ((array)$module_methods as $method_name) {
				if ($method_name == $module_name) continue;
				$this->_user_methods[$module_name.".".$method_name] = _prepare_html($module_name." -> ".$method_name);
			}
		}
		// Get user groups
		$this->_user_groups[""] = "-- ALL --";
		$Q = db()->query("SELECT `id`,`name` FROM `".db('user_groups')."` WHERE `active`='1'");
		while ($A = db()->fetch_assoc($Q)) $this->_user_groups[$A['id']] = $A['name'];
	}

	/**
	* Display available tags
	*/
	function show() {
		$Q = db()->query("SELECT * FROM `".db('custom_replace_tags')."` ORDER BY `name` ASC");
		while ($A = db()->fetch_assoc($Q)) {
			$replace2 = array(
				"bg_class"		=> !(++$i % 2) ? "bg1" : "bg2",
				"name"			=> _prepare_html($A["name"]),
				"desc"			=> _prepare_html($A["desc"]),
				"active"		=> intval($A["active"]),
				"edit_link"		=> "./?object=".$_GET["object"]."&action=edit_tag&id=".$A["id"],
				"delete_link"	=> "./?object=".$_GET["object"]."&action=delete_tag&id=".$A["id"],
				"rules_link"	=> "./?object=".$_GET["object"]."&action=show_rules&id=".$A["id"],
			);
			$items .= tpl()->parse($_GET["object"]."/tags_item", $replace2);
		}
		$replace = array(
			"items"			=> $items,
			"form_action"	=> "./?object=".$_GET["object"]."&action=insert_tag",
			"words_link"	=> "./?object=".$_GET["object"]."&action=show_words",
		);
		return tpl()->parse($_GET["object"]."/tags_main", $replace);
	}

	/**
	* Edit tag info
	*/
	function edit_tag () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) return "No id!";
		// Get current tag info
		$tag_info = db()->query_fetch("SELECT * FROM `".db('custom_replace_tags')."` WHERE `id`=".intval($_GET["id"]));
		if (empty($tag_info["id"])) return "No such tag!";
		// Do save
		if (isset($_POST["go"])) {
			// Check for errors
			if (!common()->_error_exists()) {
				db()->UPDATE("custom_replace_tags", array(
					"name"				=> _es($_POST["name"]),
					"desc"				=> _es($_POST["desc"]),
					"pattern_find"		=> _es($_POST["pattern_find"]),
					"pattern_replace"	=> _es($_POST["pattern_replace"]),
					"active"			=> intval((bool)$_POST["active"]),
				), "`id`=".intval($_GET["id"]));
			}
			// Refresh system cache
			if (main()->USE_SYSTEM_CACHE)	cache()->refresh("custom_replace_tags");
			// Return user back
			return js_redirect("./?object=".$_GET["object"]);
		}
		// Display form
		if (!isset($_POST["go"]) || common()->_error_exists()) {
			$replace = array(
				"form_action"		=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$_GET["id"],
				"name"				=> _prepare_html($tag_info["name"]),
				"desc"				=> _prepare_html($tag_info["desc"]),
				"pattern_find"		=> _prepare_html($tag_info["pattern_find"], 0),
				"pattern_replace"	=> _prepare_html($tag_info["pattern_replace"], 0),
				"active_box"		=> $this->_box("active", $tag_info["active"]),
				"back_link"			=> "./?object=".$_GET["object"]."&action=show",
			);
			return tpl()->parse($_GET["object"]."/edit_tag", $replace);
		}
	}

	/**
	* Add new block record
	*/
	function insert_tag () {
		// Do insert record
		db()->INSERT("custom_replace_tags", array(
			"name"				=> _es($_POST["name"]),
			"desc"				=> _es($_POST["desc"]),
			"pattern_find"		=> _es($_POST["pattern_find"]),
			"pattern_replace"	=> _es($_POST["pattern_replace"]),
			"active"			=> 1,
		));
		// Refresh system cache
		if (main()->USE_SYSTEM_CACHE)	cache()->refresh("custom_replace_tags");
		// Return user back
		js_redirect("./?object=".$_GET["object"]);
	}

	/**
	* Delete tag and its rules
	*/
	function delete_tag () {
		$_GET["id"] = intval($_GET["id"]);
		// Get current tag info
		if (!empty($_GET["id"])) {
			$tag_info = db()->query_fetch("SELECT * FROM `".db('custom_replace_tags')."` WHERE `id`=".intval($_GET["id"]));
		}
		// Do delete tag and its rules
		if (!empty($tag_info["id"])) {
			db()->query("DELETE FROM `".db('custom_replace_tags')."` WHERE `id`=".intval($_GET["id"])." LIMIT 1");
			db()->query("DELETE FROM `".db('custom_replace_rules')."` WHERE `tag_id`=".intval($_GET["id"]));
		}
		// Refresh system cache
		if (main()->USE_SYSTEM_CACHE)	cache()->refresh("custom_replace_tags");
		if (main()->USE_SYSTEM_CACHE)	cache()->refresh("custom_replace_rules");
		// Return user back
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo $_GET["id"];
		} else {
			return js_redirect("./?object=".$_GET["object"]);
		}
	}

	/**
	* Display all rules for selected tag
	*/
	function show_rules() {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) return "No id!";
		// Get current tag info
		$tag_info = db()->query_fetch("SELECT * FROM `".db('custom_replace_tags')."` WHERE `id`=".intval($_GET["id"]));
		if (empty($tag_info["id"])) return "No such tag!";
		// Connect pager
		$sql = "SELECT * FROM `".db('custom_replace_rules')."` WHERE `tag_id`=".intval($tag_info["id"])." ORDER BY `methods` ASC, `order` ASC";
		list($limit_sql, $pages, $total) = common()->divide_pages($sql);
		// Process records
		$Q = db()->query($sql. $limit_sql);
		while ($A = db()->fetch_assoc($Q)) {
			// Prepare data
			$user_groups = array();
			foreach (explode(",",$A["user_groups"]) as $k => $v) {
				if (empty($this->_user_groups[$v])) {
					continue;
				}
				$user_groups[] = $this->_user_groups[$v];
			}
			$user_groups = implode("<br />",$user_groups);
			$A["methods"] = str_replace(",","\r\n",$A["methods"]);
			if (empty($A["methods"])) {
				$A["methods"]	= "-- ALL --";
			}
			if (empty($user_groups)) {
				$user_groups	= "-- ALL --";
			}
			// Process template
			$replace2 = array(
				"id"			=> $A["id"],
				"bg_class"		=> !(++$i % 2) ? "bg1" : "bg2",
				"user_groups"	=> $user_groups,
				"methods"		=> nl2br(_prepare_html($A["methods"])),
				"query_string"	=> _prepare_html($A["query_string"]),
				"sites"			=> _prepare_html($this->_sites_names[$A["site_id"]]),
				"langs"			=> _prepare_html($A["language"]),
				"order"			=> intval($A["order"]),
				"active"		=> intval((bool) $A["active"]),
				"edit_link"		=> "./?object=".$_GET["object"]."&action=edit_rule&id=".$A["id"],
				"delete_link"	=> "./?object=".$_GET["object"]."&action=delete_rule&id=".$A["id"],
			);
			$items .= tpl()->parse($_GET["object"]."/rules_item", $replace2);
		}
		$replace = array(
			"add_link"		=> "./?object=".$_GET["object"]."&action=add_rule&id=".$tag_info["id"],
			"edit_tag_link"	=> "./?object=".$_GET["object"]."&action=edit_tag&id=".$tag_info["id"],
			"back_link"		=> "./?object=".$_GET["object"],
			"tag_name"		=> _prepare_html($tag_info["name"]),
			"items"			=> $items,
			"pages"			=> $pages,
			"total"			=> intval($total),
		);
		return tpl()->parse($_GET["object"]."/rules_main", $replace);
	}

	/**
	* Add rule
	*/
	function add_rule () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) return "No id!";
		// Get current tag info
		$tag_info = db()->query_fetch("SELECT * FROM `".db('custom_replace_tags')."` WHERE `id`=".intval($_GET["id"]));
		if (empty($tag_info["id"])) return "No such tag!";
		// Do save
		if (isset($_POST["go"])) {
			// Cleanup methods
			$_POST["methods"]	= $this->_cleanup_methods_for_save($_POST["methods"]);
			// Cleanup user_groups
			if (is_array($_POST["user_groups"])) {
				$_POST["user_groups"] = implode(",",$_POST["user_groups"]);
			}
			$_POST["user_groups"]	= str_replace(array(" ","\t","\r","\n"), "", $_POST["user_groups"]);
			// Check for errors
			if (!common()->_error_exists()) {
				db()->INSERT("custom_replace_rules", array(
					"tag_id"			=> intval($tag_info["id"]),
					"methods"			=> _es($_POST["methods"]),
					"user_groups"		=> _es($_POST["user_groups"]),
					"query_string"		=> _es($_POST["query_string"]),
					"tag_replace"		=> _es($_POST["tag_replace"]),
					"site_id"			=> intval($_POST["site_id"]),
					"language"			=> _es($_POST["language"]),
					"order"				=> intval($_POST["order"]),
					"eval_code"			=> intval((bool) $_POST["eval_code"]),
					"active"			=> intval((bool) $_POST["active"]),
				));
			}
			// Refresh system cache
			if (main()->USE_SYSTEM_CACHE)	cache()->refresh("custom_replace_rules");
			// Return user back
			js_redirect("./?object=".$_GET["object"]."&action=show_rules&id=".$tag_info["id"]);
		}
		// Display form
		$replace = array(
			"for_edit"			=> 0,
			"error_message"		=> _e(),
			"form_action"		=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$tag_info["id"],
			"tag_name"			=> _prepare_html($tag_info["name"]),
			"tag_replace"		=> _prepare_html($rule_info["tag_replace"], 0),
			"query_string"		=> _prepare_html($rule_info["query_string"]),
			"methods_box"		=> $this->_box("methods",		""),
			"user_groups_box"	=> $this->_box("user_groups",	""),
			"langs_box"			=> $this->_box("langs",			""),
			"sites_box"			=> $this->_box("sites",			""),
			"active_box"		=> $this->_box("active", 		0),
			"eval_code_box"		=> $this->_box("eval_code",		1),
			"words_box"			=> $this->_box("words"),
			"order"				=> intval($rule_info["order"]),
			"back_link"			=> "./?object=".$_GET["object"]."&action=show_rules&id=".intval($tag_info["id"]),
			"words_link"		=> "./?object=".$_GET["object"]."&action=show_words",
			"edit_tag_link"		=> "./?object=".$_GET["object"]."&action=edit_tag&id=".$tag_info["id"],
			"edit_sites_link"	=> "./?object=db_parser&table=sys_sites",
			"edit_langs_link"	=> "./?object=locale_editor",
			"edit_methods_link"	=> "./?object=user_modules",
			"edit_groups_link"	=> "./?object=user_groups",
		);
		return tpl()->parse($_GET["object"]."/edit_rule", $replace);
	}

	/**
	* Edit rule
	*/
	function edit_rule () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return _e(t("No id!"));
		}
		// Get current rule info
		$rule_info = db()->query_fetch("SELECT * FROM `".db('custom_replace_rules')."` WHERE `id`=".intval($_GET["id"]));
		if (empty($rule_info["id"])) {
			return _e(t("No such rule!"));
		}
		// Get current tag info
		$tag_info = db()->query_fetch("SELECT * FROM `".db('custom_replace_tags')."` WHERE `id`=".intval($rule_info["tag_id"]));
		if (empty($tag_info["id"])) {
			return _e(t("No such tag!"));
		}
		// Do save
		if (isset($_POST["go"])) {
			// Cleanup methods
			$_POST["methods"]	= $this->_cleanup_methods_for_save($_POST["methods"]);
			// Cleanup user_groups
			if (is_array($_POST["user_groups"])) {
				$_POST["user_groups"] = implode(",",$_POST["user_groups"]);
			}
			$_POST["user_groups"]	= str_replace(array(" ","\t","\r","\n"), "", $_POST["user_groups"]);
			// Check for errors
			if (!common()->_error_exists()) {
				db()->UPDATE("custom_replace_rules", array(
					"methods"			=> _es($_POST["methods"]),
					"user_groups"		=> _es($_POST["user_groups"]),
					"query_string"		=> _es($_POST["query_string"]),
					"tag_replace"		=> _es($_POST["tag_replace"]),
					"site_id"			=> intval($_POST["site_id"]),
					"language"			=> _es($_POST["language"]),
					"order"				=> intval($_POST["order"]),
					"eval_code"			=> intval((bool) $_POST["eval_code"]),
					"active"			=> intval((bool) $_POST["active"]),
				), "`id`=".intval($_GET["id"]));
			}
			// Refresh system cache
			if (main()->USE_SYSTEM_CACHE)	cache()->refresh("custom_replace_rules");
			// Return user back
			return js_redirect("./?object=".$_GET["object"]."&action=show_rules&id=".$tag_info["id"]);
		}
		// Prepare methods for form
		$rule_info["methods"]	= explode(",",str_replace(array(" ","\t","\r","\n"), "", $rule_info["methods"]));
		$tmp_array = $rule_info["methods"];
		$rule_info["methods"] = array();
		foreach ((array)$tmp_array as $method_name) {
			$rule_info["methods"][$method_name] = $method_name;
		}
		// Prepare user groups
		$rule_info["user_groups"]	= explode(",",str_replace(array(" ","\t","\r","\n"), "", $rule_info["user_groups"]));
		foreach ((array)$rule_info["user_groups"] as $v) {
			$tmp[$v] = $v;
		}
		$rule_info["user_groups"] = $tmp;
		// Process template
		$replace = array(
			"for_edit"			=> 1,
			"error_message"		=> _e(),
			"form_action"		=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$rule_info["id"],
			"tag_name"			=> _prepare_html($tag_info["name"]),
			"tag_replace"		=> _prepare_html($rule_info["tag_replace"], 0),
			"query_string"		=> _prepare_html($rule_info["query_string"]),
			"methods_box"		=> $this->_box("methods",		$rule_info["methods"]),
			"user_groups_box"	=> $this->_box("user_groups",	$rule_info["user_groups"]),
			"langs_box"			=> $this->_box("langs",			$rule_info["language"]),
			"sites_box"			=> $this->_box("sites",			$rule_info["site_id"]),
			"active_box"		=> $this->_box("active", 		$rule_info["active"]),
			"eval_code_box"		=> $this->_box("eval_code",		$rule_info["eval_code"]),
			"words_box"			=> $this->_box("words"),
			"order"				=> intval($rule_info["order"]),
			"back_link"			=> "./?object=".$_GET["object"]."&action=show_rules&id=".intval($tag_info["id"]),
			"words_link"		=> "./?object=".$_GET["object"]."&action=show_words",
			"edit_tag_link"		=> "./?object=".$_GET["object"]."&action=edit_tag&id=".$tag_info["id"],
			"edit_sites_link"	=> "./?object=db_parser&table=sys_sites",
			"edit_langs_link"	=> "./?object=locale_editor",
			"edit_methods_link"	=> "./?object=user_modules",
			"edit_groups_link"	=> "./?object=user_groups",
		);
		return tpl()->parse($_GET["object"]."/edit_rule", $replace);
	}

	/**
	* Delete rule
	*/
	function delete_rule () {
		$_GET["id"] = intval($_GET["id"]);
		// Get current rule info
		if (!empty($_GET["id"])) {
			$rule_info = db()->query_fetch("SELECT * FROM `".db('custom_replace_rules')."` WHERE `id`=".intval($_GET["id"]));
		}
		// Do delete rules
		if (!empty($rule_info["id"])) {
			db()->query("DELETE FROM `".db('custom_replace_rules')."` WHERE `id`=".intval($_GET["id"]));
		}
		// Refresh system cache
		if (main()->USE_SYSTEM_CACHE)	cache()->refresh("custom_replace_rules");
		// Return user back
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo $_GET["id"];
		} else {
			return js_redirect("./?object=".$_GET["object"]."&action=show_rules&id=".$rule_info["tag_id"]);
		}
	}

	/**
	* Display all words
	*/
	function show_words() {
		// Connect pager
		$sql = "SELECT * FROM `".db('custom_replace_words')."` ORDER BY `key` ASC";
		list($limit_sql, $pages, $total) = common()->divide_pages($sql);
		// Process records
		$Q = db()->query($sql. $limit_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$replace2 = array(
				"id"			=> $A["id"],
				"bg_class"		=> !(++$i % 2) ? "bg1" : "bg2",
				"key"			=> _prepare_html($A["key"]),
				"desc"			=> _prepare_html($A["desc"]),
				"active"		=> intval((bool) $A["active"]),
				"edit_link"		=> "./?object=".$_GET["object"]."&action=edit_word&id=".$A["id"],
				"delete_link"	=> "./?object=".$_GET["object"]."&action=delete_word&id=".$A["id"],
			);
			$items .= tpl()->parse($_GET["object"]."/words_item", $replace2);
		}
		$replace = array(
			"add_link"		=> "./?object=".$_GET["object"]."&action=add_word",
			"back_link"		=> "./?object=".$_GET["object"],
			"items"			=> $items,
			"pages"			=> $pages,
			"total"			=> intval($total),
		);
		return tpl()->parse($_GET["object"]."/words_main", $replace);
	}

	/**
	* Add new word
	*/
	function add_word () {
		// Do save
		if (isset($_POST["go"])) {
			// Check for errors
			if (!common()->_error_exists()) {
				db()->INSERT("custom_replace_words", array(
					"key"		=> _es($_POST["key"]),
					"desc"		=> _es($_POST["desc"]),
					"value"		=> _es($_POST["value"]),
					"active"	=> intval((bool) $_POST["active"]),
				));
			}
			// Refresh system cache
			if (main()->USE_SYSTEM_CACHE)	cache()->refresh("custom_replace_words");
			// Return user back
			js_redirect("./?object=".$_GET["object"]."&action=show_words");
		}
		// Display form
		if (!isset($_POST["go"]) || common()->_error_exists()) {
			$replace = array(
				"for_edit"			=> 0,
				"form_action"		=> "./?object=".$_GET["object"]."&action=".$_GET["action"],
				"key"				=> _prepare_html($word_info["key"]),
				"value"				=> _prepare_html($word_info["value"], 0),
				"desc"				=> _prepare_html($word_info["desc"], 0),
				"active_box"		=> $this->_box("active", 1),
				"back_link"			=> "./?object=".$_GET["object"]."&action=show_words",
			);
			return tpl()->parse($_GET["object"]."/edit_word", $replace);
		}
	}

	/**
	* Edit word
	*/
	function edit_word () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) return "No id!";
		// Get current rule info
		$word_info = db()->query_fetch("SELECT * FROM `".db('custom_replace_words')."` WHERE `id`=".intval($_GET["id"]));
		if (empty($word_info["id"])) return "No such word!";
		// Do save
		if (isset($_POST["go"])) {
			// Check for errors
			if (!common()->_error_exists()) {
				db()->UPDATE("custom_replace_words", array(
					"key"		=> _es($_POST["key"]),
					"desc"		=> _es($_POST["desc"]),
					"value"		=> _es($_POST["value"]),
					"active"	=> intval((bool) $_POST["active"]),
				), "`id`=".intval($_GET["id"]));
			}
			// Refresh system cache
			if (main()->USE_SYSTEM_CACHE)	cache()->refresh("custom_replace_words");
			// Return user back
			js_redirect("./?object=".$_GET["object"]."&action=show_words");
		}
		// Display form
		if (!isset($_POST["go"]) || common()->_error_exists()) {
			$replace = array(
				"for_edit"			=> 1,
				"form_action"		=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$word_info["id"],
				"key"				=> _prepare_html($word_info["key"], 0),
				"value"				=> _prepare_html($word_info["value"], 0),
				"desc"				=> _prepare_html($word_info["desc"]),
				"active_box"		=> $this->_box("active", 	$word_info["active"]),
				"back_link"			=> "./?object=".$_GET["object"]."&action=show_words",
			);
			return tpl()->parse($_GET["object"]."/edit_word", $replace);
		}
	}

	/**
	* Delete word
	*/
	function delete_word () {
		$_GET["id"] = intval($_GET["id"]);
		// Get current word info
		if (!empty($_GET["id"])) {
			$word_info = db()->query_fetch("SELECT * FROM `".db('custom_replace_words')."` WHERE `id`=".intval($_GET["id"]));
		}
		// Do delete
		if (!empty($word_info["id"])) {
			db()->query("DELETE FROM `".db('custom_replace_words')."` WHERE `id`=".intval($_GET["id"]));
		}
		// Refresh system cache
		if (main()->USE_SYSTEM_CACHE)	cache()->refresh("custom_replace_words");
		// Return user back
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo $_GET["id"];
		} else {
			return js_redirect("./?object=".$_GET["object"]."&action=show_words");
		}
	}

	/**
	* Cleanup methods for save them in db
	*/
	function _cleanup_methods_for_save ($methods_array = array()) {
		if (!is_array($methods_array) || empty($methods_array)) return false;
		// Prepare array
		sort($methods_array);
		$cur_top_level_methods	= array();
		$methods_for_save		= array();
		// Try to compact rules
		foreach ((array)$methods_array as $method_full_name) {
			// Verify method name
			if (empty($method_full_name) || !isset($this->_user_methods[$method_full_name])) {
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
		$pheader = t("Custom replace editor");
		// Default subheader get from action name
		$subheader = _ucwords(str_replace("_", " ", $_GET["action"]));

		// Array of replacements
		$cases = array (
			//$_GET["action"] => {string to replace}
			"show"				=> "",
			"show_rules"		=> "",
			"add_rule"			=> "",
			"show_words"		=> "Replace words list",
			"add_word"			=> "",
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
