<?php

/**
* Display log image resizes
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_custom_bbcode_editor {

   	/** @var int Number of symbols in trimmed string **/
	public $string_cut_limit = 30;


	/**
	* Constructor (PHP 4.x)
	*/
	function yf_log_image_resize_viewer () {
		return $this->__construct();
	}

	/**
	* Constructor (PHP 5.x)
	*/
	function __construct () {
		main()->USER_ID = $_GET['user_id'];
		// Try to get info about sites vars
		$this->_sites_info = main()->init_class("sites_info", "classes/");
	}

	/**
	* Default method
	* 
	* @access
	* @param
	* @return
	*/
	function show () {
		// Get data from database
		$sql = "SELECT * FROM ".db('custom_bbcode')."";
		list($add_sql, $pages, $total) = common()->divide_pages($sql);
		$result = db()->query_fetch_all($sql.$add_sql);
		foreach ((array)$result as $A) {

			preg_match("/(\[.+\])([^\[]+)(\[.+\])/ims" ,$A["example"], $bb_content);
			if ($A["useoption"]) preg_match("/(\[.+=)([^\]]+)(\].+\[.+\])/ims", $A["example"], $bb_option);

			$replace2 = array(
				"bg_class"		=> $i++ % 2 ? "bg1" : "bg2",
				"id"			=> intval($A["id"]),
				"title" 		=> $A["title"], 
				"description" 	=> $A["desc"],
				"tag" 			=> $A["tag"],
				"html_code" 	=> substr(_prepare_html($A["replace"]), 0, $this->string_cut_limit)."...",  
				"example"		=> _prepare_html($A["example"]),
				"result"		=> str_replace(array("{content}", "{option}"), array($bb_content[2], $bb_option[2]) ,$A["replace"]),
				"use_option"	=> intval($A["useoption"]),
				"edit_link"		=> "./?object=".$_GET["object"]."&action=edit&id=".$A["id"],
				"delete_link"	=> "./?object=".$_GET["object"]."&action=delete&id=".$A["id"],
				"active"		=> intval($A["active"]),
			);
			$items .= tpl()->parse($_GET["object"]."/item", $replace2);	 
		}
		$replace = array(
			"items"					=> $items,
			"pages"					=> $pages,
			"total"					=> intval($total),
			"form_action"			=> "./?object=".$_GET["object"]."&action=multi_delete",	
			"add_link"				=> "./?object=".$_GET["object"]."&action=add",	
		);		
		return tpl()->parse($_GET["object"]."/main", $replace);
	}

	/**
	* Shows edit form
	* 
	* @access
	* @param
	* @return
	*/
	function edit () {
		$A = db()->query_fetch("SELECT * FROM ".db('custom_bbcode')." WHERE id='".$_GET["id"]."'");
		$this->_select_yn = array(
			"1" 	=> t("YES"),
			"0" 	=> t("NO"),
		);
		$replace = array(
			"record_id" 		=> intval($A["id"]),
			"title"				=> _prepare_html($A["title"]),
			"description"		=> _prepare_html($A["desc"]),
			"tag"				=> $A["tag"],
			"html_code"			=> _prepare_html($A["replace"]),
			"example"			=> _prepare_html($A["example"]),
			"use_option_box"	=> common()->select_box("useoption", $this->_select_yn, $A["useoption"], 0, 2, "", false),
			"save_link"			=> "./?object=".$_GET["object"]."&action=save&id=".$A["id"],
			"back_link"			=> "./?object=".$_GET["object"],
			"action_text"		=> ucfirst($_GET["action"]),
			"active_box"		=> common()->select_box("active", $this->_select_yn, $A["active"], 0, 2, "", false),
		);
		return tpl()->parse($_GET["object"]."/form", $replace);
	}

	/**
	* Add record to database
	* 
	* @access
	* @param
	* @return
	*/
	function add () {
		$this->_use_option = array(
			"1" 	=> t("YES"),
			"0" 	=> t("NO"),
		);

		$this->fields_in_form = array(
			"action_text",
			"title",
			"description",
			"tag",
			"html_code",
			"example",
			"use_option_box",
			"save_link",
			"back_link",
		);

		$replace = array(
			"use_option_box"	=> common()->select_box("useoption", $this->_select_yn, 0, 0, 2, "", false),
			"save_link"			=> "./?object=".$_GET["object"]."&action=save",
			"back_link"			=> "./?object=".$_GET["object"],
			"action_text"		=> ucfirst($_GET["action"]),
			"active_box"		=> common()->select_box("active", $this->_select_yn, 0, 0, 2, "", false),
		);

		foreach ((array)$this->fields_in_form as $k){
			if (!isset($replace[$k])) $replace[$k] = "";
		}
		return tpl()->parse($_GET["object"]."/form", $replace);
	}

	/**
	* Updates record in database
	* 
	* @access
	* @param
	* @return
	*/
	function save () {
		if ($_GET["id"]){
			db()->UPDATE("custom_bbcode", array(
				"title"		=> _es($_POST["title"]),
				"desc"		=> _es($_POST["desc"]),
				"tag" 		=> _es($_POST["tag"]),
				"replace" 	=> _es($_POST["replace"]),
				"example" 	=> _es($_POST["example"]),
				"useoption"	=> intval($_POST["useoption"]),
				"active"	=> intval($_POST["active"]),
			), "id=".$_GET["id"]);
		}								
		else {
			db()->INSERT("custom_bbcode", array(
				"title"		=> _es($_POST["title"]),
				"desc"		=> _es($_POST["desc"]),
				"tag" 		=> _es($_POST["tag"]),
				"replace" 	=> _es($_POST["replace"]),
				"example" 	=> _es($_POST["example"]),
				"useoption"	=> intval($_POST["useoption"]),
				"active"	=> intval($_POST["active"]),
			));	
		}
		// Refresh system cache
		if (main()->USE_SYSTEM_CACHE)	cache()->refresh("custom_bbcode");

		return js_redirect("./?object=".$_GET["object"]._add_get());
	}

	/**
	* Delete single record from database
	* 
	* @access
	* @param
	* @return
	*/
	function delete () {
		$_GET["id"] = intval($_GET["id"]);
		// Do delete record
		db()->query("DELETE FROM ".db('custom_bbcode')." WHERE id=".intval($_GET["id"]));
		// Refresh system cache
		if (main()->USE_SYSTEM_CACHE)	cache()->refresh("custom_bbcode");
		// Return user back
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo $_GET["id"];
		} else {
			return js_redirect($_SERVER["HTTP_REFERER"], 0);
		}
	}

	/**
	* Multi delete records
	*/
	function multi_delete () {
		$ids_to_delete = array();
		// Prepare ids to delete
		foreach ((array)$_POST["items"] as $_cur_id) {
			if (empty($_cur_id)) {
				continue;
			}
			$ids_to_delete[$_cur_id] = $_cur_id;
		}
		// Do delete ids
		if (!empty($ids_to_delete)) {
			db()->query("DELETE FROM ".db('custom_bbcode')." WHERE id IN(".implode(",",$ids_to_delete).")");
		}
		// Refresh system cache
		if (main()->USE_SYSTEM_CACHE)	cache()->refresh("custom_bbcode");
		// Return user back
		return js_redirect($_SERVER["HTTP_REFERER"], 0);
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
				"name"	=> "Add",
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
		$pheader = t("Custom BB-code editor");
		// Default subheader get from action name
		$subheader = _ucwords(str_replace("_", " ", $_GET["action"]));

		// Array of replacements
		$cases = array (
			//$_GET["action"] => {string to replace}
			"show"				=> "",
			"add"				=> "",
			"edit"				=> "",
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
