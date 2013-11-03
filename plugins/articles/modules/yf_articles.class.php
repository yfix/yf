<?php

/**
* User articles manager
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_articles extends yf_module {

	/** @var int Maximum length of title field */
	public $MAX_TITLE_LENGTH			= 100;
	/** @var int Maximum length of summary field */
	public $MAX_SUMMARY_LENGTH			= 1000;
	/** @var int Maximum length of full text field */
	public $MAX_FULL_TEXT_LENGTH		= 50000;
	/** @var int Maximum length of credentials field  */
	public $MAX_CREDENTIALS_LENGTH		= 1000;
	/** @var int Number of allowed articles to post by user (set to "0" for unlimited) */
	public $MAX_USER_ARTICLES			= 0;
	/** @var bool Use bb codes */
	public $USE_BB_CODES				= true;
	/** @var bool Use captcha */
	public $USE_CAPTCHA				= true;
	/** @var bool All articles search filter on/off */
	public $USE_FILTER					= true;
	/** @var bool Count view or not */
	public $COUNT_VIEWS				= true;
	/** @var int Number of most active authors for the stats page */
	public $STATS_NUM_MOST_ACTIVE		= 10;
	/** @var int Number of latest entries for the stats page */
	public $STATS_NUM_LATEST			= 10;
	/** @var int Number of most commented articles for the stats page */
	public $STATS_NUM_MOST_COMMENTED	= 10;
	/** @var int Number of most read articles for the stats page */
	public $STATS_NUM_MOST_READ		= 10;
	/** @var int Number of records to show on one page for "view_cat", "view_user" */
//	public $VIEW_ALL_ON_PAGE			= 20;
	public $VIEW_ALL_ON_PAGE			= 5;
	/** @var bool allow delete comments */
	public $ALLOW_DELETE_COMMENTS		= true;
	/** @var bool allow delete comments */
	public $SEARCH_ONLY_MEMBER			= true;
	/** @var array @conf_skip Params for the comments */
	public $_comments_params			= array(
		"return_action" => "view",
	);
	/** @var int */
	public $NUM_RSS 	= 10;

	/**
	* YF module constructor
	*/
	function _init () {
		$this->_boxes = array(
			"cat_id"		=> 'select_box("cat_id", $this->_cats_for_select, $selected, false, 2, "style=\"width:100%;\"", false)',
		);
		$this->_articles_statuses = array(
			"new"		=> t("new"),
			"edited"	=> t("edited"),
			"suspended"	=> t("suspended"),
			"active"	=> t("active"),
		);
		$this->CATS_OBJ = _class("cats");
		$this->_articles_cats	= _class("cats")->_get_items_array();
		$this->_cats_for_select	= _class("cats")->_prepare_for_box("", 0);
	}

	/**
	* Main module page (stats will be here)
	*/
	function show () {
		return $this->_stats();
	}

	/**
	* Display articles statistics
	*/
	function _stats () {
		$OBJ = $this->_load_sub_module("articles_stats");
		return is_object($OBJ) ? $OBJ->_show_stats() : "";
	}
	
	/**
	* Display item for the stats
	*/
	function _process_stats_item($info_array = array()) {
		$OBJ = $this->_load_sub_module("articles_stats");
		return is_object($OBJ) ? $OBJ->_process_stats_item($info_array) : "";
	}

	/**
	* Show all articles list with links to full texts
	*/
	function search () {
		// Check CPU Load
		if (conf('HIGH_CPU_LOAD') == 1) {
			return common()->server_is_busy();
		}
		$OBJ = $this->_load_sub_module("articles_search");
		return is_object($OBJ) ? $OBJ->_go() : "";
	}

	/**
	* View articles in the given category (friendly for search engines)
	*/
	function view_cat () {
		if (empty($_GET["id"])) {
			return _e(t("No category id!"));
		}
		// Try to find such category
		$cat_id = is_numeric($_GET["id"]) ? intval($_GET["id"]) : $this->_get_cat_id_by_url($_GET["id"]);
		// Check if we found such category
		if (empty($cat_id)) {
			return _e(t("No such category!"));
		}
		// Do save filter
		$_REQUEST["cat_id"] = $cat_id;
		$this->clear_filter(1);
		$this->save_filter(1);
		// Custom articles list(search) header
//		$this->_custom_search_header	= $this->_articles_cats[$cat_id]["name"];
		$this->_custom_search_header	= $this->CATS_OBJ->_get_nav_by_item_id($cat_id);
		// Get sub categories
		$sub_cats_array	= $this->CATS_OBJ->_recursive_get_children_ids($cat_id);
		foreach ((array)$sub_cats_array as $sub_cat_id_1 => $sub_cat_items_1) {
			$replace = array(
				"cat_link"	=> $this->_cat_link($sub_cat_id_1),
				"cat_name"	=> _prepare_html($this->_articles_cats[$sub_cat_id_1]["name"]),
			);
			$sub_cats_text .= tpl()->parse(__CLASS__."/sub_cat_item", $replace);
		}
		$this->_custom_search_content	= $sub_cats_text;
		// Display results
		$OBJ = $this->_load_sub_module("articles_search");
		return is_object($OBJ) ? $OBJ->_go(0) : "";
	}

	/**
	* View articles by the given user id
	*/
	function view_by_user () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return _e(t("No user id!"));
		}
		$user_id = $_GET["id"];
		// Try to get get user info
		$user_info = user($user_id);
		if (empty($user_info)) {
			return _e(t("No such user!"));
		}
		// Do save filter
		$_REQUEST["user_id"] = $user_id;
		$this->clear_filter(1);
		$this->save_filter(1);
		// Custom articles list(search) header
		$this->_custom_search_header = _display_name($user_info);
		// Display results
		$OBJ = $this->_load_sub_module("articles_search");
		return is_object($OBJ) ? $OBJ->_go(0) : "";
	}

	/**
	* View article by the given short_url
	*/
	function view_by_name () {
		if (empty($_GET["id"])) {
			return _e(t("Missing article name"));
		}
		// Get article info
		$article_info = db()->query_fetch("SELECT * FROM ".db('articles_texts')." WHERE short_url='"._es($_GET["id"])."'");
		if (empty($article_info)) {
			return _e(t("No such article!"));
		}
		// Re-map id
		$_GET["id"] = $article_info["id"];
		// Display article
		return $this->view($article_info);
	}

	/**
	* View single artcile 
	*/
	function view ($article_info = array()) {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return _e(t("No id!"));
		}
		// Get article info
		if (empty($article_info)) {
			$article_info = db()->query_fetch("SELECT * FROM ".db('articles_texts')." WHERE id=".intval($_GET["id"]));
		}
		if (empty($article_info)) {
			return _e(t("No such article!"));
		}
		
		$ids = _class_safe("unread")->_set_read("articles", $_GET["id"]);
		
		$IS_OWN_ARTICLE = false;
		// Do get author info
		if (!empty($article_info["user_id"])) {
			if ($article_info["user_id"] == main()->USER_ID) {
				$author_info = $this->_user_info;
				$IS_OWN_ARTICLE = true;
			} else {
				$author_info = user($article_info["user_id"]);
			}
		}
		$author_name = !empty($article_info["author_name"]) ? $article_info["author_name"] : _display_name($author_info);
		$article_info["prepared_author_name"] = $author_name;
		$GLOBALS["_article_info"] = $article_info;
		// Check if author exists
		if (empty($author_info)) {
//			return _e(t("No such user!"));
		}
		if (!isset($GLOBALS['user_info'])) {
			$GLOBALS['user_info'] = $author_info;
		}
		// Count number of views
		if ($this->COUNT_VIEWS) {
			db()->_add_shutdown_query("UPDATE ".db('articles_texts')." SET views=views+1 WHERE id=".intval($article_info["id"]));
		}
		// Process user reputation
		$reput_text = "";
		if (!empty($article_info["user_id"])) {
			$REPUT_INFO	= _class_safe("reputation")->_get_user_reput_info($article_info["user_id"]);
			$reput_text	= _class_safe("reputation")->_show_for_user($article_info["user_id"], $REPUT_INFO, 1, array("articles_texts", $article_info["id"]));

			if (!empty(main()->USER_ID) && !empty($article_info["user_id"]) && !$IS_OWN_ARTICLE) {
				$SHOW_REPUT_LINK = true;
			}
		}

		// Process tags
		$this->_tags = $this->_show_tags($_GET["id"]);

		// Process template
		$replace = array(
			"id"				=> intval($article_info["id"]),
			"user_id"			=> intval($article_info["user_id"]),
			"user_name"			=> _prepare_html($author_name),
			"user_profile_link"	=> $article_info["is_own_article"] && !empty($article_info["user_id"]) && !empty($author_info) ? _profile_link($author_info) : "",
			"user_avatar"		=> _show_avatar($article_info["user_id"], $author_info),
			"is_own_article"	=> intval((bool)$article_info["is_own_article"]),
			"cat_name"			=> _prepare_html($this->_articles_cats[$article_info["cat_id"]]["name"]),
			"cat_link"			=> $this->_cat_link($article_info["cat_id"]),
			"title"				=> _prepare_html($article_info["title"]),
			"summary"			=> $this->_format_text($article_info["summary"]),
			"full_text"			=> $this->_format_text($article_info["full_text"]),
			"credentials"		=> $this->_format_text($article_info["credentials"]),
			"add_date"			=> _format_date($article_info["add_date"], "long"),
			"edit_date"			=> _format_date($article_info["edit_date"], "long"),
			"views"				=> intval($article_info["views"]),
			"status"			=> $this->_articles_statuses[$article_info["status"]],
			"edit_link"			=> $IS_OWN_ARTICLE ? "./?object=".'articles'."&action=edit&id=".$article_info["id"] : "",
			"delete_link"		=> $IS_OWN_ARTICLE ? "./?object=".'articles'."&action=delete&id=".$article_info["id"] : "",
			"comments"			=> $this->_view_comments(),
			"social_bookmarks"	=> _class('graphics')->_show_bookmarks_button($article_info["short_url"], "./?object=".'articles'."&action=view_by_name&id=".$article_info["short_url"]),
			"reput_text"		=> $reput_text,
			"vote_popup_link"	=> $SHOW_REPUT_LINK ? process_url("./?object=reputation&action=vote_popup&id=".$article_info["user_id"]."&page=".$_GET["object"]."--".$article_info["id"]) : "",
			"tags_block"		=> $this->_tags[intval($article_info["id"])],
		);
		return tpl()->parse('articles'."/view", $replace);
	}

	/**
	* Manage own articles
	*/
	function manage () {
		if (empty(main()->USER_ID)) {
			return _error_need_login();
		}
		if (isset($_GET["id"])) {
			$_GET["page"] = $_GET["id"];
			unset($_GET["id"]);
		}
		// Connect pager
		$sql = "SELECT * FROM ".db('articles_texts')." WHERE user_id=".intval(main()->USER_ID)." ORDER BY add_date DESC";
		list($add_sql, $pages, $total) = common()->divide_pages($sql);
		// Process records
		$Q = db()->query($sql. $add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$replace2 = array(
				"bg_class"		=> !(++$i % 2) ? "bg1" : "bg2",
				"num"			=> ++$num,
				"title"			=> _prepare_html($A["title"]),
				"author_name"	=> _prepare_html($A["author_name"]),
				"is_own_article"=> intval((bool)$A["is_own_article"]),
				"summary"		=> _prepare_html(substr($A["summary"], 0, 200)),
				"full_text"		=> _prepare_html(substr($A["full_text"], 0, 200)),
				"status"		=> _prepare_html($this->_articles_statuses[$A["status"]]),
				"views"			=> intval($A["views"]),
				"add_date"		=> _format_date($A["add_date"], "long"),
				"edit_date"		=> !empty($A["edit_date"]) ? _format_date($A["edit_date"]) : "",
				"cat_name"		=> 	_prepare_html($this->_articles_cats[$A["cat_id"]]["name"]),
				"cat_link"		=> $this->_cat_link($A["cat_id"]),
				"view_link"		=> "./?object=".'articles'."&action=view&id=".$A["id"],
				"edit_link"		=> "./?object=".'articles'."&action=edit&id=".$A["id"],
				"delete_link"	=> "./?object=".'articles'."&action=delete&id=".$A["id"],
			);
			$items .= tpl()->parse('articles'."/manage_item", $replace2);
		}
		// Process template
		$replace = array(
			"items"					=> $items,
			"pages"					=> $pages,
			"total"					=> intval($total),
			"add_link"				=> "./?object=".'articles'."&action=add",
			"comments_search_link"	=> "./?object=".'articles'."&action=search_comments",
		);
		return tpl()->parse('articles'."/manage_main", $replace);
	}

	/**
	* Manage comments
	*/
	function search_comments () {
		$OBJ = $this->_load_sub_module("articles_search_comments");
		return $OBJ->search_comments();
	}
	
	function delete_articles_comment () {
		$OBJ = $this->_load_sub_module("articles_search_comments");
		return $OBJ->_delete();
	} 

	/**
	* Edit article
	*/
	function edit () {
		if (empty(main()->USER_ID)) {
			return _error_need_login();
		}
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return _e(t("No id!"));
		}
		// Get article info
		$article_info = db()->query_fetch("SELECT * FROM ".db('articles_texts')." WHERE id=".intval($_GET["id"])." AND user_id=".intval(main()->USER_ID));
		if (empty($article_info)) {
			return _e(t("No such article!"));
		}
		// Do save content
		if ($_POST) {
			// Do check captcha (if needed)
			if (module('articles')->USE_CAPTCHA) {
				main()->_execute('articles', "_captcha_check");
			}
			// Author name is required
			if (empty($_POST["author_name"])) {
				_re(t("Author Name is required"));
			}
			// Article category, title, summary and full_text are required
			if (empty($_POST["title"])) {
				_re(t("Title is required"));
			} elseif (!empty($this->MAX_TITLE_LENGTH) && strlen($_POST["title"]) > $this->MAX_TITLE_LENGTH) {
				_re(t("Title length (@item1) is more than allowed length (@item2)", array("@item1" => strlen($_POST["title"]), "@item2" => intval($this->MAX_TITLE_LENGTH))));
			}
			if (empty($_POST["summary"])) {
				_re(t("Summary is required"));
			} elseif (!empty($this->MAX_SUMMARY_LENGTH) && strlen($_POST["summary"]) > $this->MAX_SUMMARY_LENGTH) {
				_re("Summary length (".strlen($_POST["summary"]).") is more than allowed length (".intval($this->MAX_SUMMARY_LENGTH).")");
			}
			if (empty($_POST["full_text"])) {
				_re(t("Text is required"));
			} elseif (!empty($this->MAX_FULL_TEXT_LENGTH) && strlen($_POST["full_text"]) > $this->MAX_FULL_TEXT_LENGTH) {
				_re("Text length (".strlen($_POST["full_text"]).") is more than allowed length (".intval($this->MAX_FULL_TEXT_LENGTH).")");
			}
			if (!empty($this->_articles_cats) && (empty($_POST["cat_id"])) || !isset($this->_articles_cats[$_POST["cat_id"]])) {
				_re(t("Please select article category"));
			}
			// Check credentials length
			if (!empty($this->MAX_CREDENTIALS_LENGTH) && strlen($_POST["credentials"]) > $this->MAX_CREDENTIALS_LENGTH) {
				_re("Credentials length (".strlen($_POST["credentials"]).") is more than allowed length (".intval($this->MAX_CREDENTIALS_LENGTH).")");
			}
// TODO: add checking max number of articles
			// Get new status
			if (in_array($article_info["status"], array("new","edited","active"))) {
				$NEW_STATUS = "edited";
// FIXME: maybe we need to block suspended articles
			} else {
				$NEW_STATUS = "suspended";
			}
// TODO: add check if user is banned for posted articles
			// Check for errors
			if (!common()->_error_exists()) {
				// Do close BB Codes (if needed)
				if ($this->USE_BB_CODES) {
					$BB_CODES_OBJ = _class("bb_codes");
					if (is_object($BB_CODES_OBJ)) {
						$_POST["summary"]		= $BB_CODES_OBJ->_force_close_bb_codes($_POST["summary"]);
						$_POST["full_text"]		= $BB_CODES_OBJ->_force_close_bb_codes($_POST["full_text"]);
						$_POST["credentials"]	= $BB_CODES_OBJ->_force_close_bb_codes($_POST["credentials"]);
					}
				}
				db()->UPDATE("articles_texts", array(
					"cat_id"		=> intval($_POST["cat_id"]),
					"author_name"	=> _es($_POST["author_name"]),
					"is_own_article"=> intval(!$_POST["not_own_article"]),
					"title"			=> _es($_POST["title"]),
					"summary"		=> _es($_POST["summary"]),
					"full_text"		=> _es($_POST["full_text"]),
					"credentials"	=> _es($_POST["credentials"]),
					"edit_date"		=> time(),
					"status"		=> _es($NEW_STATUS),
					"short_url"		=> $this->_create_short_url($_POST["title"]),
				), "id=".intval($_GET["id"]));
				// Update user stats
				_class("user_stats")->_update(array("user_id" => main()->USER_ID));
				// Return user back
				return js_redirect("./?object=".'articles'."&action=manage");
			}
		}
		// Fill POST data
		foreach ((array)$article_info as $k => $v) {
			$DATA[$k] = isset($_POST[$k]) ? $_POST[$k] : $v;
		}
		// Display form
		if (!$_POST || common()->_error_exists()) {
			$replace = array(
				"form_action"	=> "./?object=".'articles'."&action=".$_GET["action"]."&id=".$_GET["id"],
				"error_message"	=> _e(),
				"cats_box"		=> $this->_box("cat_id", $DATA["cat_id"]),
				"cat_name"		=> _prepare_html($this->_articles_cats[$DATA["cat_id"]]["name"]),
				"author_name"	=> _prepare_html($DATA["author_name"], 0),
				"is_own_article"=> intval((bool)$DATA["is_own_article"]),
				"title"			=> _prepare_html($DATA["title"], 0),
				"summary"		=> _prepare_html($DATA["summary"], 0),
				"full_text"		=> _prepare_html($DATA["full_text"], 0),
				"credentials"	=> _prepare_html($DATA["credentials"], 0),
				"add_date"		=> _format_date($DATA["add_date"]),
				"views"			=> intval($DATA["views"]),
				"status"		=> $this->_articles_statuses[$DATA["status"]],
				"for_edit"		=> 1,
				"use_captcha"	=> intval((bool)module('articles')->USE_CAPTCHA),
				"captcha_block"	=> main()->_execute('articles', "_captcha_block"),
				"bb_codes_block_full_text"	=> $this->USE_BB_CODES ? _class("bb_codes")->_display_buttons(array("unique_id" => "bb_full_text")) : "",
				"bb_codes_block_summary"	=> $this->USE_BB_CODES ? _class("bb_codes")->_display_buttons(array("unique_id" => "bb_summary")) : "",
				"bb_codes_block_cred"		=> $this->USE_BB_CODES ? _class("bb_codes")->_display_buttons(array("unique_id" => "bb_cred")) : "",
				"max_title_length"			=> intval($this->MAX_TITLE_LENGTH),
				"max_summary_length"		=> intval($this->MAX_SUMMARY_LENGTH),
				"max_full_text_length"		=> intval($this->MAX_FULL_TEXT_LENGTH),
				"max_credentials_length"	=> intval($this->MAX_CREDENTIALS_LENGTH),
			);
			return tpl()->parse('articles'."/edit_form", $replace);
		}
	}

	/**
	* Add new article
	*/
	function add () {
		if (empty(main()->USER_ID)) {
			return _error_need_login();
		}
		// Do save content
		if ($_POST) {
			// Do check captcha (if needed)
			if (module('articles')->USE_CAPTCHA) {
				main()->_execute('articles', "_captcha_check");
			}
			// Author name is required
			if (empty($_POST["author_name"])) {
				_re(t("Author Name is required"));
			}
			// Article category, title, summary and full_text are required
			if (empty($_POST["title"])) {
				_re(t("Title is required"));
			} elseif (!empty($this->MAX_TITLE_LENGTH) && strlen($_POST["title"]) > $this->MAX_TITLE_LENGTH) {
				_re("Title length (".strlen($_POST["title"]).") is more than allowed length (".intval($this->MAX_TITLE_LENGTH).")");
			}
			if (empty($_POST["summary"])) {
				_re(t("Summary is required"));
			} elseif (!empty($this->MAX_SUMMARY_LENGTH) && strlen($_POST["summary"]) > $this->MAX_SUMMARY_LENGTH) {
				_re("Summary length (".strlen($_POST["summary"]).") is more than allowed length (".intval($this->MAX_SUMMARY_LENGTH).")");
			}
			if (empty($_POST["full_text"])) {
				_re(t("Text is required"));
			} elseif (!empty($this->MAX_FULL_TEXT_LENGTH) && strlen($_POST["full_text"]) > $this->MAX_FULL_TEXT_LENGTH) {
				_re("Text length (".strlen($_POST["full_text"]).") is more than allowed length (".intval($this->MAX_FULL_TEXT_LENGTH).")");
			}
			if (!empty($this->_articles_cats) && (empty($_POST["cat_id"])) || !isset($this->_articles_cats[$_POST["cat_id"]])) {
				_re(t("Please select article category"));
			}
			// Check credentials length
			if (!empty($this->MAX_CREDENTIALS_LENGTH) && strlen($_POST["credentials"]) > $this->MAX_CREDENTIALS_LENGTH) {
				_re("Credentials length (".strlen($_POST["credentials"]).") is more than allowed length (".intval($this->MAX_CREDENTIALS_LENGTH).")");
			}
// TODO: add checking max number of articles
			// Get new status
			$NEW_STATUS = "new";
// TODO: add check if user is banned for posted articles
			// Check for errors
			if (!common()->_error_exists()) {
				// Do close BB Codes (if needed)
				if ($this->USE_BB_CODES) {
					$BB_CODES_OBJ = _class("bb_codes");
					if (is_object($BB_CODES_OBJ)) {
						$_POST["summary"]		= $BB_CODES_OBJ->_force_close_bb_codes($_POST["summary"]);
						$_POST["full_text"]		= $BB_CODES_OBJ->_force_close_bb_codes($_POST["full_text"]);
						$_POST["credentials"]	= $BB_CODES_OBJ->_force_close_bb_codes($_POST["credentials"]);
					}
				}
				db()->INSERT("articles_texts", array(
					"user_id"		=> intval(main()->USER_ID),
					"cat_id"		=> intval($_POST["cat_id"]),
					"author_name"	=> _es($_POST["author_name"]),
					"is_own_article"=> intval(!$_POST["not_own_article"]),
					"title"			=> _es($_POST["title"]),
					"summary"		=> _es($_POST["summary"]),
					"full_text"		=> _es($_POST["full_text"]),
					"credentials"	=> _es($_POST["credentials"]),
					"add_date"		=> time(),
					"status"		=> _es($NEW_STATUS),
					"short_url"		=> $this->_create_short_url($_POST["title"]),
				));
				// Update user stats
				_class("user_stats")->_update(array("user_id" => main()->USER_ID));
				// Return user back
				return js_redirect("./?object=".'articles'."&action=manage");
			}
		}
		// Fill POST data
		$DATA = $_POST;
		if (!$_POST) {
			$DATA["author_name"] = _display_name($this->_user_info);
		}
		// Display form
		if (!$_POST || common()->_error_exists()) {
			$replace = array(
				"form_action"	=> "./?object=".'articles'."&action=".$_GET["action"],
				"error_message"	=> _e(),
				"cats_box"		=> $this->_box("cat_id", $DATA["cat_id"]),
				"cat_name"		=> _prepare_html($this->_articles_cats[$DATA["cat_id"]]["name"]),
				"author_name"	=> _prepare_html($DATA["author_name"], 0),
				"is_own_article"=> intval((bool)$DATA["is_own_article"]),
				"title"			=> _prepare_html($DATA["title"]),
				"summary"		=> _prepare_html($DATA["summary"]),
				"full_text"		=> _prepare_html($DATA["full_text"]),
				"credentials"	=> _prepare_html($DATA["credentials"]),
				"add_date"		=> _format_date($DATA["add_date"]),
				"views"			=> intval($DATA["views"]),
				"status"		=> $this->_articles_statuses[$DATA["status"]],
				"for_edit"		=> 0,
				"use_captcha"	=> intval((bool)module('articles')->USE_CAPTCHA),
				"captcha_block"	=> main()->_execute('articles', "_captcha_block"),
				"allow_bb_code"	=> intval((bool) $this->USE_BB_CODES),
				"bb_codes_block_full_text"	=> $this->USE_BB_CODES ? _class("bb_codes")->_display_buttons(array("unique_id" => "bb_full_text")) : "",
				"bb_codes_block_summary"	=> $this->USE_BB_CODES ? _class("bb_codes")->_display_buttons(array("unique_id" => "bb_summary")) : "",
				"bb_codes_block_cred"		=> $this->USE_BB_CODES ? _class("bb_codes")->_display_buttons(array("unique_id" => "bb_cred")) : "",
				"max_title_length"			=> intval($this->MAX_TITLE_LENGTH),
				"max_summary_length"		=> intval($this->MAX_SUMMARY_LENGTH),
				"max_full_text_length"		=> intval($this->MAX_FULL_TEXT_LENGTH),
				"max_credentials_length"	=> intval($this->MAX_CREDENTIALS_LENGTH),
			);

			return tpl()->parse('articles'."/edit_form", $replace);
		}
	}

	/**
	* Delete selected article
	*/
	function delete () {
		if (empty(main()->USER_ID)) {
			return _error_need_login();
		}
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return _e(t("No id!"));
		}
		// Get article info
		$article_info = db()->query_fetch("SELECT * FROM ".db('articles_texts')." WHERE id=".intval($_GET["id"])." AND user_id=".intval(main()->USER_ID));
		if (empty($article_info)) {
			return _e(t("No such article!"));
		}
		
		$OBJ = module("unread");
		if(is_object($OBJ)){
			$ids = $OBJ->_set_read("articles", $_GET["id"]);
		}
		
		// Do delete article
		db()->query("DELETE FROM ".db('articles_texts')." WHERE id=".intval($_GET["id"])." LIMIT 1");
		// Update user stats
		_class("user_stats")->_update(array("user_id" => main()->USER_ID));
		// Return user back
		return js_redirect("./?object=".'articles'."&action=manage");
	}

	/**
	* Generate link to the given category id
	*
	* @access	private
	* @param	int
	* @return	string
	*/
	function _cat_link ($cat_id = 0) {
		return "./?object=".'articles'."&action=view_cat&id=".(!empty($this->_articles_cats[$cat_id]["url"]) ? _prepare_html($this->_articles_cats[$cat_id]["url"]) : $cat_id);
	}

	/**
	* Alias for "_cat_link"
	*/
	function _callback_cat_link ($cat_id = 0) {
		return $this->_cat_link($cat_id);
	}

	/**
	* Generate link to the given user id articles
	*/
	function _user_articles_link ($user_id = 0, $author_name = "") {
		if (!empty($user_id)) {
			return "./?object=".'articles'."&action=view_by_user&id=".$user_id;
		} else {
			return "./?object=".'articles'."&action=search&q=results&author_name=".rawurlencode($author_name);
		}
	}

	/**
	* Try to get cat id by url
	*/
	function _get_cat_id_by_url ($cat_name = "") {
		$cat_id = 0;
		foreach ((array)$this->_articles_cats as $cur_cat_id => $cur_cat_info) {
			if ($cur_cat_info["url"] == $cat_name) {
				$cat_id = $cur_cat_id;
				break;
			}
		}
		return $cat_id;
	}

	/**
	* Prepare title text fro short url
	*/
	function _create_short_url($title = "", $max_length = 64) {
// TODO: need to add checking for uniqueness
		$title = substr($title, 0, $max_length);
		$title = str_replace(array(";",",",".",":"," ","/"), "_", $title);
		$title = str_replace("__", "_", $title);
		$title = strtolower(preg_replace("/\W/i", "", $title));
		return $title;
	}

	/**
	* Process custom box
	*/
	function _box ($name = "", $selected = "") {
		if (empty($name) || empty($this->_boxes[$name])) return false;
		else return eval("return common()->".$this->_boxes[$name].";");
	}

	/**
	* Try to load sub_module
	*/
	function _load_sub_module ($module_name = "") {
		return _class($module_name, 'modules/articles/');
	}

	/**
	* Generate filter SQL query
	*
	* @access	public
	* @return	string
	*/
	function _create_filter_sql () {
		$OBJ = $this->_load_sub_module("articles_filter");
		return is_object($OBJ) ? $OBJ->_create_filter_sql() : "";
	}

	/**
	* Session - based filter form
	*
	* @access	public
	* @return	string
	*/
	function _show_filter () {
		$OBJ = $this->_load_sub_module("articles_filter");
		return is_object($OBJ) ? $OBJ->_show_filter() : "";
	}

	/**
	* Filter save method
	*
	* @access	public
	* @return	string
	*/
	function save_filter ($silent = false) {
		$OBJ = $this->_load_sub_module("articles_filter");
		return is_object($OBJ) ? $OBJ->_save_filter($silent) : "";
	}

	/**
	* Clear filter
	*
	* @access	public
	* @return	string
	*/
	function clear_filter ($silent = false) {
		$OBJ = $this->_load_sub_module("articles_filter");
		return is_object($OBJ) ? $OBJ->_clear_filter($silent) : "";
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
			"url"	=> "./?object=articles",
		));

		// Get articles categories from db
		$sql = "SELECT id FROM ".db('category_items')." WHERE cat_id IN (SELECT id FROM ".db('categories')." WHERE name='articles_cats')";
		$Q = db()->query($sql);
		while ($A = db()->fetch_assoc($Q)) {
			$OBJ->_store_item(array(
				"url"	=> "./?object=articles&action=view_cat&id=".$A["id"],
			));
		}

		// Single articles
		$sql = "SELECT id FROM ".db('articles_texts')."";
		$Q = db()->query($sql);
		while ($A = db()->fetch_assoc($Q)) {
			$OBJ->_store_item(array(
				"url"	=> "./?object=articles&action=view&id=".$A["id"],
			));
		}

		return true;
	}
	
	function _for_home_page ($NUM_NEWEST_ARTICLE_POST = 4) {
		$OBJ = $this->_load_sub_module("articles_integration");
		return is_object($OBJ) ? $OBJ->_for_home_page($NUM_NEWEST_ARTICLE_POST, $NEWEST_ARTICLE_POST_LEN) : "";
	}
	
	function _for_user_profile ($id, $MAX_SHOW_ARTICLES = 4) {
		$OBJ = $this->_load_sub_module("articles_integration");
		return is_object($OBJ) ? $OBJ->_for_user_profile($id, $MAX_SHOW_ARTICLES) : "";
	}
	
	function _rss_general(){
		$OBJ = $this->_load_sub_module("articles_integration");
		return is_object($OBJ) ? $OBJ->_rss_general($this->NUM_RSS) : "";
	}

	/**
	* Quick menu auto create
	*/
	function _quick_menu () {
		$menu = array(
			array(
				"name"	=> "Manage",
				"url"	=> "./?object=".$_GET["object"]."&action=manage",
			),
			array(
				"name"	=> "Add New Article",
				"url"	=> "./?object=".$_GET["object"]."&action=add",
			),
			array(
				"name"	=> "Comments to my articles",
				"url"	=> "./?object=".$_GET["object"]."&action=search_comments",
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
		$pheader = t("Articles");
		if ($_GET["action"] == "view") {
			$pheader = "";
		}
		// Default subheader get from action name
		$subheader = _ucwords(str_replace("_", " ", $_GET["action"]));

		if ($_GET["action"] == "view_cat" && !is_numeric($_GET["id"])) {
			$subheader = t("Category").": ".$_GET["id"];
		} elseif ($_GET["action"] == "view_cat" && is_numeric($_GET["id"])) {
			$subheader = t("Category").": ".$this->_articles_cats[$_GET["id"]]["name"];
		}

		// Array of replacements
		$cases = array (
			//$_GET["action"] => {string to replace}
			"show"			=> "",
			"view"			=> "",
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
	*
	*/
	function _unread () {
	
		if(empty($this->_user_info["last_view"])){
			return;
		}
		
		$Q = db()->query("SELECT id FROM ".db('articles_texts')." WHERE status = 'active' AND user_id != ".intval(main()->USER_ID)." AND add_date > ".$this->_user_info["last_view"]);
		while ($A = db()->fetch_assoc($Q)) {
			$ids[$A["id"]] = $A["id"];
		}
		
		$link = process_url("./?object=articles&action=view_unread");
		
		$unread = array(
			"count"	=> count($ids),
			"ids"	=> $ids,
			"link"	=> $link,
		);
	
		return $unread;
	}
	
	/**
	*
	*/
	function view_unread () {
		if(empty(main()->USER_ID)){
			return;
		}
	
		$OBJ = module("unread");
		$ids = $OBJ->_get_unread("articles");
		
		if(!empty($ids)){
			$sql		= "SELECT id,title FROM ".db('articles_texts')." WHERE id IN(".implode(",", (array)$ids).")";
			$order_sql	= " ORDER BY add_date DESC";
			list($add_sql, $pages, $total) = common()->divide_pages($sql);
			$Q = db()->query($sql.$order_sql.$add_sql);
			while ($A = db()->fetch_assoc($Q)) {
				$articles_info[$A["id"]] = $A;
			}
		}
		
		$replace = array(
			"items"		=> $articles_info,
			"pages"		=> $pages,
		);
		
		return tpl()->parse($_GET["object"]."/unread", $replace);
	}

}
