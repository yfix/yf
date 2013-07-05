<?php

/**
* Comments management
*/
class yf_comments_manage {

	/**
	* Framework constructor
	*/
	function _init () {
		// Reference to parent object
		$this->COMMENTS_OBJ		= module(COMMENTS_CLASS_NAME);
	}

	/**
	* Form to add comments
	*/
	function _add ($params = array()) {
		if (empty($this->COMMENTS_OBJ->USER_ID) && MAIN_TYPE_USER && !$params["allow_guests_posts"]) {
			return "";
		}
		$_GET["id"] = intval($_GET["id"]);
		// Process params
		$OBJECT_NAME	= !empty($params["object_name"]) ? $params["object_name"] : $_GET["object"];
		$OBJECT_ID		= !empty($params["object_id"]) ? intval($params["object_id"]) : intval($_GET["id"]);
		$FORM_ACTION	= !empty($params["add_form_action"]) ? $params["add_form_action"] : "./?object=".$_GET["object"]."&action=add_comment&id=".$OBJECT_ID;
		$STPL_NAME_ADD	= !empty($params["stpl_add"]) ? $params["stpl_add"] : "comments/add_form";
		$RETURN_PATH	= $_SERVER["HTTP_REFERER"];
		if (!empty($params["return_path"])) {
			$RETURN_PATH = process_url($params["return_path"]);
		} elseif (!empty($params["return_action"])) {
			$RETURN_PATH = process_url("./?object=".$_GET["object"]."&action=".$params["return_action"]."&id=".$OBJECT_ID);
		}
		// Check required params
		if (empty($OBJECT_NAME) || empty($OBJECT_ID)) {
			return "";
		}
		// Check if comment is allowed
		$COMMENT_IS_ALLOWED = true;
		if (is_object(module($_GET["object"]))) {
			$COMMENT_IS_ALLOWED = main()->_execute($_GET["object"], $this->COMMENTS_OBJ->_add_allowed_method, $params);
		}
		// Display errors
		if (!$COMMENT_IS_ALLOWED && common()->_error_exists()) {
			return _e();
		}
		// Stop is we do not allow to post new comments here
		if (!$COMMENT_IS_ALLOWED) {
			return false;
		}
		// Do add comment
		if (count($_POST) > 0 && !isset($_POST["_not_for_comments"])) {
			// Fix for tree mode second form
			if (isset($_POST["text2"]) && !isset($_POST["text"])) {
				$_POST["text"] = $_POST["text2"];
				unset($_POST["text2"]);
			}
			// Check for errors
			if (!common()->_error_exists()) {
				$_POST["text"]	= substr($_POST["text"], 0, $this->COMMENTS_OBJ->MAX_POST_TEXT_LENGTH);
				if (empty($_POST["text"])) {
					_re(t("Comment text required"));
				}
			}
			// Do check captcha (if needed)
			if (module($_GET["object"])->USE_CAPTCHA) {
				// Tree mode
				if ($this->COMMENTS_OBJ->USE_TREE_MODE && isset($_POST['parent_id'])) {
					if (empty($this->COMMENTS_OBJ->USER_ID)) {
						main()->_execute($_GET["object"], "_captcha_check");
					} else {
// TODO: more checks for the members in tree mode
					}
				} else {
					main()->_execute($_GET["object"], "_captcha_check");
				}
			}
			// Check for errors
			if (!common()->_error_exists() && MAIN_TYPE_USER) {
				$info_for_check = array(
					"comment_text"	=> $_POST["text"],
					"user_id"		=> $this->COMMENTS_OBJ->USER_ID,
				);
				$USER_BANNED = _check_user_ban($info_for_check, $this->COMMENTS_OBJ->_user_info);
				if ($USER_BANNED) {
					$this->COMMENTS_OBJ->_user_info = user($this->COMMENTS_OBJ->USER_ID);
				}
				// Stop here if user is banned
				if ($this->COMMENTS_OBJ->_user_info["ban_comments"]) {
					return _e(
						"Sorry, you are not allowed to post comments!\r\nPerhaps, you broke some of our rules and moderator has banned you from using this feature. Please, enjoy our site in some other way!"
						."For more details <a href=\"./?object=faq&action=view&id=16\">click here</a>"
					);
				}
			}
			// Anti-flood check
			if (!common()->_error_exists() && $this->COMMENTS_OBJ->ANTI_FLOOD_TIME && MAIN_TYPE_USER) {
				$FLOOD_DETECTED = db()->query_fetch(
					"SELECT `id`,`add_date` FROM `".db('comments')."` 
					WHERE ".($this->COMMENTS_OBJ->USER_ID ? "`user_id`=".intval($this->COMMENTS_OBJ->USER_ID) : "`ip`='"._es(common()->get_ip())."'")
						." AND `add_date` > ".(time() - $this->COMMENTS_OBJ->ANTI_FLOOD_TIME)." 
					ORDER BY `add_date` DESC LIMIT 1"
				);
				if (!empty($FLOOD_DETECTED)) {
					_re("Please wait ".intval($this->COMMENTS_OBJ->ANTI_FLOOD_TIME - (time() - $FLOOD_DETECTED["add_date"]))." seconds before post comment.");
				}
			}
			
			// Anti-spam check
			if (!common()->_error_exists()){
				if($this->COMMENTS_OBJ->ANTI_SPAM_DETECT){
					$this->_spam_check($_POST["text"]);
				}
			}
			
			// Check valid email
			if(!empty($_POST["user_email"])){
				if (!preg_match('#^[0-9a-z]([-_.]?[0-9a-z])*@[0-9a-z]([-.]?[0-9a-z])*\.[a-z]{2,3}$#i', $_POST["user_email"])) {
					_re(t("Invalid e-mail, please check your spelling"));
				}
			}
			
			// Check for errors
			if (!common()->_error_exists()) {
				// Check text fields
				if ($this->COMMENTS_OBJ->AUTO_FILTER_INPUT_TEXT) {
					$_POST["text"] = _filter_text($_POST["text"]);
				}
				// Do close BB Codes (if needed)
				if ($this->COMMENTS_OBJ->USE_BB_CODES) {
					$BB_CODES_OBJ = main()->init_class("bb_codes", "classes/");
					if (is_object($BB_CODES_OBJ)) {
						$_POST["text"] = $BB_CODES_OBJ->_force_close_bb_codes($_POST["text"]);
					}
				}
				// Do insert record
				db()->INSERT("comments", array(
					"object_name"		=> _es($OBJECT_NAME),
					"object_id"			=> intval($OBJECT_ID),
					"parent_id"			=> intval(isset($_POST['parent_id'])?$_POST['parent_id']:0),
					"user_id"			=> intval($this->COMMENTS_OBJ->USER_ID),
					"user_name"			=> !$this->COMMENTS_OBJ->USER_ID ? _es($_POST["user_name"]) : "",
					"user_email"		=> !$this->COMMENTS_OBJ->USER_ID ? _es($_POST["user_email"]): "",
					"text" 				=> _es($_POST["text"]),
					"add_date"			=> time(),
					"active"			=> 1,
					"ip"				=> _es(common()->get_ip()),
				));
				$RECORD_ID = db()->INSERT_ID();
				// Execute custom on_update trigger (if exists one)
				$try_trigger_callback = array(module($_GET["object"]), $this->COMMENTS_OBJ->_on_update_trigger);
				if (is_callable($try_trigger_callback)) {
					call_user_func($try_trigger_callback, $params);
				}
				// Save activity log
				common()->_add_activity_points($this->COMMENTS_OBJ->USER_ID, $OBJECT_NAME."_comment", strlen($_POST["text"]), $RECORD_ID);
				// Return user back
				return js_redirect($RETURN_PATH, false);
			}
		}
		$error_message = _e();
		// Display form
		if (empty($_POST["go"]) || !empty($error_message)) {
		
			if(($this->COMMENTS_OBJ->VIEW_EMAIL_FIELD == true) AND (empty($this->COMMENTS_OBJ->USER_ID))){
				$view_user_email = "1";
			}else{
				$view_user_email = "0";
			}
		
			$replace = array(
				"form_action"		=> $FORM_ACTION,
				"error_message"		=> $error_message,
				"user_name"			=> $_POST["user_name"],
				"view_user_email"	=> $view_user_email,
				"user_email"		=> $_POST["user_email"],
				"text"				=> _prepare_html($_POST["text"]),
				"object_name"		=> _prepare_html($OBJECT_NAME),
				"object_id"			=> intval($OBJECT_ID),
				"use_captcha"		=> intval((bool)module($_GET["object"])->USE_CAPTCHA),
				"captcha_block"		=> main()->_execute($_GET["object"], "_captcha_block"),
				"bb_codes_block"	=> $this->COMMENTS_OBJ->USE_BB_CODES ? main()->call_class_method("bb_codes", "classes/", "_display_buttons", array("unique_id" => "text")) : "",
				"submit_buttons"	=> main()->call_class_method("preview", "classes/", "_display_buttons"),
				"js_check"			=> intval((bool)$this->COMMENTS_OBJ->JS_TEXT_CHECKING),
				"parent_id"			=> intval($_POST["parent_id"]),
			);
			$body = tpl()->parse($STPL_NAME_ADD, $replace);
		}
		return $body;
	}

	/**
	* Do edit own comment
	*/
	function _edit ($params = array()) {
		if (empty($this->COMMENTS_OBJ->USER_ID) && MAIN_TYPE_USER) {
			return _error_need_login();
		}
		$_GET["id"] = intval($_GET["id"]);
		// Try to get given comment info
		$comment_info = db()->query_fetch("SELECT * FROM `".db('comments')."` WHERE `id`=".intval($_GET["id"]));
		if (empty($comment_info["id"])) {
			return _e(t("No such comment!"));
		}
		// Process params
		$OBJECT_NAME	= !empty($params["object_name"]) ? $params["object_name"] : $_GET["object"];
		$OBJECT_ID		= !empty($params["object_id"]) ? intval($params["object_id"]) : intval($_GET["id"]);
		$FORM_ACTION	= !empty($params["add_form_action"]) ? $params["add_form_action"] : "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$OBJECT_ID;
		$STPL_NAME_EDIT	= !empty($params["stpl_edit"]) ? $params["stpl_edit"] : "comments/edit_form";
		$RETURN_PATH	= $_SERVER["HTTP_REFERER"];
		if (!empty($params["return_path"])) {
			$RETURN_PATH = process_url($params["return_path"]);
		} elseif (!empty($params["return_action"])) {
			$RETURN_PATH = process_url("./?object=".$_GET["object"]."&action=".$params["return_action"]."&id=".$comment_info["object_id"]);
		}
		// Check required params
		if (empty($OBJECT_NAME) || empty($OBJECT_ID)) {
			return "";
		}
		// Check if user is allowed to perform this action
		$edit_allowed = false;
		$edit_allowed_check_method	= is_object(module($_GET["object"])) && method_exists(module($_GET["object"]), $this->COMMENTS_OBJ->_edit_allowed_method);
		if ($edit_allowed_check_method) {
			$edit_allowed	= (bool)main()->_execute($_GET["object"], $this->COMMENTS_OBJ->_edit_allowed_method, array(
				"user_id" => $comment_info["user_id"],
				"object_id" => $comment_info["object_id"]
			));
		} else {
			$edit_allowed = $this->COMMENTS_OBJ->USER_ID && $comment_info["user_id"] == $this->COMMENTS_OBJ->USER_ID;
		}
		// Hack for use from the admin section
		if (MAIN_TYPE_ADMIN) {
			$edit_allowed	= true;
		} else {
			if(!empty($this->COMMENTS_OBJ->EDIT_LIMIT_TIME)){
				$elapse_time = time() - $comment_info["add_date"];
				if($elapse_time > $this->COMMENTS_OBJ->EDIT_LIMIT_TIME){
					return _e(t("allowed time to edit has expired"));
				}
			}
		}
		
		if (!$edit_allowed) {
			return _e(t("You are not allowed to perform this action"));
		}
		// Try to get given user info
		$user_info = user($comment_info["user_id"], array("id","name",$this->COMMENTS_OBJ->_user_nick_field,"photo_verified"), array("WHERE" => array("active" => 1)));
		// Check posted data and save
		if (count($_POST) > 0 && !isset($_POST["_not_for_comments"])) {
			$_POST["text"] = substr($_POST["text"], 0, $this->COMMENTS_OBJ->MAX_POST_TEXT_LENGTH);
			if (empty($_POST["text"])) {
				_re(t("Comment text required"));
			}
			// Do check captcha (if needed)
			if (module($_GET["object"])->USE_CAPTCHA) {
				main()->_execute($_GET["object"], "_captcha_check");
			}
			// Check for errors
			if (!common()->_error_exists() && MAIN_TYPE_USER) {
				$info_for_check = array(
					"comment_text"	=> $_POST["text"],
					"user_id"		=> $this->COMMENTS_OBJ->USER_ID,
				);
				$USER_BANNED = _check_user_ban($info_for_check, $this->COMMENTS_OBJ->_user_info);
				if ($USER_BANNED) {
					$this->COMMENTS_OBJ->_user_info = user($this->COMMENTS_OBJ->USER_ID);
				}
				// Stop here if user is banned
				if ($this->COMMENTS_OBJ->_user_info["ban_comments"]) {
					return _e(
						"Sorry, you are not allowed to post comments!\r\nPerhaps, you broke some of our rules and moderator has banned you from using this feature. Please, enjoy our site in some other way!"
						."For more details <a href=\"./?object=faq&action=view&id=16\">click here</a>"
					);
				}
			}
			// Anti-flood check
			if (!common()->_error_exists() && $this->COMMENTS_OBJ->ANTI_FLOOD_TIME && MAIN_TYPE_USER) {
				$FLOOD_DETECTED = db()->query_fetch("SELECT `id`,`add_date` FROM `".db('comments')."` WHERE ".($this->COMMENTS_OBJ->USER_ID ? "`user_id`=".intval($this->COMMENTS_OBJ->USER_ID) : "`ip`='"._es(common()->get_ip())."'")." AND `add_date` > ".(time() - $this->COMMENTS_OBJ->ANTI_FLOOD_TIME)." ORDER BY `add_date` DESC LIMIT 1");
				if (!empty($FLOOD_DETECTED)) {
					_re("Please wait ".intval($this->COMMENTS_OBJ->ANTI_FLOOD_TIME - (time() - $FLOOD_DETECTED["add_date"]))." seconds before post comment.");
				}
			}
			
			// Anti-spam check
			if (!common()->_error_exists()){
				if($this->COMMENTS_OBJ->ANTI_SPAM_DETECT){
					$this->_spam_check($_POST["text"]);
				}
			}

			// Check for errors
			if (!common()->_error_exists()) {
				// Check text fields
				if ($this->COMMENTS_OBJ->AUTO_FILTER_INPUT_TEXT) {
					$_POST["text"] = _filter_text($_POST["text"]);
				}
				// Do close BB Codes (if needed)
				if ($this->COMMENTS_OBJ->USE_BB_CODES) {
					$BB_CODES_OBJ = main()->init_class("bb_codes", "classes/");
					if (is_object($BB_CODES_OBJ)) {
						$_POST["text"] = $BB_CODES_OBJ->_force_close_bb_codes($_POST["text"]);
					}
				}
				// Do update record
				db()->UPDATE("comments", array(
					"text" 			=> _es($_POST["text"]),
				), "`id`=".intval($comment_info["id"]));
				// Execute custom on_update trigger (if exists one)
				$try_trigger_callback = array(module($_GET["object"]), $this->COMMENTS_OBJ->_on_update_trigger);
				if (is_callable($try_trigger_callback)) {
					call_user_func($try_trigger_callback, $params);
				}
				// Return user back
				$RETURN_PATH = !empty($params["return_path"]) ? process_url($params["return_path"]) : (!empty($params["return_action"]) ? process_url("./?object=".$_GET["object"]."&action=".$params["return_action"]."&id=".$comment_info["object_id"]) : $_SERVER["HTTP_REFERER"]);
				return js_redirect($RETURN_PATH, false);
			}
		} else {
			$_POST["text"] = $comment_info["text"];
		}
		$error_message = _e();
		// Show form
		if (empty($_POST["go"]) || !empty($error_message)) {
			$replace = array(
				"form_action"		=> $FORM_ACTION,
				"error_message"		=> $error_message,
				"user_id"			=> intval($this->COMMENTS_OBJ->USER_ID),
				"user_name"			=> _prepare_html(_display_name($user_info)),
				"user_avatar"		=> _show_avatar($comment_info["user_id"], $user_info, 1, 1),
				"user_profile_link"	=> _profile_link($comment_info["user_id"]),
				"user_email_link"	=> _email_link($comment_info["user_id"]),
				"text"				=> _prepare_html($_POST["text"]),
				"back_url"			=> $_SERVER["HTTP_REFERER"],
				"object_name"		=> _prepare_html($OBJECT_NAME),
				"object_id"			=> intval($OBJECT_ID),
				"use_captcha"		=> intval((bool)module($_GET["object"])->USE_CAPTCHA),
				"captcha_block"		=> main()->_execute($_GET["object"], "_captcha_block"),
				"bb_codes_block"	=> $this->COMMENTS_OBJ->USE_BB_CODES ? main()->call_class_method("bb_codes", "classes/", "_display_buttons", array("unique_id" => "text")) : "",
				"js_check"			=> intval((bool)$this->COMMENTS_OBJ->JS_TEXT_CHECKING),
			);
			$body = tpl()->parse($STPL_NAME_EDIT, $replace);
		}
		return $body;
	}

	/**
	* Do delete comment
	*/
	function _delete ($params = array()) {
		if (empty($this->COMMENTS_OBJ->USER_ID) && MAIN_TYPE_USER) {
			return _error_need_login();
		}
		$_GET["id"] = intval($_GET["id"]);
		// Try to get given comment info
		$comment_info = db()->query_fetch("SELECT * FROM `".db('comments')."` WHERE `id`=".intval($_GET["id"]));
		if (empty($comment_info["id"])) {
			return _e(t("No such comment!"));
		}
		// Process params
		$OBJECT_NAME	= !empty($params["object_name"]) ? $params["object_name"] : $_GET["object"];
		$OBJECT_ID		= !empty($params["object_id"]) ? intval($params["object_id"]) : intval($_GET["id"]);
		$SILENT_MODE	= !empty($params["silent_mode"]) ? 1 : 0;
		$RETURN_PATH	= $_SERVER["HTTP_REFERER"];
		if (!empty($params["return_path"])) {
			$RETURN_PATH = process_url($params["return_path"]);
		} elseif (!empty($params["return_action"])) {
			$RETURN_PATH = process_url("./?object=".$_GET["object"]."&action=".$params["return_action"]."&id=".$comment_info["object_id"]);
		}
		// Check required params
		if (empty($OBJECT_NAME) || empty($OBJECT_ID)) {
			return "";
		}
		// Stop here if user is banned
		if ($this->COMMENTS_OBJ->_user_info["ban_comments"] && MAIN_TYPE_USER) {
			return _e(
				"Sorry, you are not allowed to post comments!\r\nPerhaps, you broke some of our rules and moderator has banned you from using this feature. Please, enjoy our site in some other way!"
				."For more details <a href=\"./?object=faq&action=view&id=16\">click here</a>"
			);
		}
		$module_obj = module($_GET["object"]);
		// Check if user is allowed to perform this action
		$delete_allowed = false;
		$delete_allowed_check_method	= is_object($module_obj) && method_exists($module_obj, $this->COMMENTS_OBJ->_delete_allowed_method);
		if ($delete_allowed_check_method) {
			$delete_allowed	= (bool)main()->_execute($_GET["object"], $this->COMMENTS_OBJ->_delete_allowed_method, array(
				"user_id" => $comment_info["user_id"],
				"object_id" => $comment_info["object_id"]
			));
		} else {
			$delete_allowed = $this->COMMENTS_OBJ->USER_ID && $comment_info["user_id"] == $this->COMMENTS_OBJ->USER_ID;
		}
		// Hack for use from the admin section
		if (MAIN_TYPE_ADMIN || $SILENT_MODE) {
			$delete_allowed	= true;
		} else {
			// get elapse time
			if(!empty($this->COMMENTS_OBJ->EDIT_LIMIT_TIME)){
				$elapse_time = time() - $comment_info["add_date"];
				if($elapse_time > $this->COMMENTS_OBJ->EDIT_LIMIT_TIME){
					return _e(t("allowed time to delete has expired"));
				}
			}
		}
		
		if (!$delete_allowed) {
			return _e(t("You are not allowed to perform this action"));
		}
		
		// set comments read
		$OBJ = &main()->init_class("unread");
		if (is_object($OBJ)) {
			$ids = $OBJ->_set_read("comments", $_GET["id"]);
		}
		
		// Do delete comment
		if ($this->COMMENTS_OBJ->USE_TREE_MODE) {
			//if comment not have follow-ups
			$have_children = db()->query_fetch("SELECT `id` FROM `".db('comments')."` WHERE `object_name`='".$comment_info["object_name"]."' AND `object_id`=".$comment_info["object_id"]." AND `parent_id`=".$comment_info["id"]." LIMIT 1");

			if ($have_children) {
				db()->UPDATE("comments", array(
					"text"		=> "__comment was deleted__",
					"user_id"	=> 0,
				), "`id`=".intval($_GET["id"]));
			} else {
				db()->query("DELETE FROM `".db('comments')."` WHERE `id`=".intval($_GET["id"])." LIMIT 1");
			}
		}  else {
			db()->query("DELETE FROM `".db('comments')."` WHERE `id`=".intval($_GET["id"])." LIMIT 1");
		}
		// Execute custom on_update trigger (if exists one)
		$try_trigger_callback = array(module($_GET["object"]), $this->COMMENTS_OBJ->_on_update_trigger);
		if (is_callable($try_trigger_callback)) {
			call_user_func($try_trigger_callback, $params);
		}
		// Return user back
		return !$SILENT_MODE ? js_redirect($RETURN_PATH, false) : "";
	}

	/**
	* Check spam
	*/
	function _spam_check($text){
		preg_match_all($this->COMMENTS_OBJ->HTML_LINK_REGEX, $text, $result);
		preg_match_all($this->COMMENTS_OBJ->BBCODE_LINK_REGEX, $text, $result2);
		
		$count_links = count($result[1]) + count($result2[1]);
		
		// user not logged in
		if (empty($this->COMMENTS_OBJ->USER_ID)) {
			if ($count_links > 1) {
				_re(t("Too many links"));
			}
		} else {
			if ($count_links > 3) {
				_re(t("Too many links"));
			}
		}
	}
}
