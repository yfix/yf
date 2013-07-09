<?php

/**
* Chat handling class
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_chat {

	/** @var string Current chat version */
	public $VERSION			= "1.3.1b";
	/** @var int Refresh time (in seconds) */
	public $REFRESH_TIME		= 10;
	/** @var int Message size limit (in symbols) */
	public $MESSAGE_MAX_LENGTH = 512;
	/** @var int Old message time limit (in seconds) */
	public $MESSAGES_SHOW_TTL	= 1800; // 30 * 60 (half an hour)
	/** @var int Minimal time between posts (posts from IP address with less time period are denied) */
	public $ANTISPAM_TIME		= 1;
	/** @var int Time to delete not responding users (clean "offline" users) (in seconds) */
	public $OFFLINE_TTL		= 120;
	/** @var int Number of messages to show when first time after login loading data */
	public $FIRST_SHOW_MSGS	= 15;
	/** @var int Age of private messages to show when first time after login loading data */
	public $FIRST_PRIVATE_TTL	= 43200; // 12 hours (12 * 60 * 60);
	/** @var bool Use BB-Codes */
	public $BB_CODE			= true;
	/** @var bool Show smilies images or CSS based boxes (works only if BB_CODE is allowed) */
	public $SMILIES_IMAGES		= true;
	/** @var bool Use global user accounts or only special for the chat */
	public $USE_GLOBAL_USERS	= false;
	/** @var bool Ban list usage ("on"/"off") */
	public $USE_BAN_LIST		= true;
	/** @var int Max size for the user uploaded photo (in bytes) */
	public $PHOTO_MAX_SIZE		= 100000; // (~100 kb)
	/** @var int Max width of the user photo (in pixels) */
	public $PHOTO_MAX_WIDTH	= 800;
	/** @var int Max height of the user photo (in pixels) */
	public $PHOTO_MAX_HEIGHT	= 800;
	/** @var string Folder where user photos are storing */
	public $PHOTOS_FOLDER		= "uploads/chat_photos/";
	/** @var bool Use HTML Frameset */
	public $USE_HTML_FRAMESET	= false;
	/** @var string Web path to the chat main CSS file (Will be filled later automatically depending on settings) */
	public $CSS_SRC			= "";

	/**
	* Framework module constructor
	*/
	function _init () {
		main()->NO_GRAPHICS = true;
		// Special wrapper because __CLASS__ and $_GET["object"] could not be correct here
		define("CHAT_CLASS_NAME", "chat");
		// Chat sub modules folder
		define("CHAT_MODULES_DIR", "modules/".CHAT_CLASS_NAME."/");
		// Check if browser - Opera (need some special actions)
		define("IS_OPERA", (false !== strpos(strtolower($_SERVER["HTTP_USER_AGENT"]), 'opera')) ? 1 : 0);
		// Try to clean expired online users ("offline")
		$this->_clean_offline_users();
		// Define main session vars
		$this->_verify_session_vars();
		// Check user if exists in the ban list
		if ($this->USE_BAN_LIST) {
			// Stop execution here if current user exists is in  the ban list
			$Q = db()->query("SELECT * FROM ".db('chat_ban_list')." WHERE expiration=0 OR expiration>".time());
			while ($A = db()->fetch_assoc($Q)) {
				$this->_ban_list[$A["type"]][$A["value"]] = intval($A["expiration"]);
			}
			// IP address blocking
			if (is_array($this->_ban_list["ip"]) && @in_array(common()->get_ip(), array_keys($this->_ban_list["ip"]))) {
				if (CHAT_USER_ID) {
					$this->do_logout();
				}
				exit (tpl()->parse(CHAT_CLASS_NAME."/banned_ip", $replace));
			}
		}
		if (CHAT_USER_ID && in_array($_GET["action"], array(
			"show_user_info", 
			"show_users_list", 
			"edit_personal_info", 
			"save_personal_info",
			"edit_settings",
		))) {
			$ARRAYS_OBJ = main()->init_class("chat_arrays", CHAT_MODULES_DIR);
			$ARRAYS_OBJ->_define_arrays();
		}
	}

	/**
	* Catch _ANY_ call to the class methods (yf special hook)
	*/
	function _module_action_handler($called_action = "") {
		// Prepare path to CSS
		$this->CSS_SRC = WEB_PATH. tpl()->TPL_PATH. ($this->USE_GLOBAL_USERS ? "chat_global.css" : "chat.css");
		// Catch the output
		ob_start();
		$body = $this->$called_action();
		if ($this->USE_GLOBAL_USERS && in_array($_GET["action"], array("show", "login_form"))) {
			main()->NO_GRAPHICS = false;
			$body = ob_get_contents();
			ob_clean();
		}
		ob_end_flush();
		return $body;
	}

	/**
	* Show frame containing chat
	*/
	function show() {
		if (!CHAT_USER_ID) {
			return $this->login_form();
		}
		$CHAT_GET_OBJ = main()->init_class("chat_get", CHAT_MODULES_DIR);
		// Process template
		$replace = array(
			"css_src"				=> $this->CSS_SRC,
			"charset"				=> conf('charset'),
			"page_title"			=> t("yf_chat"),
			"refresh" 				=> CHAT_USER_REFRESH_TIME ? CHAT_USER_REFRESH_TIME : $this->REFRESH_TIME,
			"own_user_id"			=> intval(CHAT_USER_ID),
			"own_group_id"			=> intval(CHAT_USER_GROUP_ID),
			"own_login"				=> CHAT_USER_LOGIN,
			"own_color"				=> CHAT_USER_TEXT_COLOR,
			"user_room_id"			=> intval(CHAT_USER_ROOM_ID),
			"user_color_1"			=> CHAT_USER_BG_COLOR_1,
			"user_color_2"			=> CHAT_USER_BG_COLOR_2,
			"user_color_3"			=> CHAT_USER_BG_COLOR_3,
			"user_color_4"			=> CHAT_USER_BG_COLOR_4,
			"user_msg_show_time"	=> CHAT_USER_MSG_SHOW_TIME,
			"users_online_array"	=> $this->_get_users_online(),
			"messages"				=> !CHAT_USER_MSG_FILTER ? (is_object($CHAT_GET_OBJ) ? $CHAT_GET_OBJ->_get_messages(true, true) : "") : "",
			"private_msgs"			=> is_object($CHAT_GET_OBJ) ? $CHAT_GET_OBJ->_get_private(true, true) : "",
			"smilies_array"			=> $this->BB_CODE ? $this->_get_smilies() : "",
			"path_to_smilies"		=> WEB_PATH."uploads/forum/smilies/",
			"smilies_use_images"	=> intval($this->SMILIES_IMAGES),
			"empty_page"			=> /*IS_OPERA*/1 ? process_url("./?object=".CHAT_CLASS_NAME."&action=show_empty_page") : "",
			"rooms_page"			=> /*IS_OPERA*/1 ? process_url("./?object=".CHAT_CLASS_NAME."&action=show_empty_page") : "",
			"post_form_page"		=> process_url("./?object=".CHAT_CLASS_NAME."&action=show_post_form"),
			"user_info_url"			=> process_url("./?object=".CHAT_CLASS_NAME."&action=show_user_info&user_id=%%id%%"),
			"ignore_url"			=> process_url("./?object=".CHAT_CLASS_NAME."&action=set_ignore&user_id=%%id%%"),
			"edit_info_url"			=> process_url("./?object=".CHAT_CLASS_NAME."&action=edit_personal_info"),
			"edit_settings_url"		=> process_url("./?object=".CHAT_CLASS_NAME."&action=edit_settings"),
			"edit_ban_list_url"		=> CHAT_USER_GROUP_ID == 1 ? process_url("./?object=".CHAT_CLASS_NAME."&action=edit_ban_list") : "",
			"ban_user_url"			=> CHAT_USER_GROUP_ID == 1 ? process_url("./?object=".CHAT_CLASS_NAME."&action=do_ban_user&user_id=%%id%%") : "",
			"ban_message_url"		=> CHAT_USER_GROUP_ID == 1 ? process_url("./?object=".CHAT_CLASS_NAME."&action=do_ban_message&msg_id=%%id%%") : "",
			"xml_cmd_url"			=> process_url("./?object=".CHAT_CLASS_NAME."&action=xml_cmd_request"),
			"post_url"				=> process_url("./?object=".CHAT_CLASS_NAME."&action=do_post"),
			"logout_url"			=> process_url("./?object=".CHAT_CLASS_NAME."&action=do_logout"),
			"post_page_contents"	=> $this->_prepare_stpl_for_js(CHAT_CLASS_NAME."/post_form_new"),
			"empty_page_contents"	=> $this->_prepare_stpl_for_js(CHAT_CLASS_NAME."/empty_page"),
			"max_msg_length"		=> intval($this->MESSAGE_MAX_LENGTH),
			"use_global_users"		=> intval((bool)$this->USE_GLOBAL_USERS),
			"chat_total_users"		=> intval(count($GLOBALS['chat_online'])),
			"chat_version"			=> $this->VERSION,
		);
		// Switch between client types
		if ($this->USE_HTML_FRAMESET) {
			$stpl_name = "main_frameset";
		} else {
			$stpl_name = "main";
		}
		echo $body = tpl()->parse(CHAT_CLASS_NAME."/".$stpl_name, $replace);
	}

	/**
	* Prepare text for javascript (load from template)
	* 
	* @access	public
	* @param	string
	* @param	array
	* @return	string
	*/
	function _prepare_stpl_for_js($STPL_NAME = "", $replace = array()) {
		if (empty($STPL_NAME)) {
			return false;
		}
		$replace = array_merge((array)$replace, array(
			"css_src"	=> $this->CSS_SRC,
		));
		$body = tpl()->parse($STPL_NAME, $replace);
		$body = str_replace("script", "scr' + 'ipt", $body);
		$body = str_replace("'", "\'", $body);
		$body = str_replace(array("\r","\n", "\t", "{text}"), "", $body);
		return $body;
	}

	/**
	* Process XML commands request
	*/
	function xml_cmd_request() {
		$CHAT_GET_OBJ = main()->init_class("chat_get", CHAT_MODULES_DIR);
		if (is_object($CHAT_GET_OBJ)) {
			$CHAT_GET_OBJ->_show_commands();
		}
		// Do send data
		$this->_send_xml_data();
	}

	/**
	* Send given data as XML
	*/
	function _send_xml_data() {
//		if (empty($this->_CLIENT_CMDS)) return false;
		// Prepare content
		$replace = array(
			"cmds"		=> $this->_CLIENT_CMDS,
			"charset"	=> conf('charset') ? conf('charset') : "UTF-8",
		);
		$body = tpl()->parse(CHAT_CLASS_NAME."/xml_msg", $replace);
		// Prepare headers
		header("Connection: Keep-Alive", true);
		header("Keep-Alive: timeout=5, max=15", true);
		header("Content-Type: text/xml", true);
		header("Last-Modified: " . gmdate("D, d M Y 08:15:11") . " GMT", true);
		// Send content length header
		header("Content-Length: ".strlen($body), true);
		// Throw output
		echo $body;

// Do log message
//trigger_error('Get XML request', E_USER_WARNING);

		exit();
	}

	/**
	* Show commands
	*/
	function _add_client_cmd($cmd_name = "", $cmd_value = "") {
		if (empty($cmd_name)) {
			return false;
		}
		$this->_CLIENT_CMDS[] = array(
			"name"	=> $cmd_name,
			"value"	=> $cmd_value,
		);
	}

	/**
	* Post new message to the chat
	*/
	function do_post() {
		if (!CHAT_USER_ID || !count($_POST) || !strlen($_POST["msg"])) {
			return $this->_logout_redirect();
		}
		// Check user if exists in the ban list
		if ($this->USE_BAN_LIST) {
			if (is_array($this->_ban_list["user"]) && @in_array(CHAT_USER_LOGIN, array_keys($this->_ban_list["user"]))) {
				$expire = $this->_ban_list["user"][CHAT_USER_LOGIN];
//				die(tpl()->parse("chat/banned_user", array("expiration" => $expire ? date("H:i:s d/m/Y", $expire) : t("forever"))));
				$this->_add_client_cmd("do_alert_msg", "You are banned till ".($expire ? date("H:i:s d/m/Y", $expire) : t("forever")));
			}
		}
		// Prepare text
		$_POST["msg"] = substr(htmlspecialchars(stripslashes(trim($_POST["msg"]))), 0, $this->MESSAGE_MAX_LENGTH);
		if (!strlen($_POST["msg"])) {
			_re(t("no_message"));
			$this->_add_client_cmd("do_alert_msg", t("no_message"));
		}
		// Process private message
		if (strlen($_POST["private_to"]) && !common()->_error_exists()) {
			$SPAM_EXISTS = @$GLOBALS[db]->query_num_rows("SELECT id FROM ".db('chat_private')." WHERE add_date>".(time() - $this->ANTISPAM_TIME)." AND user_from=".intval(CHAT_USER_ID)." AND room_id=".intval(CHAT_USER_ROOM_ID));
			if ($SPAM_EXISTS) {
				_re(t("spam_exists"));
				$this->_add_client_cmd("do_alert_msg", t("spam"));
			}
			$_POST["private_to"] = stripslashes($_POST["private_to"]);
			// Try to select target user info
			$A2 = db()->query_fetch("SELECT * FROM ".db('chat_users')." WHERE login='"._es($_POST["private_to"])."'");
			if (!$A2["id"]) {
				_re(t("no_such_user"));
				$this->_add_client_cmd("do_alert_msg", t("no_such_user"));
			}
			db()->INSERT("chat_private", array(
				"room_id"	=> intval(CHAT_USER_ROOM_ID),
				"user_from"	=> intval(CHAT_USER_ID),
				"user_to"	=> intval($A2["id"]),
				"login_from"=> _es(CHAT_USER_LOGIN),
				"login_to"	=> _es($_POST["private_to"]),
				"text"		=> _es($_POST["msg"]),
				"add_date"	=> time(),
				"poster_ip"	=> common()->get_ip(),
				"text_color"=> _es(CHAT_USER_TEXT_COLOR),
			));
		// Process common message
		} else {
			$SPAM_EXISTS = db()->query_num_rows("SELECT id FROM ".db('chat_messages')." WHERE add_date>".(time() - $this->ANTISPAM_TIME)." AND user_id=".intval(CHAT_USER_ID)." AND room_id=".intval(CHAT_USER_ROOM_ID));
			if ($SPAM_EXISTS) {
				$this->_add_client_cmd("do_alert_msg", t("spam"));
				_re(t("spam_exists"));
			}
			db()->INSERT("chat_messages", array(
				"user_id"	=> intval(CHAT_USER_ID),
				"room_id"	=> intval(CHAT_USER_ROOM_ID),
				"login"		=> _es(CHAT_USER_LOGIN),
				"text"		=> _es($_POST["msg"]),
				"add_date"	=> time(),
				"poster_ip"	=> common()->get_ip(),
				"text_color"=> _es(CHAT_USER_TEXT_COLOR),
			));
		}
		// Do send data
		$this->_send_xml_data();
	}

	/**
	* Show page contents
	*/
	function show_empty_page($text = "", $internal_call = false) {
		if (!CHAT_USER_ID && !strlen($text) && !$internal_call) {
			return $this->_logout_redirect();
		}
		$replace = array(
			"css_src"			=> $this->CSS_SRC,
			"charset"			=> conf('charset'),
			"text"				=> $text,
			"use_global_users"	=> intval((bool)$this->USE_GLOBAL_USERS),
		);
		echo tpl()->parse("chat/empty_page", $replace);
	}

	/**
	* Show information about the user with given ID
	*/
	function show_user_info() {
		$OBJ = main()->init_class("chat_user_info", CHAT_MODULES_DIR);
		$this->show_empty_page(is_object($OBJ) ? $OBJ->_view() : "");
	}

	/**
	* Form to edit user info
	*/
	function edit_personal_info() {
		if (!CHAT_USER_ID) {
			return $this->_logout_redirect();
		}
		$OBJ = main()->init_class("chat_user_info", CHAT_MODULES_DIR);
		$this->show_empty_page(is_object($OBJ) ? $OBJ->_edit() : "");
	}

	/**
	* Save user info
	*/
	function save_personal_info() {
		if (!CHAT_USER_ID) {
			return $this->_logout_redirect();
		}
		$OBJ = main()->init_class("chat_user_info", CHAT_MODULES_DIR);
		$this->show_empty_page(is_object($OBJ) ? $OBJ->_save() : "");
	}

	/**
	* Form to edit user settings
	*/
	function edit_settings() {
		if (!CHAT_USER_ID) {
			return $this->_logout_redirect();
		}
		$OBJ = main()->init_class("chat_settings", CHAT_MODULES_DIR);
		$this->show_empty_page(is_object($OBJ) ? $OBJ->_edit() : "");
	}

	/**
	* Save user settings
	*/
	function save_settings() {
		if (!CHAT_USER_ID || !count($_POST)) {
			return $this->_logout_redirect();
		}
		$OBJ = main()->init_class("chat_settings", CHAT_MODULES_DIR);
		$this->show_empty_page(is_object($OBJ) ? $OBJ->_save() : "");
	}

	/**
	* Set new ignore status for the selected user
	*/
	function set_ignore() {
		if (!CHAT_USER_ID) {
			return $this->_logout_redirect();
		}
		$OBJ = main()->init_class("chat_settings", CHAT_MODULES_DIR);
		if (is_object($OBJ)) $OBJ->_set_ignore();
	}
	
	/**
	* Show login form
	*/
	function login_form() {
		if (CHAT_USER_ID) {
			return js_redirect("./?object=".CHAT_CLASS_NAME);
		}
		$OBJ = main()->init_class("chat_login", CHAT_MODULES_DIR);
		if (is_object($OBJ)) echo $OBJ->_form();
	}
	
	/**
	* Process login
	*/
	function do_login() {
		if (CHAT_USER_ID) {
			return js_redirect("./?object=".CHAT_CLASS_NAME);
		}
		$OBJ = main()->init_class("chat_login", CHAT_MODULES_DIR);
		$this->show_empty_page(is_object($OBJ) ? $OBJ->_do() : "", 1);
	}

	/**
	* Process logout
	*/
	function do_logout() {
		if (CHAT_USER_ID) {
			unset($GLOBALS['chat_online'][CHAT_USER_ID]);
			$this->_set_system_message (CHAT_USER_ROOM_ID, CHAT_USER_GENDER, CHAT_USER_LOGIN, CHAT_USER_TEXT_COLOR, 1);
			db()->query("DELETE FROM ".db('chat_online')." WHERE user_id=".intval(CHAT_USER_ID));
			$A = $GLOBALS['chat_online'][CHAT_USER_ID];
			$this->_log_change_online_status($A, $A["add_date"]);
		}
		// Destroy chat session vars
		foreach ((array)$_SESSION as $k => $v) {
			if (substr($k, 0, 5) == "chat_") {
				unset($_SESSION[$k]);
			}
		}
		return js_redirect("./?object=".CHAT_CLASS_NAME);
	}
	
	/**
	* Show registration form
	*/
	function register_form() {
		if (CHAT_USER_ID) {
			return js_redirect("./?object=".CHAT_CLASS_NAME);
		}
		$OBJ = main()->init_class("chat_register", CHAT_MODULES_DIR);
		if (is_object($OBJ)) {
			echo $OBJ->_form();
		}
	}
	
	/**
	* Make user registration
	*/
	function do_register() {
		$OBJ = main()->init_class("chat_register", CHAT_MODULES_DIR);
		$this->show_empty_page(is_object($OBJ) ? $OBJ->_do() : "");
	}

	/**
	* Verify session variables
	*/
	function _verify_session_vars () {
		// Use global user names (for whole system not only chat)
		if ($this->USE_GLOBAL_USERS && strlen($_SESSION["user_id"]) && strlen($_SESSION["user_group"])) {
// TODO: add code here
			if (!isset($GLOBALS['chat_online'])) {
				$GLOBALS['chat_online'] = array();
				$Q5 = db()->query("SELECT * FROM ".db('chat_online')."");
				while ($A5 = db()->fetch_assoc($Q5)) {
					$GLOBALS['chat_online'][$A5["user_id"]] = $A5;
				}
			}
			foreach ((array)$GLOBALS['chat_online'] as $A5) {
				if (($A5['user_id'] == intval($_SESSION["chat_user_id"])) && ($A5["session_id"] == session_id())) {
					$A = &$A5;
					break;
				}
			}
		// Use only chat local users (do not use global user accounts)
		} elseif (strlen($_SESSION["chat_user_id"])) {
			if (!isset($GLOBALS['chat_online'])) {
				$GLOBALS['chat_online'] = array();
				$Q5 = db()->query("SELECT * FROM ".db('chat_online')."");
				while ($A5 = db()->fetch_assoc($Q5)) {
					$GLOBALS['chat_online'][$A5["user_id"]] = $A5;
				}
			}
			foreach ((array)$GLOBALS['chat_online'] as $A5) {
				if (($A5['user_id'] == intval($_SESSION["chat_user_id"])) && ($A5["session_id"] == session_id())) {
					$A = &$A5;
					break;
				}
			}
		}
		// Database unique user ID (int)
		define('CHAT_USER_ID',				intval($A["user_id"]));
		// User login (string)
		define('CHAT_USER_LOGIN',			$A["login"]);
		// Color of the user messages (string)
		define('CHAT_USER_TEXT_COLOR',		$A["text_color"]);
		// bg color of my messages (string)
		define('CHAT_USER_BG_COLOR_1', 		$A["chat_color_1"]);
		// bg color of messages for me (string)
		define('CHAT_USER_BG_COLOR_2', 		$A["chat_color_2"]);
		// bg color of my private (string)
		define('CHAT_USER_BG_COLOR_3', 		$A["chat_color_3"]);
		// bg color of private for me (string)
		define('CHAT_USER_BG_COLOR_4', 		$A["chat_color_4"]);
		// 0 - without seconds, 1 - with seconds, 2 - show no time
		define('CHAT_USER_MSG_SHOW_TIME',	$A["chat_show_time"]);
		// 0 - show all (default), 1 - only private
		define('CHAT_USER_MSG_FILTER',		$A["chat_msg_filter"]);
		// Custom refresh time for the current user (int)
		define('CHAT_USER_REFRESH_TIME',	intval($A["chat_refresh"]));
		// Gender of the current user ("m" or "f")
		define('CHAT_USER_GENDER',			$A["gender"] == "f" ? "f" : "m");
		// Logged in time (current session) (int)
		define('CHAT_USER_TIME_LOGIN',		intval($A["add_date"]));
		// Last visit for the commands to the server (int)
		define('CHAT_USER_LAST_VISIT',		intval($A["last_visit"]));
		// Room ID where user is logged in
		define('CHAT_USER_ROOM_ID',			intval($A["room_id"]));
		// Group ID (int)
		define('CHAT_USER_GROUP_ID', 		intval($A["group_id"]));
		// Group name (string)
		define('CHAT_USER_GROUP_NAME',		$A["group_id"] == 1 ? t("moderator") : t("member"));
		// Client type
		define('CHAT_USER_CLIENT_TYPE',		"");
	}

	/**
	* Try to clean users that do not respond for a while (thinking they are "offline")
	*/
	function _clean_offline_users () {
		if (!isset($GLOBALS['chat_online'])) {
			$GLOBALS['chat_online'] = array();
			$Q5 = db()->query("SELECT * FROM ".db('chat_online')."");
			while ($A5 = db()->fetch_assoc($Q5)) $GLOBALS['chat_online'][$A5["user_id"]] = $A5;
		}
		if (is_array($GLOBALS['chat_online'])) foreach ((array)$GLOBALS['chat_online'] as $A5) {
			if ($A5['last_visit'] < (time() - $this->OFFLINE_TTL)) $delete_ids[$A5["user_id"]] = $A5;
		}
		if (is_array($delete_ids) && count($delete_ids)) {
			foreach ((array)$delete_ids as $A)	{
				unset($GLOBALS['chat_online'][$A["user_id"]]);
				$this->_set_system_message ($A["room_id"], $A["gender"], $A["login"], $A["text_color"], 2);
				$this->_log_change_online_status($A, $A["add_date"]);
			}
			db()->query("DELETE FROM ".db('chat_online')." WHERE user_id IN(".implode(",", array_keys($delete_ids)).")");
		}
	}

	/**
	* Users online
	*/
	function _get_users_online($first_time = true) {
		$users = array();
		// Process online users
		foreach ((array)$GLOBALS['chat_online'] as $A) {
			if ($A['room_id'] != intval(CHAT_USER_ROOM_ID)) {
				continue;
			}
			$users[] .= "{".
				"'user_id'		:".intval($A["user_id"]).",".
				"'gender'		:\""._prepare_html($A["gender"])."\",".
				"'text_color'	:\""._prepare_html($A["text_color"])."\",".
				"'ignore_list'	:".intval($this->ignore_list[$A["user_id"]]).",".
				"'user_login'	:\""._prepare_html($A["login"])."\",".
				"'info_status'	:".intval($A["info_status"]).",".
				"'group_id'		:".intval($A["group_id"] == 1 ? $A["group_id"] : "").
				"}";
		}
		return str_replace(array("\r","\n","\t"), "", implode(",",$users));
	}

	/**
	* Get array of smilies
	*/
	function _get_smilies() {
		if (!CHAT_USER_ID) {
			return false;
		}
		if (!isset($this->_smilies_data)) {
//			$this->_smilies_data = main()->get_data("smilies");
			$this->_smilies_data = main()->get_data("chat_smilies");
		}
		// Prepare array for JS
		$output = array();
		foreach ((array)$this->_smilies_data as $A) {
			$output[] = "[".$A["id"].",\"".$A["code"]."\",\"".$A["url"]."\",\"".t($A["emoticon"])."\"]";
		}
		return implode(",", $output);
	}

	/**
	* Create system message telling that some user changes his status (online or offline)
	*/
	function _set_system_message ($room_id = 0, $user_gender = "m", $user_login = "", $user_color = "", $status = 0) {
		// Statuses: 0 - online, 1 - offline (logout manually), 2 - offline (auto-logout)
		$room_id = intval($room_id);
		$online_msg		= $user_gender == "m" ? t("we_have_new_male_user_online") : t("we_have_new_female_user_online");
		$offline_msg	= $status == 1 ? ($user_gender == "m" ? t("log_out_male_user") : t("log_out_female_user")) : ($user_gender == "m" ? t("somewhere_gone_male_user") : t("somewhere_gone_female_user"));
		if ($room_id && strlen($user_login) && strlen($user_color) && strlen($status)) {
			$message = "[i]".(in_array($status, array(1,2)) ? $offline_msg : $online_msg)." [b]".$user_login."[/b] (".t("chat_online_users")." ".count($GLOBALS['chat_online'])." ".t("users").")[/i]";
			db()->INSERT("chat_messages", array(
				"room_id"		=> $room_id,
				"text"			=> _es($message),
				"add_date"		=> time(),
				"text_color"	=> _es($user_color),
			));
		}
	}

	/**
	* Common used function for auto-logout user
	*/
	function _logout_redirect () {
		$body = "parent.window.location = \"".WEB_PATH. "?object=".CHAT_CLASS_NAME."&action=do_logout\";";
		echo "<script>try {".$body."} catch (x) {}</script>";
	}

	/**
	* Store log info about user login or logout (if $login_date is specified - then it is logout)
	*/
	function _log_change_online_status ($A, $login_date = 0) {
		$OBJ = main()->init_class("chat_utils", CHAT_MODULES_DIR);
		if (is_object($OBJ)) $OBJ->_log_change_online_status ($A, $login_date);
	}

	//-----------------------------------------------------------------------------
	// MODERATORS METHODS HERE
	//-----------------------------------------------------------------------------

	/**
	* Edit ban list control (ONLY FOR MODERATORS!)
	*/
	function edit_ban_list() {
		if (!CHAT_USER_ID || CHAT_USER_GROUP_ID != 1) {
			return $this->_logout_redirect();
		}
		$MOD_OBJ = main()->init_class("chat_moderator", CHAT_MODULES_DIR);
		$this->show_empty_page(is_object($MOD_OBJ) ? $MOD_OBJ->_edit_ban_list() : "");
	}
	
	/**
	* Delete ban list item (ONLY FOR MODERATORS!)
	*/
	function delete_ban_item() {
		if (!CHAT_USER_ID || CHAT_USER_GROUP_ID != 1) {
			return $this->_logout_redirect();
		}
		$MOD_OBJ = main()->init_class("chat_moderator", CHAT_MODULES_DIR);
		if (is_object($MOD_OBJ)) $MOD_OBJ->_delete_ban_item();
	}
	
	/**
	* Add item to the ban list (ONLY FOR MODERATORS!)
	*/
	function do_ban_user() {
		if (!CHAT_USER_ID || CHAT_USER_GROUP_ID != 1) {
			return $this->_logout_redirect();
		}
		$MOD_OBJ = main()->init_class("chat_moderator", CHAT_MODULES_DIR);
		if (is_object($MOD_OBJ)) $MOD_OBJ->_do_ban_user();
	}
	
	/**
	* Delete selected message (ONLY FOR MODERATORS!)
	*/
	function do_ban_message() {
		if (!CHAT_USER_ID || CHAT_USER_GROUP_ID != 1) {
			return $this->_logout_redirect();
		}
		$MOD_OBJ = main()->init_class("chat_moderator", CHAT_MODULES_DIR);
		if (is_object($MOD_OBJ)) $MOD_OBJ->_do_ban_message();
	}

	/**
	* Try to load forum sub_module
	*/
	function _load_sub_module ($module_name = "") {
		$OBJ = main()->init_class($module_name, CHAT_MODULES_DIR);
		if (!is_object($OBJ)) {
			trigger_error("CHAT: Cant load sub_module \"".$module_name."\"", E_USER_WARNING);
			return false;
		}
		return $OBJ;
	}
}
