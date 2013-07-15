<?php

if (!class_exists("news")) {
	require_once (YF_PATH."priority2/modules/yf_news.class.php");
}

class yf_manage_news extends yf_news {

	/**
	* Default method
	*/
	function show() {
		$Q = db()->query("SELECT id,title,head_text,add_date,active FROM ".db('news')." ORDER BY add_date DESC");
		while ($A = db()->fetch_assoc($Q)) {
			$replace2 = array(
				"bg_class"		=> !(++$i % 2) ? "bg1" : "bg2",
				"title"			=> $A["title"],
				"head_text"		=> nl2br($A["head_text"]),
				"add_date"		=> _format_date($A["add_date"]),
				"active"		=> $A["active"],
				"active_link"	=> "./?object=".$_GET["object"]."&action=activate_item&id=".$A["id"],
				"edit_link"		=> "./?object=".$_GET["object"]."&action=edit&id=".$A["id"],
				"delete_link"	=> "./?object=".$_GET["object"]."&action=delete&id=".$A["id"],
			);
			
			$items .= tpl()->parse($_GET["object"]."/item", $replace2);
		}
		
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"]."&action=add",
			"items"			=> $items,
		);
		
		return tpl()->parse($_GET["object"]."/main", $replace);
	}

	/**
	* activate item
	*/
	function activate_item() {
		if (!empty($_GET["id"])) {
			$item_info = db()->query_fetch("SELECT id,active FROM ".db('news')." WHERE id=".intval($_GET["id"]));
		}
		// Do change activity status
		if (!empty($item_info)) {
			db()->UPDATE("news", array("active" => (int)!$item_info["active"]), "id=".intval($item_info["id"]));
		}

		// Return user back
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo ($item_info["active"] ? 0 : 1);
		} else {
			return js_redirect("./?object=".$_GET["object"]);
		}
	}

	
	
	/**
	* add
	*/
	function add(){
		if($_POST){
			db()->INSERT("news", array(
				"title"		=> _es($_POST["title"]), 
				"head_text"	=> _es($_POST["head_text"]), 
				"full_text"	=> _es($_POST["full_text"]), 
				"add_date"	=> intval(time()),
				"active"	=> intval($_POST["active"]),
			));
			return js_redirect("./?object=".$_GET["object"]);
		}
		
		$replace = array(
			"form_action"			=> "./?object=".$_GET["object"]."&action=".$_GET["action"],
			"title"					=> $news["title"],
			"head_text"				=> $news["head_text"],
			"full_text"				=> $news["full_text"],
			"active"				=> "0",
			//"bb_codes_full_text"	=> $this->USE_BB_CODES ? _class("bb_codes")->_display_buttons(array("unique_id" => "full_text")) : "",
		);
		
		return tpl()->parse($_GET["object"]."/edit", $replace);
	}

	/**
	* edit
	*/
	function edit(){
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return _e("No id");
		}
		
		if($_POST){
			db()->UPDATE("news", array(
				"title"		=> _es($_POST["title"]), 
				"head_text"	=> _es($_POST["head_text"]), 
				"full_text"	=> _es($_POST["full_text"]), 
				"active"	=> intval($_POST["active"]),
			), "id=".intval($_GET["id"]));
			return js_redirect("./?object=".$_GET["object"]);
		}
		
		$news = db()->query_fetch("SELECT * FROM ".db('news')." WHERE id=".intval($_GET["id"]));
		
		$replace = array(
			"form_action"			=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$_GET["id"],
			"title"					=> $news["title"],
			"head_text"				=> $news["head_text"],
			"full_text"				=> $news["full_text"],
			"active"				=> $news["active"],
			//"bb_codes_full_text"	=> $this->USE_BB_CODES ? _class("bb_codes")->_display_buttons(array("unique_id" => "full_text")) : "",

		);
		
		return tpl()->parse($_GET["object"]."/edit", $replace);
	}


	/**
	* delete
	*/
	function delete() {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return _e("No id");
		}
		db()->query("DELETE FROM ".db('news')." WHERE id=".intval($_GET["id"])." LIMIT 1");
		return js_redirect("./?object=".$_GET["object"]);
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
		$pheader = t("Manage news");
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
