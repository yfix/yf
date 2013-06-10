<?php

/**
* Internal forum authentication module
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_forum_auth {

	/** @var int */
	public $MAX_LOGIN_LENGTH		= 64;
	/** @var int */
	public $MAX_PSWD_LENGTH		= 64;
	/** @var string */
	public $COOKIE_NAME			= "yf_forum_user_id";
	/** @var string */
	public $COOKIE_LIFE_TIME		= 2592000;	// 60*60*24*30 = 30 days
	/** @var string */
	public $VAR_NAME_USER_ID		= "forum_user_id";
	/** @var string */
	public $VAR_NAME_USER_NAME		= "forum_user_name";
	/** @var string */
	public $VAR_NAME_GROUP_ID		= "forum_group_id";
	/** @var string */
	public $VAR_NAME_VISIBILITY	= "forum_visibility";
	/** @var string */
	public $VAR_NAME_TIME_ZONE		= "forum_user_time_zone";
	/** @var int */
	public $LASTUP_TTL				= 900; // 15*60 = 15 minutes

	/**
	* Constructor
	*/
	function _init () {
		// Set cookie lofetime
		$this->COOKIE_LIFE_TIME = time() + 86400 * conf('cookie_life_time');
		// Types of login
		$this->_login_types = array(
			0	=> "Normal",
			1	=> "Invisible",
		);
		// Set session expiration time
		if (isset(module('forum')->SETTINGS["SESSION_EXPIRE_TIME"])) {
			$this->LASTUP_TTL = module('forum')->SETTINGS["SESSION_EXPIRE_TIME"];
		}
	}

	/**
	* Log in function
	*/
	function _login () {
		// Process POSTed data
		if (count($_POST) && !empty($_POST["login"]) && !empty($_POST["pswd"])) {
			// Prepare data for db query
			$posted_login		= _substr(trim($_POST["login"]), 0,	$this->MAX_LOGIN_LENGTH);
			$posted_password 	= _substr(trim($_POST["pswd"]), 0,	$this->MAX_PSWD_LENGTH);
			// Try to find forum user with given data
			$user_info = db()->query_fetch("SELECT * FROM `".db('forum_users')."` WHERE `name`='"._es($posted_login)."' AND `pswd`='".md5($posted_password)."' AND `status`='a' LIMIT 1");
			// If user found - store its info inside session
			if (!empty($user_info['id'])) {
				// Insert data into session
				$_SESSION[$this->VAR_NAME_USER_ID]		= $user_info["id"];
				$_SESSION[$this->VAR_NAME_USER_NAME]	= $user_info["name"];
				$_SESSION[$this->VAR_NAME_GROUP_ID]		= $user_info["group"];
				if (!empty($_POST['private_mode']) && in_array($_POST['private_mode'], $this->_login_types)) {
					$_SESSION[$this->VAR_NAME_VISIBLE]	= intval($_POST['private_mode']);
				}
				$_SESSION[$this->VAR_NAME_TIME_ZONE]	= $user_info["user_timezone"];
				// Remember user login inside cookie
				if (!empty($_POST['remember_me'])) {
					$cookie_text = base64_encode($user_info['id']."###".$user_info['name']."###".$user_info['pswd']."###".time()."###".intval($_SESSION[$this->VAR_NAME_VISIBLE]));
					setcookie($this->COOKIE_NAME, $cookie_text, $this->COOKIE_LIFE_TIME, "/");
				}
				// Return user back
				js_redirect(strlen($_POST["back_url"]) ? $_POST["back_url"] : "./?object=".FORUM_CLASS_NAME, false);
			// Show message that login failed
			} else {
				$body .= tpl()->parse(FORUM_CLASS_NAME."/login_failed");
			}
		// Show login form
		} else {
			$replace = array(
				"form_action"		=> "./?object=".FORUM_CLASS_NAME."&action=".$_GET["action"]._add_get(array("page")),
				"forgot_pswd_link"	=> "./?object=".FORUM_CLASS_NAME."&action=send_password"._add_get(array("page")),
				"back_url"			=> getenv("HTTP_REFERER"),
			);
			$body .= tpl()->parse(FORUM_CLASS_NAME."/login_form", $replace);
		}
		return module('forum')->_show_main_tpl($body);
	}

	/**
	* Log out function
	*/
	function _logout () {
		// Do empty session vars
		if (isset($_SESSION[$this->VAR_NAME_USER_ID]))	unset($_SESSION[$this->VAR_NAME_USER_ID]);
		if (isset($_SESSION[$this->VAR_NAME_USER_NAME]))unset($_SESSION[$this->VAR_NAME_USER_NAME]);
		if (isset($_SESSION[$this->VAR_NAME_GROUP_ID]))	unset($_SESSION[$this->VAR_NAME_GROUP_ID]);
		if (isset($_SESSION[$this->VAR_NAME_VISIBLE]))	unset($_SESSION[$this->VAR_NAME_VISIBLE]);
		if (isset($_SESSION[$this->VAR_NAME_TIME_ZONE]))unset($_SESSION[$this->VAR_NAME_TIME_ZONE]);
		if (isset($_SESSION["user_skin"]))				unset($_SESSION["user_skin"]);
		// Do empty cookie
		setcookie($this->COOKIE_NAME, '', time() - 31536000, "/");
		// Redirect user back
		js_redirect(getenv("HTTP_REFERER"), false);
	}

	/**
	* Verify session variables
	*/
	function _verify_session_vars () {
		if ($_GET["action"] == "logout") {
			return false;
		}
		// Process session vars
		if (!empty($_SESSION[$this->VAR_NAME_USER_ID]) && !empty($_SESSION[$this->VAR_NAME_GROUP_ID])) {
			$user_id		= $_SESSION[$this->VAR_NAME_USER_ID];
			$user_name		= $_SESSION[$this->VAR_NAME_USER_NAME];
			$group_id		= $_SESSION[$this->VAR_NAME_GROUP_ID];
			$visibility		= $_SESSION[$this->VAR_NAME_VISIBILITY];
			$user_time_zone	= $_SESSION[$this->VAR_NAME_TIME_ZONE];
			// Try to get more detailed user info from db
			if (!empty($user_id)) {
				$user_info	= db()->query_fetch("SELECT * FROM `".db('forum_users')."` WHERE `id`=".intval($user_id));
			}
		// Process cookie
		} elseif (!empty($_COOKIE[$this->COOKIE_NAME])) {
			$decrypted_text = base64_decode($_COOKIE[$this->COOKIE_NAME]);
			// Data checks
			if (!empty($decrypted_text)) {
				$decrypted_array = explode("###", $decrypted_text);
				// Check if data is correct (need to be array with 4 values)
				if (is_array($decrypted_array) && count($decrypted_array) == 5) {
					$cookie_user_id		= intval($decrypted_array[0]);
					$cookie_login		= $decrypted_array[1];
					$cookie_password	= $decrypted_array[2];
					$cookie_created		= intval($decrypted_array[3]);
					$cookie_visible		= intval($decrypted_array[4]);
				}
				// Prepare data for db query
				$cookie_login		= _substr(trim($cookie_login), 0,	$this->MAX_LOGIN_LENGTH);
				$cookie_password 	= _substr($cookie_password, 0, 32);
				// Try to find forum user with given data
				$user_info = db()->query_fetch("SELECT * FROM `".db('forum_users')."` WHERE `name`='"._es($cookie_login)."' AND `pswd`='"._es($cookie_password)."' AND `status`='a' LIMIT 1");
				// Check required values
				if (!empty($user_info['id']) && ($user_info['id'] == $cookie_user_id) && (time() < ($cookie_created + $this->COOKIE_LIFE_TIME))) {
					$user_id		= $user_info['id'];
					$group_id		= $user_info['group'];
					$user_name		= $user_info["name"];
					$visibility		= $cookie_visible;
					$user_time_zone	= $user_info["user_timezone"];
				}
			}
			// Do empty cookie if something gone wrong
			if ($_GET["action"] != "login" && empty($user_id)) {
				$user_id = null;
				setcookie($this->COOKIE_NAME, '', time() - 31536000, "/");
			}
		}
		// Set admin rights for the internal call
		if (FORUM_INTERNAL_CALL) {
			$user_id	= 1;
			$group_id	= 1;
		}
		// Check if user is ban list
		$IS_BANNED = $this->_check_user_ban();
		// Set current user group and related rights
		if (empty($user_id))	$group_id = 4;	// Guests standard group
		if ($IS_BANNED)			$group_id = 5;	// Banned users standard group
		// Get group info
		$cur_user_group = &module('forum')->_forum_groups[$group_id];
		$group_name = $cur_user_group["title"];
		// Special constants
		define('FORUM_IS_ADMIN',		intval($group_id == 1 || $cur_user_group["is_admin"]));
		define('FORUM_IS_MODERATOR',	intval($group_id == 2 || $cur_user_group["is_moderator"]));
		define('FORUM_IS_GUEST',		intval($group_id == 4));
		define('FORUM_IS_BANNED',		intval($group_id == 5));
		define('FORUM_IS_MEMBER',		intval($group_id == 3));
		// Define forum constants
		define('FORUM_USER_ID',			intval($user_id));
		define('FORUM_USER_NAME',		strlen($user_name) ? _prepare_html($user_name) : "");
		define('FORUM_USER_GROUP_ID',	intval($group_id));
		define('FORUM_USER_GROUP_NAME',	strlen($group_name) ? _prepare_html($group_name) : "");
		define('FORUM_USER_LAST_VISIT',	intval($user_info["user_lastvisit"]));
		define('FORUM_USER_VISIBLE',	intval($visibility));
		define('FORUM_USER_TIME_ZONE',	floatval($user_time_zone));
		// Merge group rights with default ones
		if (is_array(module('forum')->USER_RIGHTS)) {
			foreach ((array)module('forum')->USER_RIGHTS as $rights_key => $rights_value) {
				if (!isset($cur_user_group[$rights_key])) {
					continue;
				}
				module('forum')->USER_RIGHTS[$rights_key] = $cur_user_group[$rights_key];
			}
		}
		// Set current user settings (for logged in users only)
		if (FORUM_USER_ID && !FORUM_INTERNAL_CALL) module('forum')->USER_SETTINGS = array(
			"VIEW_SIG"			=> $user_info["view_sig"],
			"VIEW_IMAGES"		=> $user_info["view_images"],
			"VIEW_AVATARS"		=> $user_info["view_avatars"],
			"POSTS_PER_PAGE"	=> $user_info["posts_per_page"],
			"TOPICS_PER_PAGE"	=> $user_info["topics_per_page"],
			"PREFERRED_SKIN"	=> $user_info["skin"],
		);
		// Process online users
		if (module('forum')->SETTINGS["ONLINE_USERS_STATS"] && !FORUM_INTERNAL_CALL && !FORUM_IS_BANNED) {
			$this->_process_online();
		}
	}

	/**
	* Process online users info
	*/
	function _process_online () {
		$cur_time = time();
		// Cleanup expired users
		if (!main()->USE_TASK_MANAGER) {
			db()->query("DELETE FROM `".db('forum_sessions')."` WHERE `last_update` < ".(time() - $this->LASTUP_TTL));
		}
		// Try to recognize well-known spiders
		if (module('forum')->SETTINGS["RECOGNIZE_SPIDERS"]) {
			$spider_name = main()->call_class_method("spider_detect", "classes/", "detect");
			if (!empty($spider_name)) {
				return false;
			}
		}
		// Get topic and forum ID
		if ($_GET["action"] == "view_forum") {
			$in_forum = intval($_GET["id"]);
			$in_topic = 0;
		} elseif ($_GET["action"] == "view_topic") {
			$in_topic = intval($_GET["id"]);
			// Get topic info
			if (empty(module('forum')->_topic_info) && !empty($_GET["id"])) {
				module('forum')->_topic_info = db()->query_fetch("SELECT * FROM `".db('forum_topics')."` WHERE `id`=".intval($_GET["id"])." LIMIT 1");
			}
			$in_forum = intval(module('forum')->_topic_info["forum"]);
		}
		// Create compact user location string
		$location = $_GET["action"].";".$_GET["id"].";".$_GET["page"];
		// Visible or not for other members except admin
		$login_type = FORUM_USER_VISIBLE;
		// Current user session ID
		$_session_id = session_id();
		// Get all users online
		$Q = db()->query("SELECT * FROM `".db('forum_sessions')."`");
		while ($A = db()->fetch_assoc($Q)) {
			$online_array[$A["id"]] = $A;
		}
		module('forum')->online_array = $online_array;
		$online_users_array = &module('forum')->online_array;
		$cur_user_session_info = &$online_users_array[$_session_id];
		// Get user login date
		if (FORUM_USER_ID) {
			$login_date = !empty($cur_user_session_info["login_date"]) ? $cur_user_session_info["login_date"] : time();
		} else {
			$login_date = 0;
		}
		// Update current user session info
		$cur_user_session_info["login_date"]	= $login_date;
		$cur_user_session_info["last_update"]	= $cur_time;
		$cur_user_session_info["location"]		= $location;
		$cur_user_session_info["in_forum"]		= $in_forum;
		$cur_user_session_info["in_topic"]		= $in_topic;
		// Refresh current session reocrd
		db()->REPLACE("forum_sessions", array(
			"id"			=> _es($_session_id),
			"user_id"		=> intval(FORUM_USER_ID),
			"user_name"		=> _es(FORUM_USER_NAME),
			"user_group"	=> intval(FORUM_USER_GROUP_ID),
			"ip_address"	=> _es(common()->get_ip()),
			"user_agent"	=> _es($_SERVER["HTTP_USER_AGENT"]),
			"login_date"	=> intval($login_date),
			"last_update"	=> intval($cur_time),
			"login_type"	=> intval($login_type),
			"location"		=> _es($location),
			"in_forum"		=> intval($in_forum),
			"in_topic"		=> intval($in_topic),
		));
		// Update member's record
		if (FORUM_USER_ID) {
			db()->query("UPDATE `".db('forum_users')."` SET `user_lastvisit` = ".$cur_time." WHERE `id`=".intval(FORUM_USER_ID));
		}
	}
	
	/**
	* Check if user is in ban list
	*/
	function _check_user_ban () {
		// Check if user in ban list
		if (module('forum')->SETTINGS["USE_BAN_IP_FILTER"]) {
			if (db()->query_num_rows("SELECT `ip` FROM `".db('bannedip')."` WHERE `ip`='"._es(common()->get_ip())."'")) {
				module('forum')->BAN_REASONS[] = "Your IP address was found in ban list!";
			}
		}

//		module('forum')->BAN_REASONS[] = "Your IP address was found in ban list!";

		return !empty(module('forum')->BAN_REASONS) ? 1 : 0;
	}

	/**
	* Delete cookies set by our board
	*/
	function _del_cookies () {

// TODO

		setcookie($this->COOKIE_NAME, '', time() - 31536000, "/");
		js_redirect($_SERVER["HTTP_REFERER"], false);
	}
}
