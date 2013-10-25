<?php

//-----------------------------------------------------------------------------
// User stats manager
class yf_user_stats {

	/** @var bool */
	public $STATS_ENABLED = true;
	/** @var bool */
	public $ENABLE_REFRESH_STATS	= false;
	/** @var */
	public $_sql_array	= array();

	//-----------------------------------------------------------------------------
	// Framework constructor
	function _init () {
		main()->USER_ID = $_SESSION['user_id'];
		// Get user account type
		$this->_account_types	= main()->get_data("account_types");
		// Get unified items stats
		$this->_sql_array = array(
			"gallery_photos"	=> "SELECT COUNT(id) AS `0` FROM ".db('gallery_photos')." WHERE user_id={_USER_ID_}",
			"blog_posts"		=> "SELECT COUNT(id) AS `0` FROM ".db('blog_posts')." WHERE user_id={_USER_ID_}",
			"articles"			=> "SELECT COUNT(id) AS `0` FROM ".db('articles_texts')." WHERE user_id={_USER_ID_}",
			"forum_posts"		=> "SELECT COUNT(id) AS `0` FROM ".db('forum_posts')." WHERE user_id={_USER_ID_}",
			"favorite_users"	=> "SELECT COUNT(id) AS `0` FROM ".db('favorites')." WHERE user_id={_USER_ID_}",
			"ignored_users"		=> "SELECT COUNT(*) AS `0` FROM ".db('ignore_list')." WHERE user_id={_USER_ID_}",
			"reput_points"		=> "SELECT points AS `0` FROM ".db('reput_total')." WHERE user_id={_USER_ID_}",
			"activity_points"	=> "SELECT points AS `0` FROM ".db('activity_total')." WHERE user_id={_USER_ID_}",
			"try_interests"		=> "SELECT LENGTH(REPLACE(keywords,';','')) AS `0` FROM ".db('interests')." WHERE user_id={_USER_ID_}",
			"try_friends"		=> "SELECT LENGTH(REPLACE(friends_list,',','')) AS `0` FROM ".db('friends')." WHERE user_id={_USER_ID_}",
		);
		// Turn off inactive modules stats
		$_active_modules = main()->get_data("user_modules");
		if (!isset($_active_modules["gallery"])) {
			unset($this->_sql_array["gallery_photos"]);
		}
		if (!isset($_active_modules["blog"])) {
			unset($this->_sql_array["blog_posts"]);
		}
		if (!isset($_active_modules["articles"])) {
			unset($this->_sql_array["articles"]);
		}
		if (!isset($_active_modules["forum"])) {
			unset($this->_sql_array["forum_posts"]);
		}
		if (!isset($_active_modules["reputation"])) {
			unset($this->_sql_array["reput_points"]);
		}
		if (!isset($_active_modules["activity"])) {
			unset($this->_sql_array["activity_points"]);
		}
		if (!isset($_active_modules["interests"])) {
			unset($this->_sql_array["try_interests"]);
		}
		if (!isset($_active_modules["friends"])) {
			unset($this->_sql_array["try_friends"]);
		}
	}

	//-----------------------------------------------------------------------------
	// Updated given user stats (special wrapper to call it from main->_execute)
	function _update ($params = array()) {
		if (!$this->STATS_ENABLED) {
			return false;
		}
		return $this->_update_user_stats ($params["user_id"], (array)$params["user_info"]);
	}

	//-----------------------------------------------------------------------------
	// Get user stats for given users ids (special wrapper to call it from main->_execute)
	function _get ($params = array()) {
		if (!$this->STATS_ENABLED) {
			return false;
		}
		return $this->_get_user_stats_for_ids (array($params["user_ids"]));
	}

	//-----------------------------------------------------------------------------
	// Get user stats for given user id (not cached)
	function _get_live_stats ($params = array()) {
		if (!$this->STATS_ENABLED) {
			return false;
		}
		$user_id = $params["user_id"];
		if (empty($user_id)) {
			return false;
		}
		// Get from memory cache
		if (isset($GLOBALS['user_live_stats'][$user_id])) {
			return $GLOBALS['user_live_stats'][$user_id];
		}
		$totals = array();
		$sql_array = $this->_sql_array;
		$_sql_keys = array_keys($sql_array);
		// Add item names (auto) to show in query result (useful for debug)
		foreach ((array)$sql_array as $_k => $_v) {
			$sql_array[$_k] = str_replace("SELECT ", "SELECT '".$_k."', ", str_replace("{_USER_ID_}", intval($user_id), $_v));
		}
		// Get and assign unified data
		if (!empty($sql_array)) {
			foreach ((array)db()->query_fetch_all("(".implode(") UNION ALL (", $sql_array).")") as $_counter => $_value) {
				$totals[$_sql_keys[$_counter]] = $_value[0];
			}
		}
		// Set memory cache value
		$GLOBALS['user_live_stats'][$user_id] = $totals;
		// Return result array
		return $totals;
	}

	//-----------------------------------------------------------------------------
	// Updated given user stats
	function _update_user_stats ($user_id = 0, $force_user_info = array()) {
		if (!$this->STATS_ENABLED || !$this->ENABLE_REFRESH_STATS) {
			return false;
		}
		// Try to get user id from session
		if (empty($user_id) && !empty(main()->USER_ID)) {
			$user_id = main()->USER_ID;
		}
		return _class("user_stats_refresh", "classes/user_stats/")->_update_user_stats($user_id, $force_user_info);
	}

	//-----------------------------------------------------------------------------
	// Get user stats for given users ids
	function _get_user_stats_for_ids ($user_ids = array()) {
		if (!$this->STATS_ENABLED) {
			return false;
		}
		if (empty($user_ids) || !is_array($user_ids)) {
			return false;
		}
		if (isset($user_ids[""])) {
			unset($user_ids[""]);
		}
		// Get info from db
		$Q = db()->query("SELECT * FROM ".db('user_stats')." WHERE user_id IN(".implode(",", $user_ids).")");
		while ($A = db()->fetch_assoc($Q)) {
			$users_stats[$A["user_id"]] = $A;
		}
		// Check if some user have no stats yet
		// Create if not
		foreach ((array)$user_ids as $cur_user_id) {
			// If info is found - check another one
			if (isset($users_stats[$cur_user_id])) {
				continue;
			}
			// Try to create one
			$try_user_stats = $this->_update_user_stats($cur_user_id);
			if (!empty($try_user_stats)) {
				$users_stats[$cur_user_id] = $try_user_stats;
			}
		}
		return $users_stats;
	}

	//-----------------------------------------------------------------------------
	// Refresh all stats (truncate table)
	function _cleanup_all_stats () {
		if (!$this->STATS_ENABLED || !$this->ENABLE_REFRESH_STATS) {
			return false;
		}
		db()->query("TRUNCATE TABLE ".db('user_stats')."");
	}

	//-----------------------------------------------------------------------------
	// Refresh all stats
	function _refresh_all_stats () {
		if (!$this->STATS_ENABLED || !$this->ENABLE_REFRESH_STATS) {
			return false;
		}
		return _class("user_stats_refresh", "classes/user_stats/")->_refresh_all_stats();
	}
}
