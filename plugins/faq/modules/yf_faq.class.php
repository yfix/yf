<?php

/**
* FAQ handler
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_faq extends yf_module {

	/** @var bool Use bb codes */
	public $USE_BB_CODES		= false;
	/** @var bool */
	public $USE_CAPTCHA		= true;
	/** @var bool */
	public $COUNT_VIEWS		= true;
	/** @var bool */
	public $SKIP_EMPTY_CATS	= true;
	/** @var string @conf_skip Params for the comments */
	public $_comments_params	= array(
		"return_action" => "view",
		"object_name"	=> "faq",
	);

	/**
	* YF module constructor
	*/
	function _init () {
		$this->CATS_OBJ			= _class("cats");
		$this->CATS_OBJ->_default_cats_block = "faq_cats";
		$this->_faqs_cats		= $this->CATS_OBJ->_get_items_array();
		$this->_cats_for_select = $this->CATS_OBJ->_prepare_for_box("", 0);
		// Array of select boxes to process
		$this->_boxes = array(
			"cat_id"	=> 'select_box("cat_id", $this->_cats_for_select, $selected, false, 2, "", false)',
		);
	}

	/**
	* Default method
	*/
	function show () {
		// Get faqs
		$Q = db()->query("SELECT * FROM ".db('faq_texts')." WHERE status='active' ORDER BY cat_id,priority DESC");
		while ($A = db()->fetch_assoc($Q)) {
			$faqs_texts[$A["id"]]	= $A;
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
					"id"		=> intval($text_info["id"]),
					"question"	=> _prepare_html($text_info["question_text"]),
					"answer"	=> _prepare_html($text_info["answer_text"]),
					"add_date"	=> _format_date($text_info["add_date"], "long"),
					"edit_date"	=> _format_date($text_info["edit_date"], "long"),
					"view_link"	=> "./?object=".'faq'."&action=view&id=".intval($text_info["id"]),
					"num_views"	=> intval($text_info["views"]),
				);
				$cur_texts .= tpl()->parse('faq'."/text_item", $replace3);
			}
			// Skip empty categories
			if (empty($cur_texts) && $this->SKIP_EMPTY_CATS) {
				$this->_skipped_cats[$cur_cat_id] = $cur_cat_id;
				continue;
			}
			// Add category to the output array
			$cats_items[] = array(
				"cat_id"		=> intval($cur_cat_id),
				"cat_level"		=> intval($cur_cat_info["level"]),
				"cat_name"		=> _prepare_html($cur_cat_info["name"]),
				"faqs_texts"	=> $cur_texts,
			);
		}
		// Process template
		$replace = array(
			"cats_items"	=> $cats_items,
			"cats_header"	=> $this->_display_categories(),
			"pages"			=> $pages,
			"total"			=> intval($total),
		);
		return tpl()->parse('faq'."/main_page", $replace);
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
		$GLOBALS['_faq_question'] = $text_info["question_text"];
		$GLOBALS['_faq_category'] = $this->_faqs_cats[$text_info["cat_id"]]["name"];
		// Count number of views
		if ($this->COUNT_VIEWS) {
			db()->_add_shutdown_query("UPDATE ".db('faq_texts')." SET views=views+1 WHERE id=".intval($text_info["id"]));
		}
		// Process template
		$replace = array(
			"id"				=> intval($text_info["id"]),
			"cat_name"			=> _prepare_html($this->_faqs_cats[$text_info["cat_id"]]["name"]),
			"cat_link"			=> "./?object=".'faq'."&action=show#cat_id_".intval($text_info["cat_id"]),
			"question"			=> $text_info["question_text"],
			"answer"			=> $text_info["answer_text"],
			"add_date"			=> _format_date($text_info["add_date"], "long"),
			"edit_date"			=> _format_date($text_info["edit_date"], "long"),
			"num_views"			=> intval($text_info["views"]),
			"comments"			=> $this->_view_comments(),
		);
		return tpl()->parse('faq'."/view", $replace);
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
				"url"		=> "./?object=".'faq'."&action=".$_GET["action"]."#cat_id_".$cur_item_id,
				"name"		=> _prepare_html($cur_item_info["name"]),
				"level"		=> intval($cur_item_info["level"]),
				"padding"	=> intval($cur_item_info["level"] * 20),
			);
			$items_to_display .= tpl()->parse('faq'."/cat_item", $replace2);
		}
		return $items_to_display;
	}

	/**
	* Hook for the site_map
	*/
	function _site_map_items ($OBJ = false) {
		if (!is_object($OBJ)) {
			return false;
		}
		// Main page		
		$OBJ->_store_item(array(
			"url"	=> "./?object=faq",
		));
		// Single FAQ
		$sql = "SELECT id FROM ".db('faq_texts')."";
		$Q = db()->query($sql);
		while ($A = db()->fetch_assoc($Q)) {
			$OBJ->_store_item(array(
				"url"	=> "./?object=faq&action=view&id=".$A["id"],
			));
		}
		return true;
	}
}
