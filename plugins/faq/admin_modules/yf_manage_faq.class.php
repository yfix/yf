<?php

// FAQ management module
class yf_manage_faq extends yf_module {

	/** @var bool Use bb codes */
	public $USE_BB_CODES		= false;
	/** @var bool */
	public $USE_CAPTCHA		= false;
	/** @var bool */
	public $COUNT_VIEWS		= true;
	/** @var bool */
	public $SKIP_EMPTY_CATS	= false;
	/** @var array @conf_skip Params for the comments */
	public $_comments_params	= array(
		"return_action" => "view",
		"object_name"	=> "faq",
	);

	
	// Constructor
	function yf_manage_faq() {
		main()->USER_ID = $_GET['user_id'];
		// Get current account types
		$this->_account_types	= main()->get_data("account_types");
		// Array of boxes
		$this->_boxes = array(
			"cat_id"	=> 'select_box("cat_id",	$this->_cats_for_select, $selected, false, 2, "", false)',
			"status"	=> 'radio_box("status",	$this->_faqs_statuses,	$selected, 0, 2, "", false)',
		);
		// Array of available faqs statuses
		$this->_faqs_statuses = array(
			"active"	=> t("active"),
			"suspended"	=> t("suspended"),
		);
		// Prepare categories
		$this->CATS_OBJ = _class("cats");
		$this->CATS_OBJ->_default_cats_block = "faq_cats";
		$this->_faqs_cats		= $this->CATS_OBJ->_get_items_array();
		$this->_cats_for_select = $this->CATS_OBJ->_prepare_for_box("", 0);
		// Init text editor
		$this->TEXT_EDITOR_OBJ = _class("text_editor");
		$this->_EDITOR_EXISTS = is_object($this->TEXT_EDITOR_OBJ);
		if ($this->_EDITOR_EXISTS) {
			$this->TEXT_EDITOR_OBJ->TEXT_FIELD_NAME = "answer_text";
		}
		// Prepare filter data
		if ($this->USE_FILTER) {
			$this->_prepare_filter_data();
		}
	}

	/**
	* Default method
	*/
	function show () {
		// Get faqs
		$Q = db()->query("SELECT * FROM ".db('faq_texts')." ORDER BY cat_id,priority DESC");
		while ($A = db()->fetch_assoc($Q)) {
			$faqs_texts[$A["id"]]	= $A;
		}
		// Do get number of comments for each faq record
		$Q = db()->query("SELECT object_id, COUNT(id) AS num_comments FROM ".db('comments')." WHERE object_name='faq' GROUP BY object_id");
		while ($A = db()->fetch_assoc($Q)) {
			$num_comments[$A["object_id"]] = $A["num_comments"];
		}
		// Process categories
		foreach ((array)$this->_faqs_cats as $cur_cat_info) {
			$cur_cat_id = $cur_cat_info["id"];
			if (empty($cur_cat_id)) {
				continue;
			}
			$cur_texts = "";
			// Get texts for the current category
			foreach ((array)$faqs_texts as $text_id => $text_info) {
				if ($text_info["cat_id"] != $cur_cat_id) {
					continue;
				}
				// Process template
				$replace3 = array(
					"id"			=> intval($text_info["id"]),
					"question"		=> _prepare_html($text_info["question_text"]),
					"answer"		=> _prepare_html($text_info["answer_text"]),
					"add_date"		=> _format_date($text_info["add_date"], "long"),
					"edit_date"		=> _format_date($text_info["edit_date"], "long"),
					"view_link"		=> "./?object=".$_GET["object"]."&action=view&id=".intval($text_info["id"]),
					"edit_link"		=> "./?object=".$_GET["object"]."&action=edit&id=".intval($text_info["id"]),
					"delete_link"	=> "./?object=".$_GET["object"]."&action=delete&id=".intval($text_info["id"]),
					"status"		=> _prepare_html($text_info["status"]),
					"num_views"		=> intval($text_info["views"]),
					"num_comments"	=> intval($num_comments[$text_info["id"]]),
				);
				$cur_texts .= tpl()->parse($_GET["object"]."/text_item", $replace3);
			}
			// Skip empty categories
			if (empty($cur_texts) && $this->SKIP_EMPTY_CATS) {
				$this->_skipped_cats[$cur_cat_id] = $cur_cat_id;
				continue;
			}
			// Add category to the output array
			$cats_items[] = array(
				"cat_id"		=> intval($cur_cat_id),
				"cat_level"		=> intval($cur_cat_info["name"]),
				"cat_name"		=> _prepare_html($cur_cat_info["name"]),
				"faqs_texts"	=> $cur_texts,
			);
		}
		// Process template
		$replace = array(
			"cats_items"	=> $cats_items,
			"cats_header"	=> $this->_display_categories(),
			"pages"			=> $pages,
			"total"			=> intval(count($faqs_texts)),
			"edit_cats_link"=> "./?object=category_editor&action=show_items&id=".$this->CATS_OBJ->_get_cat_id_by_name(),
			"add_link"		=> "./?object=".$_GET["object"]."&action=add",
		);
		return tpl()->parse($_GET["object"]."/main_page", $replace);
	}

	/**
	* View single FAQ item
	*/
	function view () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return _e(t("No id!"));
		}
		// Get text info
		if (empty($text_info)) {
			$text_info = db()->query_fetch("SELECT * FROM ".db('faq_texts')." WHERE id=".intval($_GET["id"]));
		}
		if (empty($text_info)) {
			return _e(t("No such text!"));
		}
		// Process template
		$replace = array(
			"id"				=> intval($text_info["id"]),
			"cat_name"			=> _prepare_html($this->_faqs_cats[$text_info["cat_id"]]["name"]),
			"cat_link"			=> "./?object=".$_GET["object"]."&action=show#cat_id_".intval($text_info["cat_id"]),
			"question"			=> $text_info["question_text"],
			"answer"			=> $text_info["answer_text"],
			"add_date"			=> _format_date($text_info["add_date"], "long"),
			"edit_date"			=> _format_date($text_info["edit_date"], "long"),
			"views"				=> intval($text_info["views"]),
			"comments"			=> $this->_view_comments(),
			"edit_link"			=> "./?object=".$_GET["object"]."&action=edit&id=".intval($text_info["id"]),
			"delete_link"		=> "./?object=".$_GET["object"]."&action=delete&id=".intval($text_info["id"]),
			"status"			=> _prepare_html($text_info["status"]),
		);
		return tpl()->parse($_GET["object"]."/view", $replace);
	}

	/**
	* Default method
	*/
	function _display_categories () {
		$items_to_display = "";
		// Process items
		foreach ((array)$this->_faqs_cats as $cur_item_info) {
			$cur_item_id = $cur_item_info["id"];
			if (empty($cur_item_id)) {
				continue;
			}
			// Skip empty category
			if (isset($this->_skipped_cats[$cur_item_id]) && $this->SKIP_EMPTY_CATS) {
				continue;
			}
			// Process template
			$replace2 = array(
				"url"		=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."#cat_id_".$cur_item_id,
				"name"		=> _prepare_html($cur_item_info["name"]),
				"level"		=> intval($cur_item_info["level"]),
				"padding"	=> intval($cur_item_info["level"] * 20),
			);
			$items_to_display .= tpl()->parse($_GET["object"]."/cat_item", $replace2);
		}
		return $items_to_display;
	}

	
	// Edit record
	function edit () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return _e(t("No id"));
		}
		// Try to get record info
		$text_info = db()->query_fetch("SELECT * FROM ".db('faq_texts')." WHERE id=".intval($_GET["id"]));
		if (empty($text_info)) {
			return _e(t("No such record"));
		}
		// Try to get given user info
		if (!empty($user_info["id"])) {
			$user_info = db()->query_fetch("SELECT id,name,nick FROM ".db('user')." WHERE id=".intval($text_info["user_id"]));
		}
		// Check posted data and save
		if (count($_POST) > 0) {
			// Check for errors
			if (!common()->_error_exists()) {
				db()->UPDATE("faq_texts", array(
					"cat_id"		=> intval($_POST["cat_id"]),
					"question_text"	=> _es($_POST["question_text"]),
					"answer_text"	=> _es($_POST["answer_text"]),
					"edit_date"		=> time(),
					"status"		=> _es($_POST["status"]),
					"priority"		=> intval($_POST["priority"]),
				), "id=".intval($_GET["id"]));
				// Return user back
				return js_redirect("./?object=".$_GET["object"]);
			}
		}
		// Fill POST data
		foreach ((array)$text_info as $k => $v) {
			$DATA[$k] = isset($_POST[$k]) ? $_POST[$k] : $v;
		}
		// Cleanup data arrays
		unset($this->_faqs_cats[" "]);
		// Prepare text
		$text_to_edit = $this->_EDITOR_EXISTS ? $this->TEXT_EDITOR_OBJ->_display_code($DATA["answer_text"]) : _prepare_html($DATA["answer_text"], 0);
		// Display form
		if (!$_POST || common()->_error_exists()) {
			$replace = array(
				"for_edit"			=> 1,
				"form_action"		=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$_GET["id"],
				"error_message"		=> _e(),
				"statuses_box"		=> $this->_box("status", $DATA["status"]),
				"cats_box"			=> $this->_box("cat_id", $DATA["cat_id"]),
				"cat_name"			=> _prepare_html($this->_faqs_cats[$DATA["cat_id"]]),
				"question_text"		=> _prepare_html($DATA["question_text"], 0),
				"use_editor_code"	=> intval($this->_EDITOR_EXISTS && !empty($text_to_edit)),
				"answer_text"		=> $text_to_edit,
				"views"				=> intval($DATA["views"]),
				"priority"			=> intval($DATA["priority"]),
				"status"			=> $this->_faqs_statuses[$DATA["status"]],
				"add_date"			=> !empty($DATA["add_date"]) ? _format_date($DATA["add_date"], "long") : "",
				"edit_date"			=> !empty($DATA["edit_date"]) ? _format_date($DATA["edit_date"], "long") : "",
				"edit_cats_link"	=> "./?object=category_editor&action=show_items&id=".$this->CATS_OBJ->_get_cat_id_by_name(),
				"back_link"			=> "./?object=".$_GET["object"],
			);
			return tpl()->parse($_GET["object"]."/edit", $replace);
		}
	}

	
	// Add record
	function add () {
		// Check posted data and save
		if (count($_POST) > 0) {
			// Check for errors
			if (!common()->_error_exists()) {
				db()->INSERT("faq_texts", array(
					"author_id"		=> intval($_SESSION["admin_id"]),
					"cat_id"		=> intval($_POST["cat_id"]),
					"question_text"	=> _es($_POST["question_text"]),
					"answer_text"	=> _es($_POST["answer_text"]),
					"add_date"		=> time(),
					"edit_date"		=> "",
					"status"		=> _es($_POST["status"]),
					"priority"		=> intval($_POST["priority"]),
				));
				// Return user back
				return js_redirect("./?object=".$_GET["object"]);
			}
		}
		// Fill POST data
		$DATA = &$_POST;
		// Cleanup data arrays
		unset($this->_articles_cats[" "]);
		// Prepare text
		$text_to_edit = $this->_EDITOR_EXISTS ? $this->TEXT_EDITOR_OBJ->_display_code($DATA["answer_text"]) : _prepare_html($DATA["answer_text"], 0);
		// Display form
		if (!$_POST || common()->_error_exists()) {
			$replace = array(
				"for_edit"			=> 0,
				"form_action"		=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$_GET["id"],
				"error_message"		=> _e(),
				"statuses_box"		=> $this->_box("status", !empty($DATA["status"]) ? $DATA["status"] : "active"),
				"cats_box"			=> $this->_box("cat_id", $DATA["cat_id"]),
				"cat_name"			=> _prepare_html($this->_faqs_cats[$DATA["cat_id"]]),
				"question_text"		=> _prepare_html($DATA["question_text"], 0),
				"use_editor_code"	=> intval($this->_EDITOR_EXISTS && !empty($text_to_edit)),
				"answer_text"		=> $text_to_edit,
				"views"				=> 0,
				"priority"			=> 0,
				"status"			=> $this->_faqs_statuses[$DATA["status"]],
				"add_date"			=> "",
				"edit_date"			=> "",
				"edit_cats_link"	=> "./?object=category_editor&action=show_items&id=".$this->CATS_OBJ->_get_cat_id_by_name(),
				"back_link"			=> "./?object=".$_GET["object"],
			);
			return tpl()->parse($_GET["object"]."/edit", $replace);
		}
	}

	
	// Do delete record
	function delete () {
		$_GET["id"] = intval($_GET["id"]);
		// Do delete record
		if (!empty($_GET["id"])) {
			db()->query("DELETE FROM ".db('faq_texts')." WHERE id=".intval($_GET["id"])." LIMIT 1");
			// Delete linked comments
			db()->query("DELETE FROM ".db('comments')." WHERE object_name='faq' AND object_id=".intval($_GET["id"]));
		}
		// Return user back
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo $_GET["id"];
		} else {
			return js_redirect("./?object=".$_GET["object"]);
		}
	}

	
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
				"name"	=> "Edit categories",
				"url"	=> "./?object=".$_GET["object"],
			),
			array(
				"name"	=> "Add new entry",
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
		$pheader = t("Manage FAQ")." (".t("Frequently Asked Questions").")";
		// Default subheader get from action name
		$subheader = _ucwords(str_replace("_", " ", $_GET["action"]));

		// Array of replacements
		$cases = array (
			//$_GET["action"] => {string to replace}
			"show"					=> "",
			"add"					=> "Add new question",
			"edit"					=> "Edit question",
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

	function _hook_widget__faq_list ($params = array()) {
// TODO
	}
}
