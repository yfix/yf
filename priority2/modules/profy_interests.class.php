<?php

/**
* Users interests handler
* 
* @package		Profy Framework
* @author		Yuri Vysotskiy <profy.net@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class profy_interests {

	/** @var int */
	var $MAX_KEYWORDS_NUM		= 25;
	/** @var int */
	var $MIN_KEYWORD_LENGTH		= 3;
	/** @var int */
	var $MAX_KEYWORD_LENGTH		= 30;
	/** @var int */
	var $DISPLAY_MOST_POPULAR	= 50;
	/** @var int */
	var $CLOUD_MIN_FONT_SIZE	= 10;
	/** @var int */
	var $CLOUD_FONT_SIZE_STEP	= 1;
	/** @var bool */
	var $STATS_SORT_BY_NAME		= true;
	/** @var bool */
	var $JS_CHECK				= true;
	/** @var bool */
	var $UTF8_MODE				= true;

	/**
	* Profy framework module constructor
	*
	* @access	private
	* @return	void
	*/
	function _init () {
		// class name (to allow changing only in one place)
		define("INTERESTS_CLASS_NAME", "interests");
		// Get user account type
		$this->_account_types	= main()->get_data("account_types");
	}

	/**
	* Default method
	*/
	function show () {
		return $this->_show_stats();
	}

	/**
	* Statistics page
	*/
	function _show_stats () {
		// Get most popular keywords
		$A = db()->query_fetch_all("SELECT * FROM `".db('interests_keywords')."` ORDER BY `users` DESC LIMIT ".intval($this->DISPLAY_MOST_POPULAR));
		foreach ((array)$A as $data) {
			$cloud_data[$data["keyword"]] = $data["users"];
		}

		$cloud = common()->_create_cloud($cloud_data, array("object" => INTERESTS_CLASS_NAME));
		// Process template
		$replace = array(
			"search_link"	=> "./?object=".INTERESTS_CLASS_NAME."&action=search",
			"manage_link"	=> "./?object=".INTERESTS_CLASS_NAME."&action=manage",
			"cloud"			=> $cloud,
		);
		return tpl()->parse(INTERESTS_CLASS_NAME."/main_page", $replace);
	}

	/**
	* View user's interests
	*/
	function view () {
		if (empty($_GET["id"])) {
			return _e(t("No user id"));
		}
		// Check user
		$user_info = user($_GET["id"], "full", array("WHERE" => array("active" => 1)));


		if (empty($user_info)) {
			return _e(t("No such user"));
		}
		if (!isset($GLOBALS['user_info'])) {
			$GLOBALS['user_info'] = $user_info;
		}
		// Try to get data
		$interests_info = db()->query_fetch("SELECT * FROM `".db('interests')."` WHERE `user_id`=".intval($_GET["id"]));
		// Prepare keywords with links to search
		foreach ((array)explode(";", trim($interests_info["keywords"])) as $cur_word) {
			if (empty($cur_word)) {
				continue;
			}
			// Cut long keywords
			if ($this->MAX_KEYWORD_LENGTH && strlen($cur_word) > $this->MAX_KEYWORD_LENGTH) {
				$cur_word = substr($cur_word, 0, $this->MAX_KEYWORD_LENGTH);
			}
			$replace2 = array(
				"search_link"	=> "./?object=".INTERESTS_CLASS_NAME."&action=search&id=".$this->_prepare_keyword_for_url($cur_word),
				"keyword"		=> _prepare_html($cur_word),
				"need_div"		=> 1,
			);
			$items .= tpl()->parse(INTERESTS_CLASS_NAME."/view_item", $replace2);
		}
		// Process template
		$replace = array(
			"items"				=> $items,
			"pages"				=> $pages,
			"total"				=> intval($total),
			"user_profile_link"	=> _profile_link($user_info),
			"user_name"			=> _prepare_html(_display_name($user_info)),
			"user_avatar"		=> _show_avatar($user_info["id"], $user_info/*, 1, 1*/),
		);
		return tpl()->parse(INTERESTS_CLASS_NAME."/view_main", $replace);
	}

	/**
	* Manage own interests
	*/
	function manage ($FORCE_USER_ID = false) {
		$USER_ID = $FORCE_USER_ID ? $FORCE_USER_ID : $this->USER_ID;
		if (!$FORCE_USER_ID && empty($USER_ID)) {
			return _error_need_login();
		}
		// Array of keywords
		$this->_activities	= main()->get_data("locale:activities");
		// Get activities
		$act_info = db()->query_fetch("SELECT * FROM `".db('prof_keywords')."` WHERE `user_id`=".intval($USER_ID));
		if (empty($act_info)) {
			db()->INSERT("prof_keywords", array(
				"user_id"	=> intval($USER_ID),
				"keywords"	=> "",
			));
		}
		// Prepare saved keywords
		foreach (explode(";", $act_info["keywords"]) as $_id) {
			if (empty($_id)) {
				continue;
			}
			$selected_ids[$_id] = $_id;
		}
		$num_cols		= 2;
		$total_words	= count($this->_activities);
		// Prepare prof interests
		foreach ((array)$this->_activities as $_id => $_cur_word) {
			$act_keywords[$_cur_word] = array(
				"id"		=> intval($_id),
				"word"		=> _prepare_html($_cur_word),
				"checked"	=> isset($selected_ids[$_id]) ? 1 : 0,
				"need_div"	=> !(++$i % ceil($total_words / $num_cols)) ? 1 : 0,
			);
		}
		// Try to get data
		$interests_info = db()->query_fetch("SELECT * FROM `".db('interests')."` WHERE `user_id`=".intval($USER_ID));
		// Save form
		if (!empty($_POST)) {
			// Cleanup keywords
			$_POST["keywords"] = $this->_pack_for_db($_POST["keywords"]);
			// Check for errors and continue
			if (!common()->_error_exists()/* && strlen($_POST["keywords"])*/) {
				// Do create initial record for the current user
				if (!isset($interests_info["user_id"])) {
					db()->INSERT("interests", array(
						"user_id"	=> $USER_ID,
					));
				}
				// Update record
				db()->UPDATE("interests", array(
					"keywords"	=> _es($_POST["keywords"]),
				), "`user_id`=".intval($USER_ID));
				// Update user stats
				main()->call_class_method("user_stats", "classes/", "_update", array("user_id" => $USER_ID));
				// Execute re-count on shutdown
//				register_shutdown_function(array(&$this, "_update_unique_keywords"));
				$this->_update_unique_keywords();
				// Save activities
//				if (isset($_POST["act_keywords"])) {
					$ids_to_save = array();
					foreach ((array)$_POST["act_keywords"] as $_id) {
						if (empty($_id) || !isset($this->_activities[$_id])) {
							continue;
						}
						$ids_to_save[$_id] = $_id;
					}
					// Save db record
//					if (!empty($ids_to_save)) {
						db()->UPDATE("prof_keywords", array(
							"keywords"	=> (";"._es(implode(";", $ids_to_save)).";"),
						), "`user_id`=".intval($USER_ID));
//					}
//				}
			}
			// Return user back
			return !$FORCE_USER_ID ? js_redirect("./?object=".INTERESTS_CLASS_NAME."&action=".$_GET["action"]) : "";
		}
		// Fill POST data
		foreach ((array)$interests_info as $k => $v) {
			$DATA[$k] = isset($_POST[$k]) ? $_POST[$k] : $v;
		}
		// Process teplate
		$replace = array(
			"form_action"	=> "./?object=".INTERESTS_CLASS_NAME."&action=".$_GET["action"],
			"error_message"	=> _e(),
			"keywords"		=> trim(str_replace(";", "\r\n", $DATA["keywords"])),
			"max_items"		=> intval($this->MAX_KEYWORDS_NUM),
			"min_length"	=> intval($this->MIN_KEYWORD_LENGTH),
			"max_length"	=> intval($this->MAX_KEYWORD_LENGTH),
			"js_check"		=> intval((bool)$this->JS_CHECK),
			"act_keywords"	=> $act_keywords,
		);
		return tpl()->parse(INTERESTS_CLASS_NAME."/manage", $replace);
	}

	/**
	* Interviews search engine
	*/
	function search () {
		// Prepare search keyword
		$KEYWORD = $_REQUEST["id"];
		$KEYWORD = str_replace("+", " ", $KEYWORD);
		$KEYWORD = trim(preg_replace("/[^a-z0-9 -ï€-Ÿ\-\s]/ims", "", $KEYWORD));
		$KEYWORD = trim(preg_replace("/[\s]{2,}/ims", " ", $KEYWORD));
		// Switch between match types
		$EXACT_MATCH = 1;
		if (!empty($_POST["match"]) && (empty($_SESSION["_interests_match"]) || $_POST["match"] != $_SESSION["_interests_match"])) {
			$_SESSION["_interests_match"] = $_POST["match"];
		}
		if ($_POST["match"] == "partial" || $_SESSION["_interests_match"] == "partial") {
			$EXACT_MATCH = 0;
		}
		// Do search
		$sql = "SELECT * FROM `".db('interests')."` ".(!empty($KEYWORD) ? "WHERE `keywords` LIKE '%;"._es($KEYWORD). ($EXACT_MATCH ? ";" : "")."%'" : "");
		$order_by_sql = " ORDER BY `user_id` ASC ";
		$url = "./?object=".INTERESTS_CLASS_NAME."&action=".$_GET["action"]."&id=".(strlen($KEYWORD) ? $this->_prepare_keyword_for_url($KEYWORD) : "_");
		list($add_sql, $pages, $total) = common()->divide_pages($sql, $url);
		// Get records
		$Q = db()->query($sql.$order_by_sql.$add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$users_ids[$A["user_id"]] = $A["user_id"];
			$users_keywords[$A["user_id"]] = explode("\r\n", $this->_unpack_from_db($A["keywords"]));
			sort($users_keywords[$A["user_id"]]);
		}
		// Get users details
		if (!empty($users_ids)) {
			$users_infos = user($users_ids, "full", array("WHERE" => array("active" => 1)));

		}
		// Process users
		foreach ((array)$users_ids as $cur_user_id) {
			$user_info = $users_infos[$cur_user_id];
			$prepared_keywords	= array();
			foreach ((array)$users_keywords[$cur_user_id] as $cur_word) {
				$prepared_word = _prepare_html($cur_word);
				if (!$EXACT_MATCH) {
					$prepared_word = highlight($prepared_word, $KEYWORD);
				}
				$prepared_keywords[] = array(
					"word"			=> $prepared_word,
					"search_link"	=> "./?object=".INTERESTS_CLASS_NAME."&action=search&id=".$this->_prepare_keyword_for_url($cur_word),
				);
			}
			// Process template
			$replace2 = array(
				"bg_class"			=> !(++$i % 2) ? "bg1" : "bg2",
				"user_profile_link"	=> _profile_link($user_info),
				"user_name"			=> $user_info["group"]=="99"?_prepare_html(_display_name($user_info))." (".t("community").")":_prepare_html(_display_name($user_info)),
				"user_avatar"		=> _show_avatar($user_info["id"], $user_info, 1),
				"keywords"			=> !empty($prepared_keywords) ? $prepared_keywords : "",
				"account_type"		=> _prepare_html($this->_account_types[$user_info["group"]]),
				"sex"				=> _prepare_html($user_info["sex"]),
				"age"				=> !empty($user_info["age"]) ? intval($user_info["age"]) : "",
			);
			$items .= tpl()->parse(INTERESTS_CLASS_NAME."/search_item", $replace2);
		}
		// Process template
		$replace = array(
			"items"			=> $items,
			"pages"			=> $pages,
			"total"			=> intval($total),
			"search_form"	=> $this->_show_search_form($KEYWORD, $EXACT_MATCH),
		);
		return tpl()->parse(INTERESTS_CLASS_NAME."/search_main", $replace);
	}

	/**
	* Display search form
	*/
	function _show_search_form ($cur_word = "", $EXACT_MATCH = 0) {
		$replace = array(
			"form_action"	=> "./?object=".INTERESTS_CLASS_NAME."&action=".$_GET["action"],
			"keyword"		=> $cur_word,
			"exact_match"	=> intval($EXACT_MATCH),
		);
		return tpl()->parse(INTERESTS_CLASS_NAME."/search_form", $replace);
	}

	/**
	* Get user's interests
	*/
	function _get_for_user_id ($user_id = 0) {
		$output_array = array();
		if (empty($user_id)) {
			return $output_array;
		}
		// Try to get data
		$interests_info = db()->query_fetch("SELECT * FROM `".db('interests')."` WHERE `user_id`=".intval($user_id));
		if (empty($interests_info)) {
			return $output_array;
		}
		// Prepare keywords with links to search
		foreach ((array)explode(";", trim($interests_info["keywords"])) as $cur_word) {
			if (!strlen($cur_word)) {
				continue;
			}
			$output_array[$cur_word] = array(
				"search_link"	=> "./?object=".INTERESTS_CLASS_NAME."&action=search&id=".$this->_prepare_keyword_for_url($cur_word),
				"keyword"		=> _prepare_html($cur_word),
				"need_div"		=> 1,
			);
		}
		return $output_array;
	}

	/**
	* Prepare keyword for the url
	*/
	function _prepare_keyword_for_url ($cur_word = "") {
		return rawurlencode(str_replace(" ", "+", $cur_word));
	}

	/**
	* Prepare for db
	*/
	function _pack_for_db ($source = "") {
		if (empty($source)) {
			return "";
		}
		$keywords_array = array();
		$source = str_replace(array("\r", "\t"), array("", " "), $source);
		$source = trim(preg_replace("/[ ]{2,}/ims", " ", $source));
		$source = trim(preg_replace("/[\n]{2,}/ims", "\n", $source));
		// Allow here only these below \x7F == 127 (ASCII) :
		//		\x0A == 13 (CR), 
		//		\x20 == 32 (Space), 
		//		\x30-\x39 (0-9), 
		//		\x41-\x5A (A-Z),
		//		\x61-\x7A (a-z)
		$source = trim(preg_replace('/[\x00-\x09\x0B-\x1F\x21-\x2F\x3A-\x40\x5B-\x60\x7B-\x7F]/ims', "", $source));
//		$source = strtolower(trim(preg_replace("/[^a-z0-9\-\s\n]/ims", "", $source)));
		// Split by lines
		$lines	= explode("\n", $source);
		if (count($lines) > $this->MAX_KEYWORDS_NUM) {
			common()->_raise_error("Too many keywords (".count($lines)."), allowed max=".$this->MAX_KEYWORDS_NUM);
		}
		// Last cleanup
		foreach ((array)$lines as $cur_word) {
			if (empty($cur_word) || (strlen($cur_word) * ($this->UTF8_MODE ? 2 : 1)) < $this->MIN_KEYWORD_LENGTH) {
				common()->_raise_error("Keyword \""._prepare_html($cur_word)."\" is too short (min length=".$this->MIN_KEYWORD_LENGTH.")");
//				continue;
			}
			// Check max number of keywords
			if (++$i > $this->MAX_KEYWORDS_NUM) {
				break;
			}
			// Cut long keywords
			if ($this->MAX_KEYWORD_LENGTH && strlen($cur_word) > $this->MAX_KEYWORD_LENGTH * ($this->UTF8_MODE ? 2 : 1)) {
				common()->_raise_error("Keyword \""._prepare_html($cur_word)."\" is too long (max length=".$this->MAX_KEYWORD_LENGTH.")");
//				$cur_word = substr($cur_word, 0, $this->MAX_KEYWORD_LENGTH);
			}
			if (!isset($keywords_array[$cur_word])) {
				$keywords_array[$cur_word] = $cur_word;
			}
		}
		$output = "";
		if (!empty($keywords_array)) {
			$output = ";".implode(";", $keywords_array).";";
		}
		return $output;
	}

	/**
	* Prepare from db
	*/
	function _unpack_from_db ($source = "") {
		return trim(str_replace(";", "\r\n", $source));
	}

	/**
	* Insert unique interests (from all users) into keywords table
	* (it's good to run this method on shutdown, because it can take long time to wait for)
	*/
	function _update_unique_keywords () {
		// Optimize interests
		db()->query("DELETE FROM `".db('interests')."` WHERE `keywords` IN('', ';;')");
		db()->query("UPDATE `".db('interests')."` SET `keywords` = LOWER(`keywords`)");
//		db()->query("OPTIMIZE TABLE `".db('interests')."`");
		// Cleanup keywords
		db()->query("TRUNCATE TABLE `".db('interests_keywords')."`");
		// Collect unique words
		for ($i = 0; $i < $this->MAX_KEYWORDS_NUM; $i++) {
			$sql_for_keyword = "LOWER(SUBSTRING_INDEX(SUBSTRING_INDEX(`keywords`,';',".intval($i + 2)."),';',-1))";
			// Fix very long keywords (if these possibly arrived)
			if ($this->MAX_KEYWORD_LENGTH) {
				$sql_for_keyword = "SUBSTR(".$sql_for_keyword.",1,".$this->MAX_KEYWORD_LENGTH.")";
			}
			$sql = "INSERT INTO ".db('interests_keywords')." (`keyword`) 
					SELECT ".$sql_for_keyword." AS `0` 
					FROM `".db('interests')."` 
					WHERE `keywords` NOT IN('', ';;') 
					HAVING `0` != ''
					ON DUPLICATE KEY UPDATE `users` = `users` + 1";
			db()->query($sql);
		}
		db()->query("UPDATE `".db('interests_keywords')."` SET `users` = `users` + 1");
		db()->query("DELETE FROM `".db('interests_keywords')."` WHERE `keyword`=''");
//		db()->query("OPTIMIZE TABLE `".db('interests_keywords')."`");
	}

	/**
	* !!! ONLY FOR TESTING !!!
	*/
	function _insert_test_keywords () {
		set_time_limit(600);
		ignore_user_abort(1);
		// First insert test interests
		$Q = db()->query("SELECT * FROM `".db('user')."` WHERE `active`='1'");
		while ($A = db()->fetch_assoc($Q)) {
			$rnd_data = "";
			// Prepare random data
			if (rand(0,1)) {
				$rnd_data .= $A["orientation"]."\r\n";
			}
			if (rand(0,1)) {
				$rnd_data .= $A["star_sign"]."\r\n";
			}
			if (rand(0,1)) {
				$rnd_data .= $A["smoking"]."\r\n";
			}
			if (rand(0,1)) {
				$rnd_data .= $A["english"]."\r\n";
			}
			if (rand(0,1)) {
				$rnd_data .= $A["eye_color"]."\r\n";
			}
			if (rand(0,1)) {
				$rnd_data .= $A["hair_color"]."\r\n";
			}
			// Do insert values
			db()->query("REPLACE INTO `".db('interests')."` (`user_id`,`keywords`) VALUES (".intval($A["id"]).",'"._es($this->_pack_for_db(strtolower($rnd_data)))."')");
			// Sleep some time
			if (!(++$counter % 10000)) {
				sleep(1);
			}
		}
		db()->query("DELETE FROM `".db('interests')."` WHERE `keywords` IN('', ';;')");
		db()->query("OPTIMIZE TABLE `".db('interests')."`");
	}
		
	/**
	* Hook for the home page
	*/
	function _for_home_page($NUM_MOST_POPULAR_INTERESTS = 10, $MIN_FONT_SIZE = 10, $FONT_SIZE_STEP = 1, $STATS_SORT_BY_NAME = true){

		$NUM_MOST_POPULAR_INTERESTS ? $this->NUM_MOST_POPULAR_INTERESTS = $NUM_MOST_POPULAR_INTERESTS:"";
		$MIN_FONT_SIZE ? $this->MIN_FONT_SIZE = $MIN_FONT_SIZE:"";
		$FONT_SIZE_STEP ? $this->FONT_SIZE_STEP = $FONT_SIZE_STEP:"";
		$STATS_SORT_BY_NAME ? $this->STATS_SORT_BY_NAME = $STATS_SORT_BY_NAME:"";

		$Q = db()->query("SELECT * FROM `".db('interests_keywords')."` ORDER BY `users` DESC LIMIT ".intval($this->NUM_MOST_POPULAR_INTERESTS));
		while ($A = db()->fetch_assoc($Q)) {
			$top_keywords[$A["keyword"]]	= $A["users"];
			$font_sizes[$A["keyword"]]		= round(($this->MIN_FONT_SIZE + $this->NUM_MOST_POPULAR_INTERESTS * $this->FONT_SIZE_STEP) - (++$k * $this->FONT_SIZE_STEP));
		}
		// Sort by name (if needed)
		if (!empty($top_keywords) && $this->STATS_SORT_BY_NAME) {
			ksort($top_keywords);
		}
		foreach ((array)$top_keywords as $cur_word => $num_users) {
			$most_popular[] = array(
				"bg_class"		=> !(++$i % 2) ? "bg1" : "bg2",
				"pos"			=> intval($i),
				"font_size"		=> $font_sizes[$cur_word],
				"users"			=> intval($num_users),
				"keyword"		=> _prepare_html($cur_word),			
				"search_link"	=> "./?object=interests&action=search&id=".$this->_prepare_keyword_for_url($cur_word),
			);
		}
		// Process template
		$replace = array(
			"most_popular"	=> $most_popular,
		);
		return tpl()->parse(__CLASS__ ."/for_home_page", $replace);
	}

	/**
	* Widget cloud of interests
	*/
	function _widget_cloud ($params = array()) {

		if ($params["describe"]) {
			return array("allow_cache" => 0);
		}
		$cloud_data = array();
		// Get most popular keywords
		$A = db()->query_fetch_all("SELECT * FROM `".db('interests_keywords')."` ORDER BY `users` DESC LIMIT ".intval($this->DISPLAY_MOST_POPULAR));
		foreach ((array)$A as $data) {
			$cloud_data[$data["keyword"]] = $data["users"];
		}
		$items = $cloud_data ? common()->_create_cloud($cloud_data, array("object" => INTERESTS_CLASS_NAME)) : "";
		if (!$items) {
			return "";
		}
		// Process template
		$replace = array(
			"items"			=> $items,
		);
		return tpl()->parse(INTERESTS_CLASS_NAME."/widget_cloud", $replace);
	}

	/**
	* Page header hook
	*/
	function _show_header() {
		// Default subheader get from action name
		$subheader = _ucwords(str_replace("_", " ", $_GET["action"]));
		// Array of replacements
		$cases = array (
			//$_GET["action"] => {string to replace}
			"manage"	=> "",
			"show"		=> "",
		);
		if (isset($cases[$_GET["action"]])) {
			// Rewrite default subheader
			$subheader = $cases[$_GET["action"]];
		}
		return array(
			"header"	=> $page_header ? _prepare_html($page_header) : "Interests",
			"subheader"	=> $subheader ? _prepare_html($subheader) : "",
		);
	}

}
