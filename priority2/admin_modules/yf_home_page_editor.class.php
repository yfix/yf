<?php

/**
* Sites home pages editor
* 
* @package		YF
* @author		Yuri Vysotskiy <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_home_page_editor {

	/** @var string Name of home page template */
	var $stpl_name = "home_page/main";

	/**
	* Constructor
	*/
	function yf_home_page_editor() {
		// Physical path to the library templates
		define(TPLS_LIB_PATH, INCLUDE_PATH. tpl()->_THEMES_PATH);
		// Try to get info about sites vars
		$this->_sites_info = main()->init_class("sites_info", "classes/");
	}

	/**
	* Default function
	*/
	function show () {
		// Copy photos to the other sites folders
		foreach ((array)$this->_sites_info->info as $site_id => $SITE_INFO) {
			$theme_name = $this->_sites_info->_get_site_current_theme($SITE_INFO);
			$replace2 = array(
				"num"		=> ++$i,
				"bg_class"	=> !($i % 2) ? "bg1" : "bg2",
				"site_id"	=> intval($site_id),
				"site_name"	=> $SITE_INFO["name"],
				"theme_name"=> $theme_name,
				"edit_url"	=> "./?object=".$_GET["object"]."&action=edit&id=".$site_id,
			);
			$items .= tpl()->parse($_GET["object"]."/item", $replace2);
		}
		$replace = array(
			"items" 		=> $items,
		);
		return tpl()->parse($_GET["object"]."/main", $replace);
	}

	/**
	* Edit home page for the selected site
	*/
	function edit () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) return "No ID!";
		if (empty($this->_sites_info->info[$_GET["id"]])) return "Wrong Site ID!";
		// Get site info
		$SITE_INFO	= $this->_sites_info->info[$_GET["id"]];
		// Get home page template contents
		$theme_name = $this->_sites_info->_get_site_current_theme($SITE_INFO);
		list($record_id, $text) = db()->query_fetch("SELECT `id` AS `0`, `text` AS `1` FROM `".db('templates')."` WHERE `theme_name`='".$theme_name."' AND `name`='"._es($this->stpl_name)."' AND `site_id`=".intval($_GET["id"])." AND `active`='1'");
		// If record doesnt exists - get it from lib
		if (empty($record_id)) {
			$site_home_page_path	= $SITE_INFO["REAL_PATH"]. tpl()->_THEMES_PATH. $theme_name. "/". $this->stpl_name. tpl()->_STPL_EXT;
			$lib_home_page_path		= TPLS_LIB_PATH. $theme_name. "/". $this->stpl_name. tpl()->_STPL_EXT;
			// Get template file from lib or from site if exists there
			$text = file_get_contents(file_exists($site_home_page_path) ? $site_home_page_path : $lib_home_page_path);
		}
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"]."&action=save&id=".$_GET["id"],
			"theme_name"	=> $theme_name,
			"site_name"		=> $SITE_INFO["name"],
			"home_page_url"	=> $SITE_INFO["WEB_PATH"],
			"string"		=> _prepare_html($text, 0),
		);
		return tpl()->parse($_GET["object"]."/edit_main", $replace);
	}

	/**
	* Save home page template contents
	*/
	function save () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) return "No ID!";
		if (empty($this->_sites_info->info[$_GET["id"]])) return "Wrong Site ID!";
		// Get site info
		$SITE_INFO	= $this->_sites_info->info[$_GET["id"]];
		// Get home page template contents
		$theme_name = $this->_sites_info->_get_site_current_theme($SITE_INFO);
		// Check if current template exists in the db
		list($record_id) = db()->query_fetch("SELECT `id` AS `0` FROM `".db('templates')."` WHERE `theme_name`='".$theme_name."' AND `name`='"._es($this->stpl_name)."' AND `site_id`=".intval($_GET["id"])." AND `active`='1'");
		// Save template contents
		$text = $_POST["string"];
		// Update db
		if ($record_id) db()->query("UPDATE `".db('templates')."` SET `text`='"._es($text)."' WHERE `id`=".intval($record_id));
		else db()->query("REPLACE INTO `".db('templates')."` (`theme_name`,`name`,`text`,`site_id`) VALUES ('".$theme_name."','".$this->stpl_name."','"._es($text)."',".intval($_GET["id"]).")");
		// Create template entry in files
		$site_home_page_path = $SITE_INFO["REAL_PATH"]. tpl()->_THEMES_PATH. $theme_name. "/". $this->stpl_name. tpl()->_STPL_EXT;
		// Create template folder if it doesnt exists
		if (!file_exists($site_home_page_path)) {
			_mkdir_m(dirname($site_home_page_path));
		}
		// Save file
		file_put_contents($site_home_page_path, $text);
		// Redirect back
		js_redirect("./?object=".$_GET["object"]."&action=edit&id=".$_GET["id"]);
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
		$pheader = t("Home page editor");
		// Default subheader get from action name
		$subheader = _ucwords(str_replace("_", " ", $_GET["action"]));

		// Array of replacements
		$cases = array (
			//$_GET["action"] => {string to replace}
			"show"				=> "",
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
