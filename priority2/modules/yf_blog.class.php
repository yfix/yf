<?php

/**
* Blogs
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_blog extends yf_module {

	/** @var int Max post title length */
	public $MAX_POST_TITLE_LENGTH		= 95;
	/** @var int Max post text length */
	public $MAX_POST_TEXT_LENGTH		= 10000;
	/** @var int Max mode text length (playing, reading etc) */
	public $MAX_MODE_TEXT_LENGTH		= 100;
	/** @var int Number of posts per page */
	public $POSTS_PER_PAGE				= 10;
	/** @var bool Use bb codes */
	public $USE_BB_CODES				= true;
	/** @var bool Allow HTML in posts */
	public $ALLOW_HTML_IN_POSTS		= false;
	/** @var string Folder where attached images are storing */
	public $ATTACH_IMAGES_DIR			= "blog_images/";
	/** @var int @conf_skip Default attributes for folders */
	public $DEF_DIR_MODE				= 0777;
	/** @var int Attach image max width (in pixels) */
	public $ATTACH_LIMIT_X				= 350;	// px
	/** @var int Attach image max height (in pixels) */
	public $ATTACH_LIMIT_Y				= 1000;	// px
	/** @var int Attach image max file size (in bytes) */
	public $MAX_IMAGE_SIZE				= 100000;// bytes
	/** @var int Number of blog messages for profile */
	public $NUM_FOR_PROFILE			= 5;
	/** @var int Number of records to show on one page for "view all" */
	public $VIEW_ALL_ON_PAGE			= 30;
	/** @var int Number of records to show on one page for "show in cat" */
	public $SHOW_IN_CAT_ON_PAGE		= 30;
	/** @var int Number of most active authors for the stats page */
	public $STATS_NUM_MOST_ACTIVE		= 25;
	/** @var int Number of the latest posts for the stats page */
	public $STATS_NUM_LATEST_POSTS		= 25;
	/** @var int Number of most commented posts for the stats page */
	public $STATS_NUM_MOST_COMMENTED	= 25;
	/** @var int Number of most read posts for the stats page */
	public $STATS_NUM_MOST_READ		= 25;
	/** @var int Number of friends posts for the stats page */
	public $STATS_NUM_FRIENDS_POSTS	= 25;
	/** @var int Number of latest posts to display */
	public $NUM_LATEST_POSTS			= 25;
	/** @var int Latest post cut length */
	public $LATEST_POSTS_CUT_LENGTH	= 50;
	/** @var int Number of latest friends posts to display */
	public $FRIENDS_POSTS_PER_PAGE		= 25;
	/** @var int Max allowed blog title length */
	public $MAX_BLOG_TITLE_LENGTH		= 100;
	/** @var int Max number of allowed custom categories */
	public $MAX_CUSTOM_CATS_NUM		= 20;
	/** @var int Max number of allowed friendly sites (links) */
	public $MAX_BLOG_LINKS_NUM			= 10;
	/** @var array @conf_skip Current blog settings array */
	public $BLOG_SETTINGS				= null;
	/** @var bool Use captcha */
	public $USE_CAPTCHA				= true;
	/** @var int Number of symbols for text preview */
	public $POST_TEXT_PREVIEW_LENGTH	= 500;
	/** @var bool Use preview on "show_posts" or not */
	public $PREVIEW_CUT_ON_SHOW_POSTS	= false;
	/** @var bool All blogs search filter on/off */
	public $USE_FILTER					= true;
	/** @var bool Force stripslashes on "_format_text" method */
	public $FORCE_STRIPSLASHES			= true;
	/** @var int Minimal text length to search in posts (in symbols) */
	public $MIN_SEARCH_TEXT_LENGTH		= 2;
	/** @var bool Hide empty categories on teh stats page */
	public $STATS_HIDE_EMPTY_CATS		= true;
	/** @var bool Allow RSS feeds or not */
	public $ALLOW_RSS_EXPORT			= true;
	/** @var bool Allow ping Google on post change */
	public $ALLOW_PING_GOOGLE			= false;
	/** @var bool Allow custom cats link texts */
	public $CUSTOM_CATS_LINKS_TEXTS	= true;
	/** @var bool Use js_calendar */
	public $USE_JS_CALENDAR			= false;
	/** @var bool */
	public $ARCHIVE_NAV_FULL			= false;
	/** @var bool Enable or not Geo location limits for content */
	public $ALLOW_GEO_FILTERING		= false;
	/** @var bool Search related posts for single post or not */
	public $SHOW_RELATED_POSTS			= false;
	/** @var bool If this turned on - then system will hide total ids for user, 
	* and wiil try to use small id numbers dedicated only for this user
	*/
	public $HIDE_TOTAL_ID				= false;
	/** @var array @conf_skip Params for the comments */
	public $_comments_params = array(
		"object_name"	=> "blog",
		"return_action" => "show_single_post",
	);
	/** @var array @conf_skip Params for the poll */
	public $_poll_params = array(
		"return_action" => "show_single_post",
	);
	/** @var bool Allow tagging */
	public $ALLOW_TAGGING				= true;
	/** @var int */
	public $NUM_RSS 	= 10;
	/** @var bool Hide general categories from user */
	public $HIDE_GENERAL_CATS			= false;
	/** @var enum("xml-rpc", "rest") */
	public $PING_METHOD 				= "xml-rpc";
	/** var bool allow delete comments */
	public $ALLOW_DELETE_COMMENTS		= true;
	/** var bool allow Search comments only posted by members */
	public $SEARCH_ONLY_MEMBER			= true;
	/** @var float Coeficient for number of records to get from db (to allow skipping some records) */
	public $FROM_DB_MULTIPLY			= 1.5;

	/**
	* YF module constructor
	*
	* @access	private
	* @return	void
	*/
	function _init () {
		// Blog class name (to allow changing only in one place)
		define("BLOG_CLASS_NAME", "blog");
		// Blog modules folder
		define("BLOG_MODULES_DIR", USER_MODULES_DIR. BLOG_CLASS_NAME."/");
		// Set dir name
		if (defined("SITE_BLOG_IMAGES_DIR")) {
			$this->ATTACH_IMAGES_DIR = SITE_BLOG_IMAGES_DIR;
		}
		// Array of select boxes to process
		$this->_boxes = array(
			"cat_id"			=> 'select_box("cat_id",		$this->_blog_cats2,		$selected, false, 2, "", false)',
			"mode_type"			=> 'select_box("mode_type",		$this->_mode_types,		$selected, false, 2, "", false)',
			"mood"				=> 'select_box("mood",			$this->_moods,			$selected, false, 2, "", false)',
			"privacy"			=> 'select_box("privacy",		$this->_privacy_types,	$selected, false, 2, "", false)',
			"allow_comments"	=> 'select_box("allow_comments",$this->_comments_types,	$selected, false, 2, "", false)',
			"privacy2"			=> 'select_box("privacy",		$this->_privacy_types2,	$selected, false, 2, "", false)',
			"allow_comments2"	=> 'select_box("allow_comments",$this->_comments_types2,$selected, false, 2, "", false)',
		);
		// Get user account type
		$this->_account_types	= main()->get_data("account_types");
		$this->_mode_types		= main()->get_data("mode_types");
		$this->_privacy_types	= main()->get_data("privacy_types");
		$this->_comments_types	= main()->get_data("allow_comments_types");
		// Prepare privacy and allow_comments for edit posts
		$this->_privacy_types2[0] = "-- USE GLOBAL SETTINGS --";
		foreach ((array)$this->_privacy_types as $k => $v) {
			$this->_privacy_types2[$k] = $v;
		}
		$this->_comments_types2[0] = "-- USE GLOBAL SETTINGS --";
		foreach ((array)$this->_comments_types as $k => $v) {
			$this->_comments_types2[$k] = $v;
		}
		// Array of all allowed letters
		$letters = range("a","z");
		foreach ((array)$letters as $v) {
			$this->_letters[$v] = $v;
		}
		$this->_numbers = range(0,9);
		// Get blogs categories
		$this->CATS_OBJ		= main()->init_class("cats", "classes/");
		$this->_blog_cats	= $this->CATS_OBJ->_get_items_names(BLOG_CLASS_NAME."_cats");
		$this->_blog_cats2	= $this->CATS_OBJ->_prepare_for_box(BLOG_CLASS_NAME."_cats");
		// Get moods
		$this->_moods = main()->get_data("locale:moods");
		// Check total id mode
		$this->HIDE_TOTAL_ID = main()->HIDE_TOTAL_ID;
		if ($this->HIDE_TOTAL_ID && (
			MAIN_TYPE_ADMIN || 
			(empty($GLOBALS['HOSTING_ID']) && empty(main()->USER_ID))
		)) {
			$this->HIDE_TOTAL_ID = false;
		}
		// Turn off CAPTCHA for admin section
		if (MAIN_TYPE_ADMIN) {
			$this->USE_CAPTCHA = false;
		}
	}

	/**
	* Default method
	*/
	function show () {
		// Short call for the user's blog
		if (!empty($_GET["id"])) {
			$_GET["action"] = "show_posts";
			return $this->show_posts();
		}
		return $this->_show_stats();
	}

	/**
	* Start new blog method (you can tune it as you want)
	*/
	function start () {
		return $this->_call_sub_method("blog_settings", "_start_blog");
	}

	/**
	* Blog statistics
	*/
	function _show_stats () {
		return $this->_call_sub_method("blog_stats", "_show_stats");
	}

	/**
	* Alias for "show_all_blogs"
	*/
	function search () {
		return $this->show_all_blogs();
	}

	/**
	* Show all blog authors list with links to their blogs
	*/
	function show_all_blogs () {
		// Check CPU Load
		if (conf('HIGH_CPU_LOAD') == 1) {
			return common()->server_is_busy();
		}
		return $this->_call_sub_method("blog_search", "_show_all_blogs");
	}

	/**
	* Show most commented blog posts
	*/
	function show_most_commented () {
		// Check CPU Load
		if (conf('HIGH_CPU_LOAD') == 1) {
			return common()->server_is_busy();
		}
		return $this->_call_sub_method("blog_stats", "_show_most_commented");
	}

	/**
	* Show most read blog posts
	*/
	function show_most_read () {
		// Check CPU Load
		if (conf('HIGH_CPU_LOAD') == 1) {
			return common()->server_is_busy();
		}
		return $this->_call_sub_method("blog_stats", "_show_most_read");
	}

	/**
	* Show latest blog posts
	*/
	function show_latest_posts () {
		// Check CPU Load
		if (conf('HIGH_CPU_LOAD') == 1) {
			return common()->server_is_busy();
		}
		return $this->_call_sub_method("blog_stats", "_show_latest_posts");
	}

	/**
	* Show blog posts in given category
	*/
	function show_in_cat () {
		return $this->_call_sub_method("blog_search", "_show_in_cat");
	}

	/**
	* Show friends posts
	*/
	function friends_posts () {
		return $this->_call_sub_method("blog_search", "_show_friends_posts");
	}

	/**
	* Show posts for the given blogger or for the current user ID is empty
	*/
	function show_posts () {
		$_GET["id"] = intval($_GET["id"]);
		$user_id = !empty($_GET["id"]) ? $_GET["id"] : main()->USER_ID;
		if (isset($_GET["page"])) {
			$_GET["page"] = intval($_GET["page"]);
		}
		if ($this->HIDE_TOTAL_ID && $GLOBALS['HOSTING_ID']) {
			$user_id = $GLOBALS['HOSTING_ID'];
		}
		// Check if user already have started blog
		$num_user_posts = db()->query_num_rows(
			"SELECT id FROM ".db('blog_posts')." WHERE user_id=".intval($user_id)
		);
		if (!empty($user_id) && empty($num_user_posts)) {
			$user_info = user($user_id);
			$GLOBALS['user_info'] = $user_info;
			$replace = array(
				"is_logged_in"	=> intval((bool) main()->USER_ID),
				"is_own_blog"	=> intval(($_GET["id"] && main()->USER_ID == $_GET["id"]) || (!$_GET["id"] && main()->USER_ID)),
				"start_link"	=> "./?object=".BLOG_CLASS_NAME."&action=start"._add_get(array("page")),
				"user_id"		=> intval(main()->USER_ID),
				"user_avatar"	=> _show_avatar($user_info["id"], _display_name($user_info), 1, 0),
			);
			$body = tpl()->parse(BLOG_CLASS_NAME."/no_blog_yet", $replace);
		} else {
			$body = $this->_view_user_posts(intval($user_id), intval($num_user_posts));
		}
		return $body;
	}

	/**
	* Show posts for the other months than current
	*/
	function show_posts_archive () {
		if (empty($_GET["id"])) {
			return _e(t("No date specified!"));
		}
		if ($this->HIDE_TOTAL_ID) {
			list($this->CUR_YEAR, $this->CUR_MONTH, $this->CUR_DAY) = explode("-", $_GET["id"]);
			$this->SOURCE_ARCHIVE_DATE = $_GET["id"];
			$_GET["id"] = $GLOBALS['HOSTING_ID'] ? $GLOBALS['HOSTING_ID'] : main()->USER_ID;
		} else {
			list($_GET["id"], $this->CUR_YEAR, $this->CUR_MONTH, $this->CUR_DAY) = explode("-", $_GET["id"]);
		}
		// Check given ID
		$this->CUR_YEAR		= intval(sprintf("%04d", $this->CUR_YEAR));
		$this->CUR_MONTH	= intval(sprintf("%02d", $this->CUR_MONTH));
		$this->CUR_DAY		= intval(sprintf("%02d", $this->CUR_DAY));
		if (empty($_GET["id"]) || empty($this->CUR_YEAR)) {
			return _e(t("Wrong ID!"));
		}
		$this->IS_ARCHIVE	= true;
		// Show posts for the specified date
		return $this->show_posts();
	}

	/**
	* View blog posts for the given user
	*/
	function _view_user_posts ($user_id = 0, $num_user_posts = 0, $params = array()) {
		$user_id = intval($user_id);
		// Try to get given user info
		if ($user_id) {
			$user_info = user($user_id);
		}
		if (empty($GLOBALS['user_info'])) {
			$GLOBALS['user_info'] = $user_info;
		}
		if (empty($user_info["id"])) {
			return _e(t("Wrong user ID!"));
		}
		// Get current blog settings
		$this->BLOG_SETTINGS = $this->_get_user_blog_settings($user_info["id"]);
		// Check privacy permissions
		if (!$this->_privacy_check($this->BLOG_SETTINGS["privacy"], 0, $user_id)) {
			return _e(t("You are not allowed to view this blog"));
		}
		// Get all user posts short info
		$this->_get_posts_days();
		// Try to get custom category name
		$this->_user_custom_cats	= $this->_custom_cats_into_array($this->BLOG_SETTINGS["custom_cats"]);
		$posts_ids_to_show	= array();
		// Archive view
		if ($this->IS_ARCHIVE) {
			// Filter archive posts
			foreach ((array)$this->_posts_ids_by_dates as $_date => $_posts_by_date) {
				// Skip posts from other years
				if (substr($_date, 0, 4) != $this->CUR_YEAR) {
					continue;
				}
				// Skip posts from other months
				if (!empty($this->CUR_MONTH) && substr($_date, 5, 2) != $this->CUR_MONTH) {
					continue;
				}
				foreach ((array)$_posts_by_date as $_post_id) {
					$posts_ids_to_show[$_post_id] = $_post_id;
				}
			}
			$total_posts		= count($posts_ids_to_show);
			$path				= "./?object=".BLOG_CLASS_NAME."&action=".$_GET["action"].
				"&id=".($this->HIDE_TOTAL_ID ? "" : $_GET["id"]."-").$this->CUR_YEAR
				.($this->CUR_MONTH ? "-".$this->CUR_MONTH : "")
				.($this->CUR_DAY ? "-".$this->CUR_DAY : "");
			// Get a slice from the whole array
			if (count($posts_ids_to_show) > $this->POSTS_PER_PAGE) {
				$posts_ids_to_show = array_slice($posts_ids_to_show, (empty($_GET["page"]) ? 0 : intval($_GET["page"]) - 1) * $this->POSTS_PER_PAGE, $this->POSTS_PER_PAGE);
			}
		// Custom category view
		} elseif (!empty($params["custom_cat_id"])) {
			$posts_ids_to_show	= $this->_posts_ids_by_custom_cats[$params["custom_cat_id"]];
			$total_posts		= count($this->_all_posts_ids);
			$path				= $this->_user_custom_cats[$params["custom_cat_id"] - 1]["link"];
			$custom_cat_name	= $this->_user_custom_cats[$params["custom_cat_id"] - 1]["name"];
			$this->CUSTOM_CAT_NAME = $custom_cat_name;
		// Normal view
		} else {
			$posts_ids_to_show	= array_slice($this->_all_posts_ids, $_GET["page"] ? ($_GET["page"] - 1) * $this->POSTS_PER_PAGE : 0, $this->POSTS_PER_PAGE);
			$total_posts		= count($this->_all_posts_ids);
			$path				= "./?object=".BLOG_CLASS_NAME."&action=".$_GET["action"]. ($this->HIDE_TOTAL_ID ? "" : "&id=".$user_id);
		}
		// Get selected posts details
		if (is_array($posts_ids_to_show) && !empty($posts_ids_to_show)) {
		
			$Q = db()->query("SELECT * FROM ".db('blog_posts')." WHERE id IN(".implode(",", $posts_ids_to_show).") ORDER BY add_date DESC");
			while ($post_info = db()->fetch_assoc($Q)) {
				if ($this->PREVIEW_CUT_ON_SHOW_POSTS) {
					$post_info["text"] = substr($post_info["text"], 0, $this->POST_TEXT_PREVIEW_LENGTH);
				}
				$posts_array[$post_info["id"]] = $post_info;
			}
			// Get pages
			list(, $pages, $num_items) = common()->divide_pages("", $path, null, $this->POSTS_PER_PAGE, $total_posts);
		}
		// Get number of user comments
		if (is_array($posts_array)) {
			$num_comments = $this->_get_num_comments(array(
				"objects_ids" => implode(",", array_keys($posts_array)),
			));
		}
		// Process user reputation
		$reput_text = "";
		$REPUT_OBJ = main()->init_class("reputation");
		if (is_object($REPUT_OBJ)) {
			$reput_info	= $REPUT_OBJ->_get_user_reput_info($user_id);
			$reput_text	= $REPUT_OBJ->_show_for_user($user_id, $reput_info);

			if (!empty(main()->USER_ID) && !empty($user_info["id"]) && $user_info["id"] != main()->USER_ID) {
				$this->SHOW_REPUT_LINK = true;
			}
		}
		// Process tags
		$this->_tags = $this->_show_tags(array_keys((array)$posts_array));
		// Process user posts
		$counter = count($posts_array);
		foreach ((array)$posts_array as $post_info) {
			$posts_ids[$post_info["id"]] = $post_info["id"];
			$posts .= $this->_show_post_item($post_info, intval($num_comments[$post_info["id"]]), $counter--);
		}
		$archive_date = "";
		if ($this->IS_ARCHIVE) {
			$archive_date = date((!empty($this->CUR_MONTH) ? "F " : "")."Y", strtotime($this->CUR_YEAR."-".($this->CUR_MONTH ? $this->CUR_MONTH : 01)."-01"));
		}
		// Log visit if not owner
		if (main()->USER_ID !== $post_info["user_id"]) {
			common()->_log_user_action("visit", $post_info["user_id"], $_GET["object"]);
		}
		// Process main template
		$replace = array(
			"user_name"				=> _prepare_html(_display_name($user_info)),
			"user_profile_link"		=> _profile_link($user_id),
			"change_settings_link"	=> $user_id == main()->USER_ID ? "./?object=".BLOG_CLASS_NAME."&action=settings"._add_get(array("page")) : "",
			"add_post_link"			=> $user_id == main()->USER_ID ? "./?object=".BLOG_CLASS_NAME."&action=add_post"._add_get(array("page")) : "",
			"users_comments_link"	=> $user_id == main()->USER_ID ? "./?object=".BLOG_CLASS_NAME."&action=search_comments"._add_get(array("page")) : "",
			"posts"					=> $posts,
			"page_link"				=> process_url("./?object=".BLOG_CLASS_NAME."&action=".$_GET["action"]. ($this->HIDE_TOTAL_ID ? "" : "&id=".$_GET["id"])),
			"latest_posts"			=> $this->_latest_posts,
			"archive_date"			=> $archive_date,
			"pages"					=> trim($pages),
			"right_block"			=> $this->_show_right_block(),
			"blog_title"			=> _prepare_html($this->BLOG_SETTINGS["blog_title"]),
			"user_blog_link"		=> "./?object=".BLOG_CLASS_NAME."&action=show_posts". ($this->HIDE_TOTAL_ID ? "" : "&id=".$user_id). _add_get(array("page")),
			"user_avatar"			=> _show_avatar($user_info["id"], $user_info, 1, 1),
			"reput_text"			=> $reput_text,
			"custom_cat_name"		=> $custom_cat_name,
			"rss_posts_button"		=> $this->_show_rss_link("./?object=".BLOG_CLASS_NAME."&action=rss_for_single_blog".($this->HIDE_TOTAL_ID ? "" : "&id=".$user_id), "RSS feed for blog: ".(!empty($this->BLOG_SETTINGS["blog_title"]) ? $this->BLOG_SETTINGS["blog_title"] : _display_name($user_info)."'s blog")),
		);
		$body = tpl()->parse(BLOG_CLASS_NAME."/view_blog_main", $replace);
		// Update number of reads
		if (is_array($posts_ids) && empty($GLOBALS['blog_no_count_views'])) {
			db()->query("UPDATE ".db('blog_posts')." SET num_reads = num_reads + 1 WHERE id IN(".implode(",", $posts_ids).")");
		}
		return $body;
	}

	/**
	* Get posts archive dates
	*/
	function _get_posts_days () {
		$user_info = &$GLOBALS['user_info'];
		if (empty($user_info["id"])) {
			return false;
		}
		$this->_all_posts_ids		= array();
		$this->_posts_ids_by_dates	= array();
		$this->_posts_by_days		= array();
		$this->_latest_posts		= array();
		$this->_posts_ids_by_custom_cats = array();
		// Get current month to show posts
		if (!$this->IS_ARCHIVE) {
			if (empty($this->CUR_YEAR)) {
				$this->CUR_YEAR	= date("Y");
			}
			if (empty($this->CUR_MONTH)) {
				$this->CUR_MONTH= date("m");
			}
			if (empty($this->CUR_DAY)) {
				$this->CUR_DAY	= date("d");
			}
		}
		// Get all user posts short info
		$Q = db()->query("SELECT id,id2,add_date,custom_cat_id,title FROM ".db('blog_posts')." WHERE user_id=".intval($user_info["id"])." AND active=1 ORDER BY add_date DESC");
		while ($post_info = db()->fetch_assoc($Q)) {
			$this->_all_posts_ids[$post_info["id"]] = $post_info["id"];
			$this->_posts_ids_by_dates[date("Y-m", $post_info["add_date"])][$post_info["id"]] = $post_info["id"];
			$this->_posts_by_days[date("Y-m-d", $post_info["add_date"])]++;
			// Fill latest posts array
			if ($i++ < $this->NUM_LATEST_POSTS) {
				$post_title = $this->_format_text($post_info["title"]);
				$this->_latest_posts[$post_info["id"]] = array(
					"post_link"	=> "./?object=".BLOG_CLASS_NAME."&action=show_single_post&id=". ($this->HIDE_TOTAL_ID ? $post_info["id2"] : $post_info["id"]). _add_get(array("page")),
					"post_title"=> $this->LATEST_POSTS_CUT_LENGTH && strlen($post_title) > $this->LATEST_POSTS_CUT_LENGTH ? _substr($post_title, 0, $this->LATEST_POSTS_CUT_LENGTH)."..." : $post_title,
				);
			}
			// Fill custom cats
			if (!empty($post_info["custom_cat_id"])) {
				$this->_posts_ids_by_custom_cats[$post_info["custom_cat_id"]][$post_info["id"]] = $post_info["id"];
			}
		}
		// Process posts archive links
		if (!is_array($this->_posts_ids_by_dates)) {
			return "";
		}
		krsort($this->_posts_ids_by_dates);
	}

	/**
	* Show single post with comments
	*/
	function _show_right_block () {
		return $this->_call_sub_method("blog_right_block", "_show");
	}

	/**
	* Show single post with comments
	*/
	function show_single_post () {
		$_GET["id"] = intval($_GET["id"]);
		// Try to get given user info
		$sql = "SELECT * FROM ".db('blog_posts')." WHERE ";
		if ($this->HIDE_TOTAL_ID) {
			$sql .= " id2=".intval($_GET["id"])." AND user_id=".intval($GLOBALS['HOSTING_ID'] ? $GLOBALS['HOSTING_ID'] : main()->USER_ID);
		} else {
			$sql .= " id=".intval($_GET["id"]);
		}
		$this->_post_info = db()->query_fetch($sql);
		if (empty($this->_post_info["id"])) {
			return _e(t("No such post!"));
		}
		
		// if in community
		if(!empty($this->_post_info["poster_id"])){
			$this->_post_info["user_id"] = $this->_post_info["poster_id"];
		}
		
		// Get blog author info
		$user_info = user($this->_post_info["user_id"]);
		// Check if user exists
		if (empty($user_info)) {
			return _e(t("No such user"));
		}
		if (empty($GLOBALS['user_info'])) {
			$GLOBALS['user_info'] = $user_info;
		}
		// Get current blog settings
		$this->BLOG_SETTINGS = $this->_get_user_blog_settings($user_info["id"]);
		// Check privacy permissions
		if (!$this->_privacy_check($this->BLOG_SETTINGS["privacy"], $this->_post_info["privacy"], $user_info["id"])) {
			return _e(t("You are not allowed to view this post"));
		}
		
		// Check friends group permissions
		if(!empty($this->_post_info["mask"])){
			if(!empty($this->_post_info["poster_id"])){
				$user_id = $this->_post_info["poster_id"];
			}else{
				$user_id = $this->_post_info["user_id"];
			}
			
			if($user_id != main()->USER_ID){
				$user_mask = db()->query_fetch("SELECT mask FROM ".db('friends_users')." WHERE user_id = ".$user_id." AND friend_id = ".main()->USER_ID);
				$user_mask = $user_mask["mask"];
				
				$FRIENDS_OBJ = &main()->init_class("friends");
				
				$is_allowed = $FRIENDS_OBJ->check_mask_permissions($user_mask, $this->_post_info["mask"]);
				
				if(!$is_allowed){
					return _e(t("You are not allowed to view this post"));
				}
			}
		}
		
		// Get all user posts short info
		$this->_get_posts_days();
		// Try to get custom category name
		$this->_user_custom_cats	= $this->_custom_cats_into_array($this->BLOG_SETTINGS["custom_cats"]);
		// Process user reputation
		$reput_text = "";
		$REPUT_OBJ = main()->init_class("reputation");
		if (is_object($REPUT_OBJ)) {
			$all_users_ids		= $users_ids;
			$all_users_ids[$user_info["id"]] = $user_info["id"];

			$users_reput_info	= $REPUT_OBJ->_get_reput_info_for_user_ids($all_users_ids);
			$reput_text			= $REPUT_OBJ->_show_for_user($user_info["id"], $users_reput_info[$user_info["id"]], false, array("blog_posts", $this->_post_info["id"]));

			if (!empty(main()->USER_ID) && !empty($this->_post_info["user_id"]) && $this->_post_info["user_id"] != main()->USER_ID) {
				$SHOW_REPUT_LINK = true;
			}
		}
		// Update number of posts reads
		db()->query("UPDATE ".db('blog_posts')." SET num_reads = num_reads + 1 WHERE id=".intval($this->_post_info["id"]));
		// Comments block check
		$comments_allowed = $this->_comment_allowed_check ($this->BLOG_SETTINGS["allow_comments"], $this->_post_info["allow_comments"], $this->_post_info["user_id"]);

		// Process tags
		$this->_tags = $this->_show_tags($_GET["id"]);

		// Log visit and reading if not owner
		if (main()->USER_ID !== $this->_post_info["user_id"]) {
			common()->_log_user_action("visit", $this->_post_info["user_id"], $_GET["object"]);
			common()->_log_user_action("review", $this->_post_info["user_id"], $_GET["object"], intval($_GET["id"]));
		}
		// Process main template
		$replace = array(
			"is_logged_in"			=> intval((bool) main()->USER_ID),
			"user_name"				=> _prepare_html(_display_name($user_info)),
			"user_avatar"			=> _show_avatar($user_info["id"], $user_info, 1, 1),
			"user_profile_link"		=> _profile_link($this->_post_info["user_id"]),
			"change_settings_link"	=> $this->_post_info["user_id"] == main()->USER_ID ? "./?object=".BLOG_CLASS_NAME."&action=settings"._add_get(array("page")) : "",
			"add_post_link"			=> $this->_post_info["user_id"] == main()->USER_ID ? "./?object=".BLOG_CLASS_NAME."&action=add_post"._add_get(array("page")) : "",
			"post_info"				=> $this->_show_post_item($this->_post_info, count($comments_array), 0, BLOG_CLASS_NAME."/single_post_item"),
			"user_blog_link"		=> "./?object=".BLOG_CLASS_NAME."&action=show_posts". ($this->HIDE_TOTAL_ID ? "" : "&id=".$this->_post_info["user_id"]). _add_get(array("page")),
			"back_url"				=> "./?object=".BLOG_CLASS_NAME."&action=show_posts". ($this->HIDE_TOTAL_ID ? "" : "&id=".$this->_post_info["user_id"]). _add_get(array("page")),
			"page_link"				=> process_url("./?object=".BLOG_CLASS_NAME."&action=".$_GET["action"]. ($this->HIDE_TOTAL_ID ? "" : "&id=".$_GET["id"])),
			"user_id"				=> intval(main()->USER_ID),
			"right_block"			=> $this->_show_right_block(),
			"blog_title"			=> _prepare_html($this->BLOG_SETTINGS["blog_title"]),
			"reput_text"			=> $reput_text,
			"comments"				=> $comments_allowed ? $this->_view_comments(array("object_id" => $this->_post_info["id"])) : "",
			"vote_popup_link"		=> $SHOW_REPUT_LINK ? process_url("./?object=reputation&action=vote_popup&id=".$this->_post_info["user_id"]."&page=".$_GET["object"]."--".$this->_post_info["id"]) : "",
			"rss_posts_button"		=> $this->_show_rss_link("./?object=".BLOG_CLASS_NAME."&action=rss_for_single_blog". ($this->HIDE_TOTAL_ID ? "" : "&id=".$this->_post_info["user_id"]), "RSS feed for blog: ".(!empty($this->BLOG_SETTINGS["blog_title"]) ? $this->BLOG_SETTINGS["blog_title"] : _display_name($user_info)."'s blog")),
			"poll_block"			=> $this->_poll($this->_post_info["id"]),
			"related_posts"			=> $this->_show_related_posts($this->_post_info),
		);
		return tpl()->parse(BLOG_CLASS_NAME."/single_post_main", $replace);
	}

	/**
	* Display related posts
	*/
	function _show_related_posts ($post_info = array()) {
		if (!$this->SHOW_RELATED_POSTS) {
			return false;
		}

// TODO: check privacy
// TODO: ability to select only in same category
// TODO: RSS of related posts

		$data = common()->related_content(array(
			"action"		=> "fetch", // Action: sql, fetch, stpl
			"source_array"	=> $post_info, // array to analyze title and text from
			"table_name"	=> db('blog_posts'), // database table name to query
			"fields_return"	=> "id, user_id, add_date, title, text, privacy", // array or string of fields to return in resultset
			"field_id"		=> "id",
			"field_date"	=> "add_date",
			"field_title"	=> "title",
			"field_text"	=> "text",
			"where"			=> "user_id=".intval($post_info["user_id"]), // custom WHERE condition will be added to query
		));
		if (empty($data) || !is_array($data)) {
			return false;
		}
		$result = array();
		foreach ((array)$data as $A) {
			$result[$A["id"]] = array(
				"post_id"	=> intval($A["id"]),
				"post_date"	=> _format_date($A["add_date"]),
				"post_title"=> _prepare_html($A["title"]),
				"post_link"	=> "./?object=".BLOG_CLASS_NAME."&action=show_single_post&id=".$A["id"],
			);
		}
		return $result;
	}

	/**
	* Display post item
	*/
	function _show_post_item ($post_info = array(), $num_comments = 0, $counter = 0, $stpl_name = "") {
		if (empty($stpl_name)) {
			$stpl_name = BLOG_CLASS_NAME."/view_blog_item";
		}
		$custom_cat_name	= $this->_user_custom_cats[$post_info["custom_cat_id"] - 1]["name"];
		$custom_cat_link	= $this->_user_custom_cats[$post_info["custom_cat_id"] - 1]["link"];
		
		//if this post in community
		$GLOBALS['user_info']["group"] == "99"?$post_info["user_id"] = $post_info["poster_id"]:"";

		// Check privacy permissions
		if (!$this->_privacy_check($this->BLOG_SETTINGS["privacy"], $post_info["privacy"], $post_info["user_id"])) {
			return false;
		}
		$cur_privacy = $post_info["privacy"] > $this->BLOG_SETTINGS["privacy"] ? $post_info["privacy"] : $this->BLOG_SETTINGS["privacy"];
		$cur_allow_comments = $post_info["allow_comments"] > $this->BLOG_SETTINGS["allow_comments"] ? $post_info["allow_comments"] : $this->BLOG_SETTINGS["allow_comments"];		// Process post template
		// Prepare attachment
		$attach_web_path = "";
		if (!empty($post_info["attach_image"])) {
			$attach_web_path	= $this->_attach_web_path($post_info);
			$attach_fs_path		= $this->_attach_fs_path($post_info);
			if (!file_exists($attach_fs_path)) {
				$attach_web_path = "";
			}
		}
		
		// Process template
		$replace = array(
			"counter"			=> $counter,
			"post_active"		=> intval($post_info["active"]),
			"own_blog"			=> intval($post_info["user_id"] == main()->USER_ID),
			"title"				=> $this->_format_text($post_info["title"]),
			"text"				=> $this->_format_text($post_info["text"]),
			"add_date"			=> _format_date($post_info["add_date"], "long"),
			"num_comments"		=> $cur_allow_comments < 9 ? intval($num_comments) : -1,
			"show_post_link"	=> "./?object=".BLOG_CLASS_NAME."&action=show_single_post&id=".($this->HIDE_TOTAL_ID ? $post_info["id2"] : $post_info["id"]). _add_get(array("page")),
			"edit_post_link"	=> $post_info["user_id"] == main()->USER_ID ? "./?object=".BLOG_CLASS_NAME."&action=edit_post&id=". ($this->HIDE_TOTAL_ID ? $post_info["id2"] : $post_info["id"]). _add_get(array("page")) : "",
			"delete_post_link"	=> $post_info["user_id"] == main()->USER_ID ? "./?object=".BLOG_CLASS_NAME."&action=delete_post&id=". ($this->HIDE_TOTAL_ID ? $post_info["id2"] : $post_info["id"]). _add_get(array("page")) : "",
			"attach_image_src"	=> $attach_web_path,
			"mood"				=> _prepare_html($this->_prepare_mood($post_info["mood"])),
			"mode_text"			=> _prepare_html(!empty($post_info["mode_type"]) ? $post_info["mode_text"] : ""),
			"mode_type"			=> _prepare_html($this->_mode_types[$post_info["mode_type"]]),
			"blog_cat_name"		=> !empty($this->_blog_cats[$post_info["cat_id"]]) ? _prepare_html($this->_blog_cats[$post_info["cat_id"]]) : "",
			"blog_cat_link"		=> !empty($this->_blog_cats[$post_info["cat_id"]]) ? "./?object=".BLOG_CLASS_NAME."&action=show_in_cat&id=".$post_info["cat_id"] : "",
			"custom_cat_name"	=> !empty($custom_cat_name) ? $custom_cat_name : "",
			"custom_cat_link"	=> $custom_cat_link,
			"privacy_status"	=> $cur_privacy > 0 ? $this->_privacy_types[$cur_privacy] : "",
			"comments_status"	=> $cur_allow_comments > 0 ? $this->_allow_comments_types[$cur_allow_comments] : "",
			"vote_popup_link"	=> $this->SHOW_REPUT_LINK ? process_url("./?object=reputation&action=vote_popup&id=".$post_info["user_id"]."&page=".$_GET["object"]."--".$post_info["id"]) : "",
			"tags_block"		=> $this->ALLOW_TAGGING ? $this->_tags[$post_info["id"]] : "",
		);
		return tpl()->parse($stpl_name, $replace);
	}

	/**
	* Display posts for the given user custom category
	*/
	function custom_category () {
		if ($this->HIDE_TOTAL_ID) {
			$user_id = $GLOBALS['HOSTING_ID'] ? $GLOBALS['HOSTING_ID'] : main()->USER_ID;
			$custom_cat_id = $_GET["id"];
		} else {
			list($user_id, $custom_cat_id) = explode("-", $_GET["id"]);
		}
		$user_id		= intval($user_id);
		if (empty($user_id)) {
			return _e(t("Wrong ID!"));
		}
		// Get current blog settings
		$this->BLOG_SETTINGS = $this->_get_user_blog_settings($user_id);
		// Try to resolve text category name into id
		if (is_numeric($custom_cat_id)) {
			$custom_cat_id = intval($custom_cat_id);
		} elseif ($this->CUSTOM_CATS_LINKS_TEXTS) {
			$custom_cat_id = str_replace("_", " ", urldecode($custom_cat_id));
			// Try to get custom category name
			if (!isset($this->_user_custom_cats)) {
				$this->_user_custom_cats	= $this->_custom_cats_into_array($this->BLOG_SETTINGS["custom_cats"]);
			}
			foreach ((array)$this->_user_custom_cats as $_cat_id => $_info) {
				if (str_replace("_", " ", strtolower($_info["name"])) == $custom_cat_id) {
					$custom_cat_id = $_cat_id + 1;
					break;
				}
			}
		}
		$custom_cat_id = intval($custom_cat_id);
		if (!$custom_cat_id) {
			return _e(t("No such category!"));
		}
		// Check if user already have started blog
		$num_user_posts = db()->query_num_rows(
			"SELECT id FROM ".db('blog_posts')." 
			WHERE user_id=".intval($user_id)." 
				AND custom_cat_id=".intval($custom_cat_id)
		);
		if (empty($num_user_posts)) {
			return t("No posts inside this category");
		} else {
			$body = $this->_view_user_posts(intval($user_id), intval($num_user_posts), array("custom_cat_id" => $custom_cat_id));
		}
		return $body;
	}

	/**
	* Display RSS feed for all blogs
	*/
	function rss_for_all_blogs() {
		return $this->_call_sub_method("blog_rss", "_display_for_all_blogs");
	}

	/**
	* Display RSS feed for posts from selected blog
	*/
	function rss_for_single_blog() {
		return $this->_call_sub_method("blog_rss", "_display_for_single_blog");
	}

	/**
	* Display RSS feed for posts from selected category
	*/
	function rss_for_cat() {
		return $this->_call_sub_method("blog_rss", "_display_for_cat");
	}

	/**
	* Display RSS feed for posts from selected blog
	*/
	function rss_for_friends_posts() {
		return $this->_call_sub_method("blog_rss", "_display_for_friends_posts");
	}

	/**
	* Display image button for RSS feed
	*/
	function _show_rss_link($feed_link = "", $feed_name = "") {
		// Do not show export links if turned off
		if (empty($this->ALLOW_RSS_EXPORT)) {
			return "";
		}
		return _class('graphics')->_show_rss_button($feed_name, $feed_link);
	}

	/**
	* Add new post
	*/
	function post () {
		return $this->add_post();
	}

	/**
	* Add post method
	*/
	function add_post () {
		return $this->_call_sub_method("blog_posting", "_add_post");
	}

	/**
	* Edit post method
	*/
	function edit_post () {
		return $this->_call_sub_method("blog_posting", "_edit_post");
	}

	/**
	* Delete post method
	*/
	function delete_post () {
		return $this->_call_sub_method("blog_posting", "_delete_post");
	}

	/**
	* Delete attached image
	*/
	function delete_attach_image () {
		return $this->_call_sub_method("blog_posting", "_delete_attach_image");
	}

	/**
	* Change user blog settings
	*/
	function settings () {
		return $this->_call_sub_method("blog_settings", "_change");
	}

	/**
	* Get user blog settings for one user (could be called from other modules)
	*/
	function _get_user_blog_settings ($user_id = 0) {
		if (empty($user_id)) {
			return false;
		}
		if (!empty(main()->USER_ID) && $user_id == main()->USER_ID && !empty($this->CUR_USER_BLOG_SETTINGS)) {
			return $this->CUR_USER_BLOG_SETTINGS;
		}
		// Try to get settings from db
		$BLOG_SETTINGS = db()->query_fetch("SELECT * FROM ".db('blog_settings')." WHERE user_id=".intval($user_id));
		if (empty($BLOG_SETTINGS)) {
			// Do create user blog settings (if not done yet)
			$this->_start_blog_settings($user_id);
			// Try again
			$BLOG_SETTINGS = db()->query_fetch("SELECT * FROM ".db('blog_settings')." WHERE user_id=".intval($user_id));
		}
		return $BLOG_SETTINGS;
	}

	/**
	* Get settings for many users ids (could be called from other modules)
	*/
	function _get_blog_settings_for_user_ids ($users_ids = array()) {
		if (!is_array($users_ids) || empty($users_ids)) {
			return false;
		}
		$Q = db()->query("SELECT * FROM ".db('blog_settings')." WHERE user_id IN(".implode(",", $users_ids).")");
		while ($A = db()->fetch_assoc($Q)) {
			$users_blog_settings[$A["user_id"]] = $A;
		}
		return $users_blog_settings;
	}

	/**
	* Create default blog settings fro the given user ID
	*/
	function _start_blog_settings ($user_id = 0) {
		return $this->_call_sub_method("blog_settings", "_start_blog_settings", $user_id);
	}

	/**
	* Update all blogs stats (stored in table blog_settings)
	*/
	function _update_all_stats () {
		return $this->_call_sub_method("blog_settings", "_update_all_stats");
	}

	/**
	* Convert blog links string into array
	*/
	function _blog_links_into_array ($raw_blog_links = "") {
		return $this->_call_sub_method("blog_settings", "_blog_links_into_array", $raw_blog_links);
	}

	/**
	* Convert custom categories string into array
	*/
	function _custom_cats_into_array ($raw_custom_cats = "") {
		return $this->_call_sub_method("blog_settings", "_custom_cats_into_array", $raw_custom_cats);
	}

	/**
	* Process custom box
	*/
	function _box ($name = "", $selected = "") {
		if (empty($name) || empty($this->_boxes[$name])) return false;
		else return eval("return common()->".$this->_boxes[$name].";");
	}

	/**
	* Special method for user profile
	*/
	function _show_for_profile ($user_info = array()) {
		// Get 10 latest posts
		$Q = db()->query("SELECT id,id2,title,add_date FROM ".db('blog_posts')." WHERE user_id=".intval($user_info["id"])." AND active=1 ORDER BY add_date DESC LIMIT ".intval($this->NUM_FOR_PROFILE));
		while ($A = db()->fetch_assoc($Q)) {
			$replace2 = array(
				"post_link"	=> "./?object=".BLOG_CLASS_NAME."&action=show_single_post&id=".($this->HIDE_TOTAL_ID ? $A["id2"] : $A["id"]). _add_get(array("page")),
				"post_title"=> $this->_format_text($A["title"]),
				"add_date"	=> _format_date($A["add_date"], "long"),
			);
			$items .= tpl()->parse(BLOG_CLASS_NAME."/for_profile_item", $replace2);
		}
		// Stop here if no items is found
		$items = trim($items);
		if (empty($items)) {
			return "";
		}
		// Process template
		$replace = array(
			"is_logged_in"	=> intval((bool) main()->USER_ID),
			"user_blog_link"=> "./?object=".BLOG_CLASS_NAME."&action=show_posts". ($this->HIDE_TOTAL_ID ? "" : "&id=".$user_info["id"]). _add_get(array("page")),
			"items"			=> $items,
		);
		return tpl()->parse(BLOG_CLASS_NAME."/for_profile_main", $replace);
	}

	/**
	* Return web path to given post attach
	*/
	function _attach_web_path ($post_info = array()) {
		$user_id = $post_info["user_id"];
		if ($this->HIDE_TOTAL_ID && $GLOBALS["HOSTING_FULL_NAME"]) {
			$attach_path = $this->ATTACH_IMAGES_DIR. $post_info["attach_image"];
			return "http://".$GLOBALS["HOSTING_FULL_NAME"]."/".$attach_path;
		}
		$attach_path	= $this->_get_attach_image_path($post_info["user_id"], $post_info["attach_image"]);
		return WEB_PATH. $attach_path;
	}

	/**
	* Return filesystem path to post attach
	*/
	function _attach_fs_path ($post_info = array()) {
		$user_id = $post_info["user_id"];
		if ($this->HIDE_TOTAL_ID && $GLOBALS["HOSTING_FULL_NAME"]) {
			$attach_path = $this->ATTACH_IMAGES_DIR. $post_info["attach_image"];
			return INCLUDE_PATH."users/".$GLOBALS["HOSTING_FULL_NAME"]."/".$attach_path;
		}
		$attach_path	= $this->_get_attach_image_path($post_info["user_id"], $post_info["attach_image"]);
		return INCLUDE_PATH. $attach_path;
	}

	/**
	* Return path to the user's attach images (internal only, do not use directly in your code!)
	*/
	function _get_attach_image_path ($user_id, $image_name = "") {
		return $this->ATTACH_IMAGES_DIR. _gen_dir_path($user_id, "", 0, $this->DEF_DIR_MODE). $image_name;
	}

	/**
	* Check privacy permissions (allow current user to view or not)
	*/
	function _privacy_check ($blog_privacy = 0, $post_privacy = 0, $post_author_id = 0) {
		$OBJ = $this->_load_sub_module("blog_utils");
		return $OBJ->_privacy_check ($blog_privacy, $post_privacy, $post_author_id);
	}

	/**
	* Check allow comments (allow current user to view/post or not)
	*/
	function _comment_allowed_check ($blog_comments = 0, $post_comments = 0, $post_author_id = 0) {
		$OBJ = $this->_load_sub_module("blog_utils");
		return $OBJ->_comment_allowed_check ($blog_comments, $post_comments, $post_author_id);
	}

	/**
	* Check if post comment is allowed
	*
	* @access	private
	* @return	bool
	*/
	function _comment_is_allowed ($params = array()) {
		if ($_GET["action"] == "show_single_post") {
			// Check if target user is ignored by owner
			if (common()->_is_ignored(main()->USER_ID, $this->_post_info["user_id"])) {
				return false;
			}
			return $this->_comment_allowed_check ($this->BLOG_SETTINGS["allow_comments"], $this->_post_info["allow_comments"], $this->_post_info["user_id"]);
		}
		return true;
	}

	/**
	* Execute this on comment update (add/edit/delete) action
	*
	* @access	private
	* @return	bool
	*/
	function _comment_on_update ($params = array()) {
		// Remove activity points
		if ($_GET["action"] == "delete_comment") {
			common()->_remove_activity_points(main()->USER_ID, "blog_comment");
		}
		// Synchronize all blogs stats
		$this->_update_all_stats();
	}

	/**
	* Return mood name
	*/
	function _prepare_mood ($mood = "") {
		$mood = trim($mood);
		if (empty($mood)) {
			return false;
		}
		if ($mood == 1) {
			return false;
		}
		if (is_numeric($mood) && isset($this->_moods[$mood])) {
			return $this->_moods[$mood];
		} elseif (is_string($mood)) {
			return $mood;
		}
	}

	/**
	* Try to load blog sub_module
	*/
	function _load_sub_module ($module_name = "") {
		$OBJ = main()->init_class($module_name, BLOG_MODULES_DIR);
		if (!is_object($OBJ)) {
			trigger_error("BLOG: Cant load sub_module \"".$module_name."\"", E_USER_WARNING);
			return false;
		}
		return $OBJ;
	}

	/**
	* Call sub_module method
	*/
	function _call_sub_method ($sub_module = "", $method_name = "", $params = array()) {
		$OBJ = $this->_load_sub_module($sub_module);
		return is_object($OBJ) ? $OBJ->$method_name($params) : "";
	}

	/**
	* Generate filter SQL query
	*/
	function _create_filter_sql () {
		$OBJ = $this->_load_sub_module("blog_filter");
		return is_object($OBJ) ? $OBJ->_create_filter_sql() : "";
	}

	/**
	* Session - based filter form
	*/
	function _show_filter () {
		$OBJ = $this->_load_sub_module("blog_filter");
		return is_object($OBJ) ? $OBJ->_show_filter() : "";
	}

	/**
	* Filter save method
	*/
	function save_filter ($silent = false) {
		$OBJ = $this->_load_sub_module("blog_filter");
		return is_object($OBJ) ? $OBJ->_save_filter($silent) : "";
	}

	/**
	* Clear filter
	*/
	function clear_filter ($silent = false) {
		$OBJ = $this->_load_sub_module("blog_filter");
		return is_object($OBJ) ? $OBJ->_clear_filter($silent) : "";
	}

	/**
	* Clear filter
	*/
	function _fix_id2 ($user_id = 0) {
		$OBJ = $this->_load_sub_module("blog_settings");
		return is_object($OBJ) ? $OBJ->_fix_id2($user_id) : "";
	}

	/**
	* Ban error display
	*/
	function _ban_check () {
		if ($this->_user_info["ban_blog"]) {
			return _e(
				"You broke some of our rules, so you are not allowed to post in blog!"
				."For more details <a href=\"./?object=faq&action=view&id=16\">click here</a>"
			);
		}
		return false;
	}

	/**
	* Ping Google on add new post
	*
	* @access	private
	* @return	bool
	*/
	function _do_ping ($record_id = 0, $blog_id = 0) {
		$OBJ = $this->_load_sub_module("blog_ping");
		return is_object($OBJ) ? $OBJ->_do_ping($record_id, $blog_id) : "";
	}

	/**
	* Hook for the site_map
	*/
	function _site_map_items ($SITE_MAP_OBJ = false) {
		$OBJ = $this->_load_sub_module("blog_utils");
		return $OBJ->_site_map_items($SITE_MAP_OBJ);
	}

	/**
	* Hook for navigation bar
	*/
	function _nav_bar_items ($params = array()) {
		$OBJ = $this->_load_sub_module("blog_utils");
		return is_object($OBJ) ? $OBJ->_nav_bar_items($params) : "";
	}

	/**
	* Home page integration
	*/
	function _for_home_page($NUM_NEWEST_BLOG_POSTS = 4, $NEWEST_BLOG_TEXT_LEN = 100, $params = array()){
		$OBJ = $this->_load_sub_module("blog_integration");
		return $OBJ->_for_home_page($NUM_NEWEST_BLOG_POSTS, $NEWEST_BLOG_TEXT_LEN, $params);
	}
	
	/**
	* Integration into user profile
	*/
	function _for_user_profile($id, $MAX_SHOW_BLOG_POSTS = 10){
		$OBJ = $this->_load_sub_module("blog_integration");
		return is_object($OBJ) ? $OBJ->_for_user_profile($id, $MAX_SHOW_BLOG_POSTS) : "";
	}

	/**
	* Force cut bb codes
	*/
	function _cut_bb_codes ($body = "") {
		return preg_replace("/\[[^\]]+\]/ims", "", $body);
	} 

	/**
	* Manage comments
	*/
	function search_comments () {
		$OBJ = $this->_load_sub_module("blog_search_comments");
		return $OBJ->search_comments();
	}

	/**
	* Do delete blog comment
	*/
	function delete_blog_comment () {
		$OBJ = $this->_load_sub_module("blog_search_comments");
		return $OBJ->_delete();
	} 
	
	/**
	* Blog last post
	*/
	function _widget_last_post ($params = array()) {
		$OBJ = $this->_load_sub_module("blog_widgets");
		return $OBJ->_widget_last_post($params);
	}
	
	/**
	* Blog last posts
	*/
	function _widget_last_posts ($params = array()) {
		$OBJ = $this->_load_sub_module("blog_widgets");
		return $OBJ->_widget_last_posts($params);
	}
	
	/**
	* Widget categories
	*/
	function _widget_categories ($params = array()) {
		$OBJ = $this->_load_sub_module("blog_widgets");
		return $OBJ->_widget_categories($params);
	}

	/**
	* Cloud of tags for blog
	*/
	function _widget_tags_cloud ($params = array()) {
		$OBJ = $this->_load_sub_module("blog_widgets");
		return $OBJ->_widget_tags_cloud($params);
	}

	/**
	* Most commented blog entries
	*/
	function _widget_most_commented ($params = array()) {
		$OBJ = $this->_load_sub_module("blog_widgets");
		return $OBJ->_widget_most_commented($params);
	}

	/**
	* Blog archive links 
	*/
	function _widget_archive ($params = array()) {
		$OBJ = $this->_load_sub_module("blog_widgets");
		return $OBJ->_widget_archive($params);
	}

	/**
	* Friendly sites 
	*/
	function _widget_friendly_sites ($params = array()) {
		$OBJ = $this->_load_sub_module("blog_widgets");
		return $OBJ->_widget_friendly_sites($params);
	}
	
	/**
	* Hook for RSS
	*/
	function _rss_general(){
		$OBJ = $this->_load_sub_module("blog_integration");
		return $OBJ->_rss_general();
	}

	/**
	* This method called on settings update to synchronize with site modules
	*/
	function _callback_on_update ($data = array()) {
		if (empty($data)) {
			return false;
		}
		return true;
	}

	/**
	* Page header hook
	*/
	function _show_header() {
		$pheader = t("Blog");
		// Default subheader get from action name
		$subheader = _ucwords(str_replace("_", " ", $_GET["action"]));

		// Array of replacements
		$cases = array (
			//$_GET["action"] => {string to replace}
			"show"					=> "",
			"view"					=> "",
			"show_in_cat"			=> "",
			"show_posts"			=> "",
			"show_all_blogs"		=> "",
			"show_single_post"		=> "",
			"search_comments"		=> "Comments to my blog",
			"edit_post"				=> "Edit blog entry",
			"add_post"				=> "Post a new blog entry",
			"show_most_commented" 	=> "Most Commented Blog Posts",
			"show_most_read"		=> "Most Read Blog Posts",
			"start"					=> "Start your blog",
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
	function _show_title() {
		$title = $this->BLOG_SETTINGS["blog_title"];
		$subtitle = "";

		if ($_GET["action"] == "custom_category") {
			$subtitle = $this->CUSTOM_CAT_NAME;
		} elseif ($_GET["action"] == "show_single_post") {
			$subtitle = $this->_post_info["title"];
		} elseif ($_GET["action"] == "show_posts_archive") {
			$subtitle = $this->SOURCE_ARCHIVE_DATE;
		}
		if ($subtitle) {
			$title .= " : ".$subtitle;
		}
		return $title;
	}

	/**
	* Quick menu auto create
	*/
	function _quick_menu () {
		$menu = array(
			array(
				"name"	=> "Blog settings",
				"url"	=> "./?object=blog&action=settings",
			),
			array(
				"name"	=> "Go to my blog",
				"url"	=> "./?object=blog&action=show_posts",
			),
			array(
				"name"	=> "View current content",
				"url"	=> "./?object=blog",
			),
			array(
				"name"	=> "Add post",
				"url"	=> "./?object=blog&action=add_post",
			),
			array(
				"name"	=> "Comments to my blog",
				"url"	=> "./?object=blog&action=search_comments",
			),
		);
		return $menu;	
	}
}
