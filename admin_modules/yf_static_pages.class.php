<?php

/**
* HTML content editor
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_static_pages {

	/** @var string */
	public $TEXT_FIELD_NAME 	= "text_to_edit";
	/** @var bool */
	public $MULTILANG_MODE		= false;
	/** @var string Project default locale (defines in project index.php)*/
	public $PROJ_DEFAULT_LOCALE= "en";
	/** @var bool */
	public $USE_VISUAL_EDIT	= true;

	/**
	* Module constructor
	*/
	function _init() {
		if ($this->USE_VISUAL_EDIT) {
			$this->TEXT_EDITOR_OBJ = _class("text_editor");
			$this->_EDITOR_EXISTS = is_object($this->TEXT_EDITOR_OBJ) && $this->TEXT_EDITOR_OBJ->EDITOR_EXISTS;
//			if ($this->_EDITOR_EXISTS) {
//				$this->TEXT_EDITOR_OBJ->TEXT_FIELD_NAME = "answer_text";
//			}
		}
		// Array of select boxes to process
		$this->_boxes = array(
			"active"	=> 'radio_box("active",	$this->_statuses,	$selected, false, 2, "", false)',
		);
		// Array of statuses
		$this->_statuses = array(
			"0" => "<span class='negative'>NO</span>",
			"1" => "<span class='positive'>YES</span>",
		);
		// Array of available languages
		$this->LANGUAGES = array();
		foreach ((array)main()->get_data("locale_langs") as $_locale => $_info) {
			$this->LANGUAGES[$_locale] = $_locale;
		}
	}

	/**
	* Show dialog to chose static page to edit
	*/
	function show() {

		$Q = db()->query("SELECT * FROM `".db('static_pages')."`". ($this->MULTILANG_MODE ? " GROUP BY `name`" : ""));
		while ($page_info = db()->fetch_assoc($Q)) {

			$replace2 = array(
				"bg_class"		=> !(++$i % 2) ? "bg1" : "bg2",
				"id"			=> intval($page_info["id"]),
				"name"			=> _prepare_html($page_info["name"]),
				"title"			=> _prepare_html($page_info["title"]),
				"active"		=> intval((bool)$page_info["active"]),
				
				"locale_box"	=> $this->MULTILANG_MODE ? common()->select_box("locale", $this->LANGUAGES, "", false) : "",

				"edit_link"		=> "./?object=".$_GET["object"]."&action=edit&id=".urlencode($page_info["name"]),
				"delete_link"	=> "./?object=".$_GET["object"]."&action=delete&id=".urlencode($page_info["name"]),
				"active_link"	=> "./?object=".$_GET["object"]."&action=activate&id=".urlencode($page_info["name"]),
				"view_link"		=> "./?object=".$_GET["object"]."&action=view&id=".urlencode($page_info["name"]),
			);
			$items .= tpl()->parse($_GET["object"]."/item", $replace2);
		}
		// Process template
		$replace = array(
			"items"			=> $items,
			"form_action"	=> "./?object=".$_GET["object"]."&action=add",
		);
		return tpl()->parse($_GET["object"]."/main", $replace);
	}

	/**
	* This function add new static page
	*/
	function add() {
		if (empty($_POST['name'])) {
			return _e(t("Page name required!"));
		}
		$name = preg_replace("/[^a-z0-9\_\-]/i", "_", _strtolower($_POST['name']));
		$name = str_replace(array("__", "___"), "_", $name);

		if (strlen($name)) {
			if ($this->MULTILANG_MODE) {
				foreach ((array)$this->LANGUAGES as $_locale) {
					db()->INSERT("static_pages", array(
						"name"		=> _es($name),
						"locale"	=> _es($_locale),
					));
				}
			} else {
				db()->INSERT("static_pages", array(
					"name"		=> _es($name),
					"locale"	=> _es($this->PROJ_DEFAULT_LOCALE),
				));
			}
		}
		if (main()->USE_SYSTEM_CACHE)	{
			cache()->refresh("static_pages_names");
		}
		// Check if record was added
		if (!empty($name)) {
			return js_redirect("./?object=".$_GET["object"]."&action=edit&id=".urlencode($name));
		} else {
			return _e(t("Can't insert record!"));
		}
	}

	/**
	* Edit form
	*/
	function edit() {
		$_def_locale = $this->PROJ_DEFAULT_LOCALE;
		if (!$_def_locale) {
			$_def_locale = "en";
		}
		$fields = array(
			"id",
			"cat_id",
			"name",
			"text",
			"page_title",
			"page_heading",
			"meta_keywords",
			"meta_desc",
			"locale",
			"active",
		);
		$Q = db()->query("SELECT * FROM `".db('static_pages')."` WHERE `name`='"._es(_strtolower(urldecode($_GET['id'])))."'");
		while($A = db()->fetch_assoc($Q)) {
			if (!$A["locale"]) {
				$A["locale"] = $_def_locale;
			}
			// Do not change this! Needed to prevent template missing tags when updating old project
			$info = array();
			foreach ((array)$fields as $field) {
				$info[$field] = $A[$field];
			}
			$page_info[$A["locale"]] = $info;
			$active_status = $info["active"];
		}
		if ($this->MULTILANG_MODE) {
			foreach((array)$this->LANGUAGES as $locales){
				if(in_array($locales, array_keys($page_info)) == false){
					$page_info[$locales] = array(
						"id"			=> "",
						"cat_id"		=> "",
						"name" 			=> "",
						"text" 			=> "",
						"page_title" 	=> "",
						"page_heading" 	=> "",
						"meta_keywords" => "",
						"meta_desc" 	=> "",
						"locale" 		=> $locales,
						"active"		=> intval($active_status),
					);
				}
			}
		}
		if (empty($page_info)) {
			return _e("No page info!");
		}
		// Save page
		if (!empty($_POST)) {
			if (isset($_POST['name'])) {
				$name = preg_replace("/[^a-z0-9\_\-]/i", "_", _strtolower($_POST['name']));
				$name = str_replace(array("__", "___"), "_", $name);
			}
			if ($this->MULTILANG_MODE) {
				foreach ((array)$this->LANGUAGES as $_locale) {
					$sql_array = array(
						"text"				=> _es($_POST[$_locale][$this->TEXT_FIELD_NAME]),
						"page_title"		=> _es($_POST[$_locale]["page_title"]),
						"page_heading"		=> _es($_POST[$_locale]["page_heading"]),
						"meta_keywords"		=> _es($_POST[$_locale]["meta_keywords"]),
						"meta_desc"			=> _es($_POST[$_locale]["meta_desc"]),
					);
					if (strlen($name)) {
						$sql_array["name"]	= _es($name);
					}
					if (isset($_POST['active'])) {
						$sql_array["active"]	= intval((bool)$_POST['active']); 
					}
					// Do update record
					if ($sql_array["text"]) {
						if(!empty($page_info[$_locale]['id'])){
							db()->UPDATE("static_pages", $sql_array, "`id`=".intval($page_info[$_locale]['id']));
						}else{
							db()->INSERT("static_pages", $sql_array = my_array_merge($sql_array, array(
								"name"		=> _es($name),
								"locale"	=> _es($_locale),
//								"active"	=> intval("1"),
							)));
						}
					}
				}

			} else {
				$sql_array = array(
					"text"				=> _es($_POST[$_def_locale][$this->TEXT_FIELD_NAME]),
					"page_title"		=> _es($_POST[$_def_locale]["page_title"]),
					"page_heading"		=> _es($_POST[$_def_locale]["page_heading"]),
					"meta_keywords"		=> _es($_POST[$_def_locale]["meta_keywords"]),
					"meta_desc"			=> _es($_POST[$_def_locale]["meta_desc"]),
				);
				if (strlen($name)) {
					$sql_array["name"]	= _es($name);
				}
				if (isset($_POST['active'])) {
					$sql_array["active"]	= intval((bool)$_POST['active']); 
				}
				// Do update record
				if ($sql_array["text"]) {
					db()->UPDATE("static_pages", $sql_array, "`id`=".intval($page_info[$_def_locale]['id']));
				}
			}
			if (main()->USE_SYSTEM_CACHE)	{
				cache()->refresh("static_pages_names");
			}
			// Return user back
			return js_redirect("./?object=".$_GET["object"]);
		}

		$DATA = $page_info;

		reset($DATA);
		$cur_data = current($DATA);
		$page_name 	= $cur_data["name"];
		$active 	= $cur_data["active"];

		$DATA = my_array_merge($DATA, $_POST);
		if ($this->MULTILANG_MODE) {
			$text_field_name = $this->TEXT_FIELD_NAME;
			$this->TEXT_FIELD_NAME = array();
			foreach ((array)$this->LANGUAGES as $_locale) {
				$this->TEXT_FIELD_NAME[$_locale] = $_locale."[".$text_field_name."]";
			}
		} else {
			$text_field_name = $this->TEXT_FIELD_NAME;
			$this->TEXT_FIELD_NAME = array();
			$this->TEXT_FIELD_NAME[$_def_locale] = $_def_locale."[".$text_field_name."]";
		}
		// Prepare text
//todo correct here
		foreach ((array)$DATA as $_locale => $page_data) {
			foreach ((array)$this->LANGUAGES as $_all_locale) {
				if($_all_locale == $_locale){
					$DATA[$_locale]["text_field_name"] = $this->TEXT_FIELD_NAME[$_locale];
					$_body = "";
					$_text = $DATA[$_locale]['text'];
					if ($this->_EDITOR_EXISTS) {
						$this->TEXT_EDITOR_OBJ->TEXT_FIELD_NAME = $this->TEXT_FIELD_NAME[$_locale];
// TODO: check if need here the same replace for &lt; and &gt; as for text mode
						$_body = $this->TEXT_EDITOR_OBJ->_display_code($_text);
					} else {
						$_body = _prepare_html(str_replace(array("&lt;", "&gt;"), array("&amp;lt;", "&amp;gt;"), $_text));
					}
					$DATA[$_locale]["body"] = $_body;
				}else{
/*					$_body = "";
					$_text = "";
					if ($this->_EDITOR_EXISTS) {
						$this->TEXT_EDITOR_OBJ->TEXT_FIELD_NAME = $this->TEXT_FIELD_NAME[$_all_locale];
// TODO: check if need here the same replace for &lt; and &gt; as for text mode
						$_body = $this->TEXT_EDITOR_OBJ->_display_code($_text);
					} else {
						$_body = _prepare_html(str_replace(array("&lt;", "&gt;"), array("&amp;lt;", "&amp;gt;"), $_text));
					}
/*					$DATA[$_all_locale] = array(
						$DATA[$_all_locale]["id"] => "",
						$DATA[$_all_locale]["cat_id"] => "",
						$DATA[$_all_locale]["name"] => "",
						$DATA[$_all_locale]["text"] => $_text,
						$DATA[$_all_locale]["page_title"] => "",
						$DATA[$_all_locale]["page_heading"] => "",
						$DATA[$_all_locale]["meta_keywords"] => "", 
						$DATA[$_all_locale]["meta_desc"] => "",
						$DATA[$_all_locale]["locale"] => $_all_locale,
						$DATA[$_all_locale]["active"] => "1",
						$DATA[$_all_locale]["text_field_name"] => $this->TEXT_FIELD_NAME[$_all_locale],
						$DATA[$_all_locale]["body"] => $_body,
					);*/
				}
			}
		}
//print_r($DATA);
		// Check if text editor is loaded correctly (for degrade gracefully behaviour)
		if ($this->_EDITOR_EXISTS && empty($_body)) {
			trigger_error("Empty return edit code in text_editor->_display_code", E_USER_WARNING);
		}

		// Process template
		$replace = array(
			"form_action"		=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".urlencode($page_name),
			"error_message"		=> _e(),
			"use_editor_code"	=> intval($this->_EDITOR_EXISTS && !empty($_body)),
			"page_info"			=> $DATA,
			"page_name"			=> $page_name,
			"multi_lang"		=> (int)$this->MULTILANG_MODE,
			"active_box"		=> $this->_box("active", $active),
			"back_url"			=> "./?object=".$_GET["object"],
		);
		return tpl()->parse($_GET["object"]."/edit_main", $replace);
	}

	/**
	* This function delete static page
	*/
	function delete() {
		$page_name = urldecode($_GET['id']);
		db()->query("DELETE FROM `".db('static_pages')."` WHERE `name`='".$page_name."'");

		if (main()->USE_SYSTEM_CACHE)	{
			cache()->refresh("static_pages_names");
		}
		// Return user back
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo $page_name;
		} else {
			return js_redirect("./?object=".$_GET["object"]);
		}
	}

	/**
	* Change activity status
	*/
	function activate () {
		// Get current info
		if (isset($_GET['id'])) {
			$page_info = db()->query_fetch("SELECT * FROM `".db('static_pages')."` WHERE `name`='"._es(_strtolower(urldecode($_GET['id'])))."'");
		}
		// Change activity
		if (!empty($page_info["id"])) {
			db()->UPDATE("static_pages", array("active" => (int)!$page_info["active"]), "`name`='"._es($page_info["name"])."'");
			if (main()->USE_SYSTEM_CACHE)	{
				cache()->refresh("static_pages_names");
			}
		}
		// Return user back
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo ($page_info["active"] ? 0 : 1);
		} else {
			return js_redirect("./?object=".$_GET["object"]);
		}
	}

	/**
	* View page
	*/
	function view() {
// TODO
		if (!empty($_GET['id'])) {
			$page_info = db()->query_fetch("SELECT * FROM `".db('static_pages')."` WHERE `name`='"._es(_strtolower(urldecode($_GET["id"])))."'");
		}
		if (empty($page_info["id"])) {
			return _e("No id!");
		}
		$this->PAGE_NAME	= _prepare_html($page_info["name"]);
		$this->PAGE_TITLE	= _prepare_html($page_info["title"]);
		// Show error message
		if (empty($page_info)) {
			common()->_raise_error(t("No such page!"));
			return _e();
		}
		// Process template
		$replace = array(
			"id"			=> intval($page_info["id"]),
			"name"			=> stripslashes($page_info["name"]),
			"content"		=> stripslashes($page_info["text"]), // DO NOT ADD _prepare_html here!
			"print_link"	=> "./?object=".$_GET["object"]."&action=print_view&id=".$page_info["id"],
			"pdf_link"		=> "./?object=".$_GET["object"]."&action=pdf_view&id=".$page_info["id"],
			"email_link"	=> "./?object=".$_GET["object"]."&action=email_page&id=".$page_info["id"],
		);
		return tpl()->parse($_GET["object"]."/view", $replace);
	}

	/**
	* Print View
	*/
	function print_view () {
		// Try to get page contents
		if (!empty($_GET['id'])) {
			$page_info = db()->query_fetch("SELECT * FROM `".db('static_pages')."` WHERE ".(is_numeric($_GET['id']) ? " `id`=".intval($_GET['id']) : " `name`='"._es(_strtolower($_GET["id"]))."'"));
		}
		$this->PAGE_NAME	= _prepare_html($page_info["name"]);
		$this->PAGE_TITLE	= _prepare_html($page_info["title"]);
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
		// Try to get page contents
		if (!empty($_GET['id'])) {
			$page_info = db()->query_fetch("SELECT * FROM `".db('static_pages')."` WHERE ".(is_numeric($_GET['id']) ? " `id`=".intval($_GET['id']) : " `name`='"._es(_strtolower($_GET["id"]))."'"));
		}
		$this->PAGE_NAME	= _prepare_html($page_info["name"]);
		$this->PAGE_TITLE	= _prepare_html($page_info["title"]);
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
		// Try to get page contents
		if (!empty($_GET['id'])) {
			$page_info = db()->query_fetch("SELECT * FROM `".db('static_pages')."` WHERE ".(is_numeric($_GET['id']) ? " `id`=".intval($_GET['id']) : " `name`='"._es(_strtolower($_GET["id"]))."'"));
		}
		$this->PAGE_NAME	= _prepare_html($page_info["name"]);
		$this->PAGE_TITLE	= _prepare_html($page_info["title"]);
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
		$pheader = t("Static pages");
		// Default subheader get from action name
		$subheader = _ucwords(str_replace("_", " ", $_GET["action"]));

		// Array of replacements
		$cases = array (
			//$_GET["action"] => {string to replace}
			"show"			=> "",
			"edit"			=> "",
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
