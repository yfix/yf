<?php

/**
* Read messages handler
* 
* @package		YF
* @author		Yuri Vysotskiy <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_forum_read {

	/**
	* Init read messages info
	*
	* @access	private
	* @return	void
	*/
	function _init_read_messages() {
		// We can use tracking of read messages only for members
		if (module('forum')->SETTINGS["USE_READ_MESSAGES"] && !FORUM_USER_ID) {
			module('forum')->SETTINGS["USE_READ_MESSAGES"] = 0;
		}
		// We can use tracking of read messages only when user have cookies enabled
		if (module('forum')->SETTINGS["USE_READ_MESSAGES"] && module('forum')->SETTINGS["READ_MSGS_DRIVER"] == "cookies" && conf('COOKIES_ENABLED') != 1) {
			module('forum')->SETTINGS["USE_READ_MESSAGES"] = 0;
		}
		// Get current array of read messages ids
		if (!module('forum')->SETTINGS["USE_READ_MESSAGES"]) {
			// Cleanup cookie
			if (isset($_COOKIE[module('forum')->SETTINGS["_READ_MSGS_COOKIE"]])) {
				setcookie(module('forum')->SETTINGS["_READ_MSGS_COOKIE"], "", time() + module('forum')->SETTINGS["_READ_MSGS_TTL"], "/");
			}
			return false;
		}
		$cutoff_time = time() - module('forum')->SETTINGS["_READ_MSGS_TTL"];
		/* example for driver record:
			$GLOBALS['forum_read_array'] = array(
				456	=> array( // topic id
					"time"	=> 1234567891, // last read time
					"fid"	=> 1, // forum_id, denormalization but allows fast check for forums
				)
			);
		*/
		$GLOBALS['forum_read_array']	= array();
		$_read_tmp			= array();
		// Cookies-based tracker driver
		if (module('forum')->SETTINGS["READ_MSGS_DRIVER"] == "cookie" && isset($_COOKIE[module('forum')->SETTINGS["_READ_MSGS_COOKIE"]])) {
			$_read_tmp = unserialize($_COOKIE[module('forum')->SETTINGS["_READ_MSGS_COOKIE"]]);
		// Db-based tracker driver
		} elseif (module('forum')->SETTINGS["READ_MSGS_DRIVER"] == "db") {
			$_from_db = db()->query_fetch("SELECT `data` FROM `".db('forum_read_msgs')."` WHERE `user_id`=".intval(FORUM_USER_ID));
			// Check if needed to create db record instead of update
			if (empty($_from_db)) {
				$GLOBALS['forum_need_to_create_read_record'] = true;
			} else {
				$_read_tmp = unserialize($_from_db["data"]);
			}
			unset($_from_db); // Free some memory
		}
		// Cleanup of old entries
		foreach ((array)$_read_tmp as $_topic_id => $_info) {
			if (empty($_info) || $_info["time"] < $cutoff_time) {
				continue;
			}
			$GLOBALS['forum_read_array'][$_topic_id] = $_info;
		}
		unset($_read_tmp); // Free some memory
		// Only for forums display (forum home and view_forum)
		if (in_array($_GET["action"], array("show","mark_read","unread"))) {
			// Get last posts for last $cutoff_time
			$Q = db()->query(
				"SELECT `id`,`forum`,`last_post_date` 
				FROM `".db('forum_topics')."` 
				WHERE `last_post_date` > ".intval($cutoff_time)." 
				LIMIT 200"
			);
			while ($A = db()->fetch_assoc($Q)) {
				$GLOBALS['forum_latest_topics'][$A["forum"]][$A["id"]] = $A["last_post_date"];
			}
		}
	}

	/**
	* Mark read messages
	*
	* @access	private
	* @return	void
	*/
	function _mark_read () {
		$_GET["id"] = intval($_GET["id"]);
		if (FORUM_USER_ID && module('forum')->SETTINGS["USE_READ_MESSAGES"] && $_GET["id"]) {
			$this->_set_forum_read($_GET["id"]);
		}
		return js_redirect($_SERVER["HTTP_REFERER"], false);
	}

	/**
	* Get topic is read status (if needed)
	*
	* @access	private
	* @return	void
	*/
	function _get_topic_read ($topic_info = array()) {
		if (!module('forum')->SETTINGS["USE_READ_MESSAGES"] || empty($topic_info)) {
			return false;
		}
		$cutoff_time = time() - module('forum')->SETTINGS["_READ_MSGS_TTL"];
		// Check if topic is under "cutoff"
		if ($topic_info["last_post_date"] < $cutoff_time) {
			return true;
		}
		if (isset($GLOBALS['forum_read_array'][$topic_info["id"]]) && $GLOBALS['forum_read_array'][$topic_info["id"]]["time"] >= $topic_info["last_post_date"]) {
			return true;
		}
		// Topic is "unread"
		return false;
	}

	/**
	* Get forum is read status (if needed)
	*
	* @access	private
	* @return	void
	*/
	function _get_forum_read ($forum_info = array()) {
		if (!module('forum')->SETTINGS["USE_READ_MESSAGES"] || empty($forum_info)) {
			return true;
		}
		// For empty forums return "read" status
		if (empty($forum_info["num_topics"])) {
			return true;
		}
		// Check if forum has updated topics in last "cutoff_time"
		if (empty($GLOBALS['forum_latest_topics']) || empty($GLOBALS['forum_latest_topics'][$forum_info["id"]])) {
			return true;
		}
		// Prepare read array from current forum
		$unread_topics = array();
		foreach ((array)$GLOBALS['forum_latest_topics'][$forum_info["id"]] as $_topic_id => $_latest_time) {
			if (isset($GLOBALS['forum_read_array'][$_topic_id]) && $GLOBALS['forum_read_array'][$_topic_id]["time"] >= $_latest_time) {
				continue;
			}
			$unread_topics[$_topic_id] = array(
				"fid"	=> $forum_info["id"],
				"time"	=> $_latest_time
			);
		}
		// All topics are read
		if (empty($unread_topics)) {
			return true;
		}
		// Forum is "unread" or have "unread" topics or posts
		return false;
	}

	/**
	* Set topic read (if needed)
	*
	* @access	private
	* @return	void
	*/
	function _set_topic_read ($topic_info = array()) {
		if (!module('forum')->SETTINGS["USE_READ_MESSAGES"] || empty($topic_info)) {
			return false;
		}
		if ($topic_info["last_post_date"] > (time() - module('forum')->SETTINGS["_READ_MSGS_TTL"])) {
			$GLOBALS['forum_read_array'][$topic_info["id"]] = array(
				"time"	=> time(),
				"fid"	=> intval($topic_info["forum"]),
			);
		}
		$this->_save_read_data();
	}

	/**
	* Mark forum "read" status
	*
	* @access	private
	* @return	void
	*/
	function _set_forum_read ($forum_id = 0) {
		$forum_info = module('forum')->_forums_array[$forum_id];
		if (!module('forum')->SETTINGS["USE_READ_MESSAGES"] || empty($forum_info)) {
			return true;
		}
		// For empty forums do nothing
		if (empty($forum_info["num_topics"])) {
			return true;
		}
		// Check if forum has updated topics in last "cutoff_time"
		if (empty($GLOBALS['forum_latest_topics']) || empty($GLOBALS['forum_latest_topics'][$forum_id])) {
			return true;
		}
		// Prepare read array from current forum
		$unread_topics = array();
		foreach ((array)$GLOBALS['forum_latest_topics'][$forum_id] as $_topic_id => $_latest_time) {
			if ($_latest_time > (time() - module('forum')->SETTINGS["_READ_MSGS_TTL"])) {
				$GLOBALS['forum_read_array'][$_topic_id] = array(
					"time"	=> time(),
					"fid"	=> intval($forum_id),
				);
			}
		}
		$this->_save_read_data();
	}

	/**
	* Save read data
	*
	* @access	private
	* @return	void
	*/
	function _save_read_data () {
		// Cookies-based tracker driver
		if (module('forum')->SETTINGS["READ_MSGS_DRIVER"] == "cookie") {
			setcookie(module('forum')->SETTINGS["_READ_MSGS_COOKIE"], serialize($GLOBALS['forum_read_array']), time() + module('forum')->SETTINGS["_READ_MSGS_TTL"], "/");
		// Db-based tracker driver
		} elseif (module('forum')->SETTINGS["READ_MSGS_DRIVER"] == "db") {
			if ($GLOBALS['forum_need_to_create_read_record']) {
				db()->INSERT("forum_read_msgs", array(
					"user_id"	=> intval(FORUM_USER_ID),
					"data"		=> _es(serialize($GLOBALS['forum_read_array'])),
				));
			} else {
				db()->UPDATE("forum_read_msgs", array(
					"data"		=> _es(serialize($GLOBALS['forum_read_array'])),
				), "`user_id` = ".intval(FORUM_USER_ID));
			}
		}
	}
}
