<?php

/**
* Comments handler
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_comments {

	/** @var int Number of comments to display on one page */
	public $NUM_PER_PAGE				= 20;
	/** @var int Max post text length */
	public $MAX_POST_TEXT_LENGTH		= 10000;
	/** @var bool Use bb codes or not */
	public $USE_BB_CODES				= true;
	/** @var bool Use tree mode or not */
	public $USE_TREE_MODE				= false;
	/** @var bool 
		Allow Authors to delete comments from other peoples 
		for their objects (profiles, galleries, blogs etc) 
	*/
	public $ALLOW_DELETE_FOR_AUTHOR	= true;
	/** @var bool Use "active" field */
	public $PROCESS_STATUS_FIELD		= true;
	/** @var bool Auto filtering input text */
	public $AUTO_FILTER_INPUT_TEXT		= true;
	/** @var bool Use is text checking */
	public $JS_TEXT_CHECKING			= true;
	/** @var int Time min interval between 2 comments (in seconds). Set to 0 to disable */
	public $ANTI_FLOOD_TIME			= 60;
	/** @var string @conf_skip */
	public $_user_nick_field			= "nick";
	/** @var string @conf_skip */ 
	public $_add_allowed_method		= "_comment_is_allowed";
	/** @var string @conf_skip */ 
	public $_edit_allowed_method		= "_comment_edit_allowed";
	/** @var string @conf_skip */ 
	public $_delete_allowed_method		= "_comment_delete_allowed";
	/** @var string @conf_skip */ 
	public $_view_email_allowed_method	= "_comment_view_email_allowed";
	/** @var string @conf_skip Trigger method (will be called on successful add/edit/delete) */
	public $_on_update_trigger			= "_comment_on_update";
	/** @var int Edit limit time */
	public $EDIT_LIMIT_TIME			= 604800; // week
	/** @var array Comment links @conf_skip */
	public $COMMENT_LINKS = array(
		"news"		=> "./?object=news&action=full_news&id=",
		"articles"	=> "./?object=articles&action=view&id=",
		"blog"		=> "./?object=blog&action=show_single_post&id=",
		"gallery"	=> "./?object=gallery&action=show_medium_size&id=",
	);
	/** @var int */
	public $NUM_RSS 	= 10;
	/** @var string @conf_skip */
	public $HTML_LINK_REGEX = "/<a[^>]+href=([^ >]+)[^>]*>(.*?)<\/a>/si";
	/** @var string @conf_skip */
	public $BBCODE_LINK_REGEX = "/\[URL[^\]]*\](.+?)\[\/URL\]/si";
	/** @var bool */
	public $ANTI_SPAM_DETECT = false;
	/** @var bool 
		When not register user write comment
	*/
	public $VIEW_EMAIL_FIELD = true;
	/** @var bool */
	public $CHECK_ALLOW_TO_VIEW_USER_EMAIL = false;

	/**
	* Framework constructor
	*/
	function _init () {
		define("COMMENTS_CLASS_NAME", "comments");
		define("COMMENTS_MODULES_DIR", "modules/". COMMENTS_CLASS_NAME."/");
		// Fix for the case when skipping auto-assignment of main()->USER_ID in main class
		if (!main()->USER_ID && main()->USER_ID) {
			main()->USER_ID = main()->USER_ID;
		}
	}

	/**
	* Display comments block for given object name
	*/
	function _show_for_object ($params = array()) {
		if ($this->USE_TREE_MODE) {
			return $this->_show_for_object_tree($params);
		} 
		// Get params
		$OBJECT_NAME	= !empty($params["object_name"])	? $params["object_name"] : $_GET["object"];
		$OBJECT_ID		= !empty($params["object_id"])		? intval($params["object_id"]) : intval($_GET["id"]);
		$STPL_NAME_MAIN = !empty($params["stpl_main"])		? $params["stpl_main"] : "comments/main";
		$STPL_NAME_ITEM = !empty($params["stpl_item"])		? $params["stpl_item"] : "comments/item";
		$PAGER_PATH		= !empty($params["pager_path"])		? $params["pager_path"] : "";
		// Check required params
		if (empty($OBJECT_NAME) || empty($OBJECT_ID)) {
			return "";
		}
		// Get current profile comments from db
		$sql		= "SELECT * FROM ".db('comments')." WHERE object_name='"._es($OBJECT_NAME)."' AND object_id=".intval($OBJECT_ID). ($this->PROCESS_STATUS_FIELD ? " AND active='1' " : "");
		$order_sql	= " ORDER BY add_date DESC";
		// Connect pager
		list($add_sql, $pages, $total) = common()->divide_pages(str_replace("SELECT *", "SELECT id", $sql), $PAGER_PATH, null, $this->NUM_PER_PAGE);
		// Process items
		$Q = db()->query($sql.$order_sql.$add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$comments_array[$A["id"]] = $A;
			if ($A["user_id"]) {
				$users_ids[$A["user_id"]] = $A["user_id"];
			}
		}
		// set comments read
		if(!empty(main()->USER_ID) && !empty($comments_array)){
			$OBJ = &main()->init_class("unread");
			if (is_object($OBJ)) {
				$OBJ->_set_read("comments", array_keys($comments_array));
			}
		}
		// Try to get users names
		if (!empty($users_ids)) {
			foreach ((array)user($users_ids, array("id","name",$this->_user_nick_field,"photo_verified")) as $A) {
				$users_names[$A["id"]] = _display_name($A);
				$GLOBALS['verified_photos'][$A["id"]] = $A["photo_verified"];
			} 
		}
		// Process user reputation
		$REPUT_OBJ = main()->init_class("reputation");
		if (is_object($REPUT_OBJ)) {
			$users_reput_info	= $REPUT_OBJ->_get_reput_info_for_user_ids($users_ids);
		}
		// Try to find more complex checking methods
		$obj = module($_GET["object"]);
		$edit_allowed_check_method		= is_object($obj) && method_exists($obj, $this->_edit_allowed_method);
		$delete_allowed_check_method	= is_object($obj) && method_exists($obj, $this->_delete_allowed_method);
		if($this->CHECK_ALLOW_TO_VIEW_USER_EMAIL){
			$view_email_allowed_check_method = is_object($obj) && method_exists($obj, $this->_view_email_allowed_method);		
		}
		
		// Check if view user email allowed
		if($view_email_allowed_check_method){
			$view_email = (bool)main()->_execute($_GET["object"], $this->_view_email_allowed_method, array(
				"object_id" => $OBJECT_ID
			));
		}
		
		// Process comments items
		foreach ((array)$comments_array as $comment_info) {
			// Check if edit comment allowed
			if ($edit_allowed_check_method) {
				$edit_allowed	= (bool)main()->_execute($_GET["object"], $this->_edit_allowed_method, array(
					"user_id"	=> $comment_info["user_id"],
					"object_id"	=> $comment_info["object_id"],
				));
			} else {
				$edit_allowed	= main()->USER_ID && $comment_info["user_id"] == main()->USER_ID;
			}
			// Check if delete comment allowed
			if ($delete_allowed_check_method) {
				$delete_allowed	= (bool)main()->_execute($_GET["object"], $this->_delete_allowed_method, array(
					"user_id" => $comment_info["user_id"],
					"object_id" => $comment_info["object_id"]
				));
			} else {
				$delete_allowed = main()->USER_ID && $comment_info["user_id"] == main()->USER_ID;
			}
			
			// Hack for use from the admin section
			if (MAIN_TYPE_ADMIN) {
				$edit_allowed	= true;
				$delete_allowed = true;
			}
			// Prepare comment text
			$comment_info["text"] = str_replace(array("\\\\","\\'","\\\""), array("\\","'","\""), $comment_info["text"]);
			
			if (($comment_info["text"] == "__comment was deleted__") && ($comment_info["user_id"] == "0")) {
				$comment_info["text"] = t("comment was deleted");
			}
			
			$replace2 = array(
				"need_div"				=> intval($i > 0),
				"bg_class"				=> !(++$i % 2) ? "bg1" : "bg2",
				"comment_id"			=> intval($comment_info["id"]),
				"user_name"				=> _prepare_html(!empty($comment_info["user_id"]) ? $users_names[$comment_info["user_id"]] : $comment_info["user_name"]),
				"user_email"			=> $view_email ? _prepare_html($comment_info["user_email"]) : "",
				"user_avatar"			=> $comment_info["user_id"] ? _show_avatar($comment_info["user_id"], $users_names[$comment_info["user_id"]], 1, 0) : "",
				"user_profile_link"		=> $comment_info["user_id"] ? _profile_link($comment_info["user_id"]) : "",
				"user_email_link"		=> $comment_info["user_id"] ? _email_link($comment_info["user_id"]) : "",
				"add_date"				=> _format_date($comment_info["add_date"], "long"),
				"comment_text"			=> $this->_format_text($comment_info["text"]),
				"edit_comment_link"		=> $edit_allowed ? "./?object=".$_GET["object"]."&action=edit_comment&id=".$comment_info["id"]._add_get(array("page")) : "",
				"delete_comment_link"	=> $delete_allowed ? "./?object=".$_GET["object"]."&action=delete_comment&id=".$comment_info["id"]._add_get(array("page")) : "",
				"reput_text"			=> is_object($REPUT_OBJ) && isset($users_names[$comment_info["user_id"]]) ? $REPUT_OBJ->_show_for_user($comment_info["user_id"], $users_reput_info[$comment_info["user_id"]], false, array("comments", $comment_info["id"])) : "",
				"user_id"				=> $comment_info["user_id"],
			);
		$items .= tpl()->parse($STPL_NAME_ITEM, $replace2);
		}
		
		if(!empty(main()->USER_ID)){
			$add_comment_form = $this->_add($params);
		}else{
			$add_comment_form = "";
		}
		
		if($params["allow_guests_posts"]){
			$add_comment_form = $this->_add($params);
		}
		
		// Process main template
		$replace = array(
			"comments"			=> $items,
			"comments_pages"	=> $pages,
			"num_comments"		=> intval($total),
			"add_comment_form"	=> $add_comment_form,
			"login_link"		=> empty(main()->USER_ID) && MAIN_TYPE_USER ? "./?object=login_form&go_url=".$OBJECT_NAME.";".$_GET["action"].";id=".$OBJECT_ID : "",
		);
		return tpl()->parse($STPL_NAME_MAIN, $replace);
	}

	/**
	* Display comments tree
	*/
	function _show_for_object_tree ($params = array()) {
		// Get params
		$OBJECT_NAME	= !empty($params["object_name"])	? $params["object_name"] : $_GET["object"];
		$OBJECT_ID		= !empty($params["object_id"])		? intval($params["object_id"]) : intval($_GET["id"]);
		$STPL_NAME_MAIN = !empty($params["stpl_main"])		? $params["stpl_main"] : "comments/main_tree";
		$STPL_NAME_ITEM = !empty($params["stpl_item"])		? $params["stpl_item"] : "comments/item_tree";
		$PAGER_PATH		= !empty($params["pager_path"])		? $params["pager_path"] : "";
	
		$FORM_ACTION	= !empty($params["add_form_action"]) ? $params["add_form_action"] : "./?object=".$_GET["object"]."&action=add_comment&id=".$OBJECT_ID;
		$USE_TREE_MODE = !empty($params["use_tree_mode"]) ? $params["use_tree_mode"] : $this->USE_TREE_MODE; 

		// Check required params
		if (empty($OBJECT_NAME) || empty($OBJECT_ID)) {
			return "";
		}
		// Get current profile comments from db
		$sql		= "SELECT * FROM ".db('comments')." WHERE object_name='"._es($OBJECT_NAME)."' AND object_id=".intval($OBJECT_ID). ($this->PROCESS_STATUS_FIELD ? " AND active='1' " : "");
		$order_sql	= " ORDER BY add_date ASC";
		// Process items
		$Q = db()->query($sql.$order_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$comments_array[$A["id"]] = $A;
			$comments_array_ids[$A["id"]] = $A["parent_id"];
			$users_ids[$A["user_id"]] = $A["user_id"];
		}
		// set comments read
		if(!empty(main()->USER_ID) && !empty($comments_array)){
			$OBJ = &main()->init_class("unread");
			if (is_object($OBJ)) {
				$ids = $OBJ->_set_read("comments", array_keys($comments_array));
			}
		}
		// Try to get users names
		if (!empty($users_ids)) {
			foreach ((array)user($users_ids, array("id","name",$this->_user_nick_field,"photo_verified")) as $A) {
				$users_names[$A["id"]] = _display_name($A);
				$GLOBALS['verified_photos'][$A["id"]] = $A["photo_verified"];
			} 
		}
		// Process user reputation
		$REPUT_OBJ = main()->init_class("reputation");
		if (is_object($REPUT_OBJ)) {
			$users_reput_info	= $REPUT_OBJ->_get_reput_info_for_user_ids($users_ids);
		}
		// Try to find more complex checking methods
		$obj = module($_GET["object"]);
		$edit_allowed_check_method		= is_object($obj) && method_exists($obj, $this->_edit_allowed_method);
		$delete_allowed_check_method	= is_object($obj) && method_exists($obj, $this->_delete_allowed_method);
		if($this->CHECK_ALLOW_TO_VIEW_USER_EMAIL){
			$view_email_allowed_check_method = is_object($obj) && method_exists($obj, $this->_view_email_allowed_method);
		}

		// Check if view user email allowed
		if($view_email_allowed_check_method){
			$view_email = (bool)main()->_execute($_GET["object"], $this->_view_email_allowed_method, array(
				"object_id" => $OBJECT_ID
			));
		}


		//----------  SORT ARRAY TO TREE ------
		$this->_comment_array = $comments_array_ids;
		$this->_comment_tree_array = array();

		if (!empty($this->_comment_array)) {
			foreach ((array)$this->_comment_array as $key => $value) {
				if ($value == 0) {
					$temp_array[$key] = $value;
				}
			}
			$this->_sort_to_tree($temp_array);
		}
		//-------------------------------------
		// Process comments items
		foreach ((array)$this->_comment_tree_array as $comment_tree_info) {
			$comment_info = $comments_array[$comment_tree_info["id"]];
			$level = $comment_tree_info["level"];
			
			// Check if edit comment allowed
			if ($edit_allowed_check_method) {
				$edit_allowed	= (bool)main()->_execute($_GET["object"], $this->_edit_allowed_method, array(
					"user_id" => $comment_info["user_id"],
					"object_id" => $comment_info["object_id"]
				));
			} else {
				$edit_allowed	= main()->USER_ID && $comment_info["user_id"] == main()->USER_ID;
			}
			// Check if delete comment allowed
			if ($delete_allowed_check_method) {
				$delete_allowed	= (bool)main()->_execute($_GET["object"], $this->_delete_allowed_method, array(
					"user_id" => $comment_info["user_id"],
					"object_id" => $comment_info["object_id"]
				));
			} else {
				$delete_allowed = main()->USER_ID && $comment_info["user_id"] == main()->USER_ID;
			}
			// Hack for use from the admin section
			if (MAIN_TYPE_ADMIN) {
				$edit_allowed	= true;
				$delete_allowed = true;
			}

			// Prepare comment text
			$comment_info["text"] = str_replace(array("\\\\","\\'","\\\""), array("\\","'","\""), $comment_info["text"]);
			
			if(($comment_info["text"] == "__comment was deleted__") AND ($comment_info["user_id"] == "0")){
				$comment_info["text"] = t(str_replace("__", "", $comment_info["text"]));
			}
			
			$replace2 = array(
				"user_id"					=> intval($comment_info["user_id"]),
				"user_name"					=> _prepare_html(!empty($comment_info["user_id"]) ? $users_names[$comment_info["user_id"]] : $comment_info["user_name"]),
				"user_email"				=> $view_email ? _prepare_html($comment_info["user_email"]) : "",
				"user_avatar"				=> $comment_info["user_id"] ? _show_avatar($comment_info["user_id"], $users_names[$comment_info["user_id"]], 1, 0, 1) : "",
				"user_profile_link"			=> $comment_info["user_id"] ? _profile_link($comment_info["user_id"]) : "",
				"user_email_link"			=> $comment_info["user_id"] ? _email_link($comment_info["user_id"]) : "",
				"add_date"					=> _format_date($comment_info["add_date"], "long"),
				"comment_text"				=> $this->_format_text($comment_info["text"]),
				"edit_comment_link"			=> $edit_allowed ? "./?object=".$_GET["object"]."&action=edit_comment&id=".$comment_info["id"]._add_get(array("page")) : "",
				"delete_comment_link"		=> $delete_allowed ? "./?object=".$_GET["object"]."&action=delete_comment&id=".$comment_info["id"]._add_get(array("page")) : "",
				"current_link"				=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$_GET["id"]."#cid_".$comment_info["id"],
				"reput_text"				=> is_object($REPUT_OBJ) && isset($users_names[$comment_info["user_id"]]) ? $REPUT_OBJ->_show_for_user($comment_info["user_id"], $users_reput_info[$comment_info["user_id"]], false, array("comments", $comment_info["id"])) : "",
				"id"						=> $comment_info["id"],
				"comment_margin_left"		=> $level * 30,
			);
			$items .= tpl()->parse($STPL_NAME_ITEM, $replace2);
		}
		
		if(!empty(main()->USER_ID)){
			$add_comment_form = $this->_add($params);
		}else{
			$add_comment_form = "";
		}
		
		if($params["allow_guests_posts"]){
			$add_comment_form = $this->_add($params);
		}
		
		// Process main template
		$replace = array(
			"comments"				=> $items,
			"comments_pages"		=> $pages,
			"num_comments"			=> intval($total),
			"add_comment_form"		=> $add_comment_form,
			"login_link"			=> empty(main()->USER_ID) && MAIN_TYPE_USER ? "./?object=login_form&go_url=".$OBJECT_NAME.";".$_GET["action"].";id=".$OBJECT_ID : "",
			"add_comment_action"	=> $FORM_ACTION,
		);
		return tpl()->parse($STPL_NAME_MAIN, $replace);
	}

	/**
	* Form to add comments
	*/
	function _add ($params = array()) {
		$OBJ = $this->_load_sub_module("comments_manage");
		return is_object($OBJ) ? $OBJ->_add($params) : "";
	}

	/**
	* Do edit own comment
	*/
	function _edit ($params = array()) {
		$OBJ = $this->_load_sub_module("comments_manage");
		return is_object($OBJ) ? $OBJ->_edit($params) : "";
	}

	/**
	* Do delete comment
	*/
	function _delete ($params = array()) {
		$OBJ = $this->_load_sub_module("comments_manage");
		return is_object($OBJ) ? $OBJ->_delete($params) : "";
	}

	/**
	* Get number of comments for the given objects ids
	*/
	function _get_num_comments ($params = array()) {
		// Get params
		$OBJECT_NAME	= !empty($params["object_name"]) ? $params["object_name"] : $_GET["object"];
		$OBJECTS_IDS	= !empty($params["objects_ids"]) ? $params["objects_ids"] : "";
		if (empty($OBJECTS_IDS)) {
			return false;
		}
		// Do filter ids
		$tmp_array = explode(",", $OBJECTS_IDS);
		$OBJECTS_IDS = array();
		foreach ((array)$tmp_array as $cur_id) {
			if (empty($cur_id)) {
				continue;
			}
			$OBJECTS_IDS[$cur_id] = $cur_id;
		}
		if (empty($OBJECTS_IDS)) {
			return false;
		}
		// Do get number of ids from db
		$Q = db()->query("SELECT COUNT(id) AS num,object_id FROM ".db('comments')." WHERE object_id IN(".implode(",", $OBJECTS_IDS).") AND object_name='"._es($OBJECT_NAME)."' GROUP BY object_id");
		while ($A = db()->fetch_assoc($Q)) {
			$num_comments_by_object_id[$A["object_id"]] = $A["num"];
		}
		// Do return result
		return $num_comments_by_object_id;
	}

	/**
	* Format given text (convert BB Codes, new lines etc)
	*/
	function _format_text ($body = "") {
		// Stop here if text is empty
		if (empty($body)) {
			return "";
		}
		// If special code is "on" - process it
		if ($this->USE_BB_CODES) {
			$BB_CODES_OBJ = main()->init_class("bb_codes", "classes/");
		}
		// We cannot die, need to be safe
		if ($this->USE_BB_CODES && is_object($BB_CODES_OBJ)) {
			$body = $BB_CODES_OBJ->_process_text($body);
		} else {
			$body = nl2br(_prepare_html($body, 0));
		}
		return $body;
	}

	/**
	* Comments tree sorting
	*/
	function _sort_to_tree($comment, $level = 0){
		if(empty($comment)){
			return false;
		}
		foreach ((array)$comment as $id => $parent_id) {
			if ((!in_array($id, $this->_comment_array)) and (!in_array($id, $this->_comment_tree_array))) {
				$this->_comment_tree_array[] = array("id" =>$id, "level" => $level);
				continue;
			}
			
			if (in_array($id, $this->_comment_array)) {
				$this->_comment_tree_array[] = array("id" =>$id, "level" => $level);
				
				$temp_array = array();
				foreach ((array)$this->_comment_array as $key => $value){
					if($value == $id) $temp_array[$key] = $value;
				}

				if(!empty($temp_array)){
					$this->_sort_to_tree($temp_array, $level + 1);
				}
			}
		}
	}

	/**
	* For user profile method
	*/
	function _for_user_profile($user_id, $MAX_SHOW_COMMENTS) {
	
		$OBJ = $this->_load_sub_module("comments_integration");
		return is_object($OBJ) ? $OBJ->_for_user_profile($user_id, $MAX_SHOW_COMMENTS) : "";
	}
	
	/**
	* For home page method
	*/
	function _for_home_page($NUM_NEWEST_COMMENTS = 4){
		return $this->_load_sub_module("comments_integration")->_for_home_page($NUM_NEWEST_COMMENTS);
	}
	
	/**
	* Hook for the RSS module
	*/
	function _rss_general(){
		$OBJ = $this->_load_sub_module("comments_integration");
		return is_object($OBJ) ? $OBJ->_rss_general() : "";
	}

	/**
	* Try to load sub_module
	*/
	function _load_sub_module ($module_name = "") {
		return _class($module_name, 'modules/comments/');
	}
	
	/**
	*
	*/
	function _unread () {
	
	
		if(empty($this->_user_info["last_view"])){
			return;
		}
		
		$Q = db()->query("SELECT id FROM ".db('comments')." WHERE user_id != ".intval(main()->USER_ID)." AND add_date > ".$this->_user_info["last_view"]);
		while ($A = db()->fetch_assoc($Q)) {
			$ids[$A["id"]] = $A["id"];
		}
		
		$link = process_url("./?object=comments&action=view_unread");

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
	
		$OBJ = &main()->init_class("unread");
		if (is_object($OBJ)) {
			$ids = $OBJ->_get_unread("comments");
		}
		
		$BB_CODES_OBJ = main()->init_class("bb_codes", "classes/");
		
		if(!empty($ids)){
			$sql		= "SELECT text,object_name,id,object_id FROM ".db('comments')." WHERE id IN(".implode(",", (array)$ids).")";
			$order_sql	= " ORDER BY add_date DESC";
			list($add_sql, $pages, $total) = common()->divide_pages($sql);
			$Q = db()->query($sql.$order_sql.$add_sql);
			while ($A = db()->fetch_assoc($Q)) {
			
				$A["text"] = _truncate($A["text"], 50, true);
				$A["text"] = $BB_CODES_OBJ->_force_close_bb_codes($A["text"]);
				$A["text"] = $this->_format_text($A["text"])." ...";
				
				$OBJ = &main()->init_class($A["object_name"]);
				if(is_object($OBJ)){
					$action = $OBJ->_comments_params["return_action"];
					$A["action"] = $action;
				}
				
				$comments_info[$A["id"]] = $A;
			}
		}
		

		$replace = array(
			"items"		=> $comments_info,
			"pages"		=> $pages,
		);
		
		return tpl()->parse($_GET["object"]."/unread", $replace);
	}


}
