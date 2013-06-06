<?php

/**
* Static pages display module
* 
* @package		YF
* @author		Yuri Vysotskiy <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_static_pages {

	/** @var string @conf_skip */
	var $PAGE_NAME			= null;
	/** @var string @conf_skip */
	var $PAGE_TITLE			= null;
	/** @var bool Allow HTML in text */
	var $ALLOW_HTML_IN_TEXT	= true;
	/** @var bool */
	var $MULTILANG_MODE		= false;

	/**
	* Catch missing method call
	*/
	function __call($name, $arguments) {
		trigger_error(__CLASS__.": No method ".$name, E_USER_WARNING);
		return false;
	}

	/**
	* Display page contents
	*/
	function show () {
		if (empty($_GET['id'])) {
			return "";
		}
		$page_info = $this->_get_page_from_db();
		$this->_set_global_info($page_info);
		// Show error message
		if (empty($page_info)) {
			common()->_raise_error(t("No such page!"));
			return _e();
		}
		// Get sub-pages (from menu)
		$sub_pages = array();
		$menus = main()->get_data("menus");

		$cur_menu_id = 0;
		// Find first user menu
		if (!$cur_menu_id) {
			foreach ((array)$menus as $_info) {
				if ($_info["type"] == "user" && $_info["active"]) {
					$cur_menu_id = $_info["id"];
					break;
				}
			}
		}
		$cur_menu_item_id = 0;
		if ($cur_menu_id) {
			$menu_items = main()->get_data("menu_items");
			foreach ((array)$menu_items[$cur_menu_id] as $item_info) {
				if (!$item_info["active"] || $item_info["parent_id"]) {
					continue;
				}
				if ($item_info["location"] == "object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$_GET["id"]) {
					$cur_menu_item_id = $item_info["parent_id"] ? $item_info["parent_id"] : $item_info["id"];
					break;
				}
			}
		}
		if ($cur_menu_id && $cur_menu_item_id) {
			foreach ((array)$menu_items[$cur_menu_id] as $item_info) {
				if (!$item_info["active"] || $item_info["parent_id"] != $cur_menu_item_id) {
					continue;
				}
				$sub_pages[$item_info["id"]] = array(
					"name"	=> _prepare_html($item_info["name"]),
					"link"	=> process_url("./?".$item_info["location"]),
				);
			}
		}
		$content = tpl()->parse_string("static_page__".$page_info["id"], array(), stripslashes($page_info["text"]));
		// Process template
		$replace = array(
			"id"				=> intval($page_info["id"]),
			"name"				=> stripslashes($page_info["name"]),
//			"content"			=> stripslashes($page_info["text"]), // DO NOT ADD _prepare_html here!
			"content"			=> $content,
			"page_heading"		=> _prepare_html(_ucfirst($page_info["page_heading"])),
			"page_page_title"	=> _prepare_html(_ucfirst($page_info["page_title"])),
			"print_link"		=> "./?object=".$_GET["object"]."&action=print_view&id=".$page_info["id"],
			"pdf_link"			=> "./?object=".$_GET["object"]."&action=pdf_view&id=".$page_info["id"],
			"email_link"		=> "./?object=".$_GET["object"]."&action=email_page&id=".$page_info["id"],
			"sub_pages"			=> $sub_pages,
		);
		return tpl()->parse($_GET["object"]."/main", $replace);
	}

	/**
	* Get page from database
	*/
	function _get_page_from_db () {
		if (empty($_GET['id'])) {
			return array();
		}
		// Try to get page contents
		$page_info = db()->query_fetch(
			"SELECT * FROM `".db('static_pages')."` 
			WHERE `active`='1' 
				AND ".(is_numeric($_GET['id']) ? " `id`=".intval($_GET['id']) : " `name`='"._es(_strtolower($_GET["id"]))."'")
				. ($this->MULTILANG_MODE ? " AND `locale`='"._es(conf('language'))."'" : "")
		);
		return $page_info;
	}

	/**
	* Print View
	*/
	function print_view () {
		$page_info = $this->_get_page_from_db();
		$this->_set_global_info($page_info);
		// Show error message
		if (empty($page_info)) {
			common()->_raise_error(t("No such page!"));
			$body = _e();
		} else {
			$text = $this->ALLOW_HTML_IN_TEXT ? $page_info["text"] : _prepare_html($page_info["text"]);
			$body = common()->print_page($text);
		}
		return $body;
	}

	/**
	* Pdf View
	*/
	function pdf_view () {
		$page_info = $this->_get_page_from_db();
		$this->_set_global_info($page_info);
		// Show error message
		if (empty($page_info)) {
			common()->_raise_error(t("No such page!"));
			$body = _e();
		} else {
			$text = $this->ALLOW_HTML_IN_TEXT ? $page_info["text"] : _prepare_html($page_info["text"]);
			$body = common()->pdf_page($text, "page_".$page_info["name"]);
		}
		return $body;
	}

	/**
	* Email Page
	*/
	function email_page () {
		$page_info = $this->_get_page_from_db();
		$this->_set_global_info($page_info);
		// Show error message
		if (empty($page_info)) {
			common()->_raise_error(t("No such page!"));
			$body = _e();
		} else {
			$body = common()->email_page($page_info["text"]);
		}
		return $body;
	}

	/**
	* Rss Page
	*/
	function rss_page () {
		$data	= array();
		$params = array();
		$body = common()->rss_page($data, $params);
		return $body;
	}

	/**
	* Display RSS channels contents
	*/
	function get_rss_page () {
		$params = array();
		$body = common()->fetch_rss($params);
		return $body;
	}

	/**
	* Set page infor for global use
	*/
	function _set_global_info ($page_info = array()) {
		$this->PAGE_NAME	= _prepare_html($page_info["name"]);
		$this->PAGE_HEADING	= _prepare_html(_ucfirst($page_info["page_heading"]));
		$this->PAGE_TITLE	= _prepare_html(_ucfirst($page_info["title"] ? $page_info["title"] : $page_info["page_title"]));
		conf('meta_keywords', _prepare_html($page_info["meta_keywords"]));
		conf('meta_description', _prepare_html($page_info["meta_desc"]));
	}

	/**
	* Hook for the site_map
	*/
	function _site_map_items ($OBJ = false) {
		if (!is_object($OBJ)) {
			return false;
		}
		$Q = db()->query("SELECT * FROM `".db('static_pages')."` WHERE `active`='1'". ($this->MULTILANG_MODE ? " AND `locale`='"._es(conf('language'))."'" : ""));
		while ($A = db()->fetch_assoc($Q)) {
			$OBJ->_store_item(array(
				"url"	=> "./?object=static_pages&action=show&id=".$A["id"],
			));
		}
		return true;
	}

	/**
	* Page header hook
	*/
	function _show_header() {
		$pheader = $this->PAGE_HEADING ? $this->PAGE_HEADING : $this->PAGE_NAME;
		// Default subheader get from action name
		$subheader = "";
		// Array of replacements
		$cases = array (
			//$_GET["action"] => {string to replace}
			"show"	=> "",
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

	/**
	* Title hook
	*/
	function _site_title($title) {
		$subtitle = "";

		$subtitle = $this->PAGE_TITLE ? $this->PAGE_TITLE : $this->PAGE_NAME;
		if ($subtitle) {
			$title .= " : ".t($subtitle);
		}
		return $title;
	}

	/**
	* Hook for navigation bar
	*/
	function _nav_bar_items ($params = array()) {
		$NAV_BAR_OBJ = &$params["nav_bar_obj"];
		if (!is_object($NAV_BAR_OBJ)) {
			return false;
		}
		$subtitle = $this->PAGE_TITLE ? $this->PAGE_TITLE : $this->PAGE_NAME;
		// Save old items
		$old_items = $params["items"];
		// Create new items
		$items = array();
		$items[]	= $NAV_BAR_OBJ->_nav_item("Home", "./");
		$items[]	= $NAV_BAR_OBJ->_nav_item($subtitle);
		return $items;
	}
}
