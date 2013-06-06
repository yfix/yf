<?php

//-----------------------------------------------------------------------------
// Friends manager
class yf_friends extends yf_module {

	/** @var int Number of "friends" on edit page */
	var $ALL_FRIENDS_PER_PAGE		= 10;
	/** @var int Number "friend of" users on page */
	var $ALL_FRIEND_OF_PER_PAGE		= 10;
	/** @var int Number of records to show for profile */
	var $FOR_PROFILE_NUM_FRIEND_OF	= 9;
	/** @var int Number of records to show for profile */
	var $FOR_PROFILE_NUM_FRIENDS	= 9;
	/** @var int Number of columns for view all page */
	var $VIEW_ALL_PER_LINE			= 3;
	/** @var int Number of columns for profile */
	var $FOR_PROFILE_PER_LINE		= 3;
	/** @var bool Send email notifications on add/delete */
	var $SEND_EMAIL_NOTIFY			= true;
	/** @var array Status */
	var $status = array(
			"1"	=> "waiting",
			"2"	=> "declined",
			"3"	=> "accepted",
	);
	/** @var array Mapping of vote pages */
	var $_map_post_urls = array(
		"forum"		=> "./?object=forum&action=view_post&id=",
		"blog"		=> "./?object=blog&action=show_single_post&id=",
		"articles"	=> "./?object=articles&action=view&id=",
		"gallery"	=> "./?object=gallery&action=show_medium_size&id=",
		"reviews"	=> "./?object=reviews&action=view_details&id=",
	);
	/** @var array pairs object=comment_action */
	var $_comments_actions	= array(
		"articles"		=> "view",
		"blog"			=> "show_single_post",
		"faq"			=> "view",
		"gallery"		=> "show_medium_size",
//		"help"			=> "view_answers",
		"news"			=> "full_news",
		"que"			=> "view",
		"reviews"		=> "view_details",
		"user_profile"	=> "show",
	);
	
	/** @var int Number of friends group */
	var $NUMBER_FRIENDS_GROUP = 10;
	
	/** @var array Friends group names */
	var $_friends_group = array(
		"1"	=> "Family",
		"2"	=> "Buddies",
		"3"	=> "CoWorkers"
	);

	//-----------------------------------------------------------------------------
	// YF module constructor
	function _init () {
		// Friends class name (to allow changing only in one place)
		define("FRIENDS_CLASS_NAME", "friends");
		// Friends modules folder
		define("FRIENDS_MODULES_DIR", USER_MODULES_DIR. FRIENDS_CLASS_NAME."/");
		// Try to init captcha
		$this->CAPTCHA = main()->init_class("captcha", "classes/");
//		$this->CAPTCHA->set_image_size(120, 50);
//		$this->CAPTCHA->font_height = 16;
	}

	//-----------------------------------------------------------------------------
	// Default method
	function show () {
		return $this->view_all_friends();
	}

	//-----------------------------------------------------------------------------
	// Add user to friends list
	function add () {
		$OBJ = $this->_load_sub_module("friends_manage");
		return is_object($OBJ) ? $OBJ->add() : "";
	}

	//-----------------------------------------------------------------------------
	// Delete selected friend
	function delete () {
		$OBJ = $this->_load_sub_module("friends_manage");
		return is_object($OBJ) ? $OBJ->delete() : "";
	}

	/**
	* View rool of friends posts
	*/
	function friends_posts(){
		$OBJ = $this->_load_sub_module("friends_view");
		return is_object($OBJ) ? $OBJ->friends_posts() : "";
	}

	//-----------------------------------------------------------------------------
	// All friends list for the given user
	function view_all_friends () {
		$OBJ = $this->_load_sub_module("friends_view");
		return is_object($OBJ) ? $OBJ->view_all_friends() : "";
	}

	//-----------------------------------------------------------------------------
	// All friends list for the given user
	function view_all_friend_of () {
		$OBJ = $this->_load_sub_module("friends_view");
		return is_object($OBJ) ? $OBJ->view_all_friend_of() : "";
	}

	//-----------------------------------------------------------------------------
	// Show "friends" info for user profile
	function _show_friends_for_profile ($user_info = array(), $MAX_SHOW_ITEMS = 0) {
		$OBJ = $this->_load_sub_module("friends_view");
		return is_object($OBJ) ? $OBJ->_show_friends_for_profile ($user_info, $MAX_SHOW_ITEMS) : "";
	}

	//-----------------------------------------------------------------------------
	// Show "friend_of" info for user profile
	function _show_friend_of_for_profile ($user_info = array(), $MAX_SHOW_ITEMS = 0) {
		$OBJ = $this->_load_sub_module("friends_view");
		return is_object($OBJ) ? $OBJ->_show_friend_of_for_profile ($user_info, $MAX_SHOW_ITEMS) : "";
	}

	//-----------------------------------------------------------------------------
	// Get current user friends ids array
	function _get_user_friends_ids ($target_user_id) {
		$OBJ = $this->_load_sub_module("friends_manage");
		return is_object($OBJ) ? $OBJ->_get_user_friends_ids ($target_user_id) : "";
	}

	//-----------------------------------------------------------------------------
	// Add friends to user's friends list
	function _add_user_friends_ids ($target_user_id, $add_friends_ids = array()) {
		$OBJ = $this->_load_sub_module("friends_manage");
		return is_object($OBJ) ? $OBJ->_add_user_friends_ids ($target_user_id, $add_friends_ids) : "";
	}

	//-----------------------------------------------------------------------------
	// Delete friends to user's friends list
	function _del_user_friends_ids ($target_user_id, $del_friends_ids = array()) {
		$OBJ = $this->_load_sub_module("friends_manage");
		return is_object($OBJ) ? $OBJ->_del_user_friends_ids ($target_user_id, $del_friends_ids) : "";
	}

	//-----------------------------------------------------------------------------
	// Save friends
	function _save_user_friends_ids ($target_user_id, $friends_array = array()) {
		$OBJ = $this->_load_sub_module("friends_manage");
		return is_object($OBJ) ? $OBJ->_save_user_friends_ids ($target_user_id, $friends_array) : "";
	}

	//-----------------------------------------------------------------------------
	// Check if one user if a friends to another
	function _is_a_friend ($user_id_1, $user_id_2) {
		list($IS_A_FRIEND) = db()->query_fetch("SELECT `user_id` AS `0` FROM `".db('friends')."` WHERE `user_id`=".intval($user_id_1)." AND `friends_list` LIKE '%,".intval($user_id_2).",%' LIMIT 1");
		return intval((bool) $IS_A_FRIEND);
	}

	//-----------------------------------------------------------------------------
	// Get all users where current one is in friends list
	function _get_users_where_friend_of ($user_id_1) {
		$users_ids = array();
		$Q = db()->query("SELECT `user_id` FROM `".db('friends')."` WHERE `friends_list` LIKE '%,".intval($user_id_1).",%'");
		while ($A = db()->fetch_assoc($Q)) {
			if (empty($A["user_id"])) {
				continue;
			}
			$users_ids[$A["user_id"]] = $A["user_id"];
		}
		return $users_ids;
	}

	/**
	* 
	*/
	function request_handshake_form(){
		$OBJ = $this->_load_sub_module("friends_handshake");
		return is_object($OBJ) ? $OBJ->request_handshake_form() : "";
	}
	
	/**
	* 
	*/
	function send_request_handshake(){
		$OBJ = $this->_load_sub_module("friends_handshake");
		return is_object($OBJ) ? $OBJ->send_request_handshake() : "";
	}

	/**
	* 
	*/
	function all_handshake_request($sender = 0, $object = ""){
		$OBJ = $this->_load_sub_module("friends_handshake");
		return is_object($OBJ) ? $OBJ->all_handshake_request($sender, $object) : "";
	}

	/**
	* 
	*/
	function all_handshake_request_to_you($receiver = 0, $object = ""){ 
		$OBJ = $this->_load_sub_module("friends_handshake");
		return is_object($OBJ) ? $OBJ->all_handshake_request_to_you($receiver, $object) : "";
	}

	/**
	* 
	*/
	function accept_handshake(){
		$OBJ = $this->_load_sub_module("friends_handshake");
		return is_object($OBJ) ? $OBJ->accept_handshake() : "";
	}

	/**
	* 
	*/
	function decline_handshake(){
		$OBJ = $this->_load_sub_module("friends_handshake");
		return is_object($OBJ) ? $OBJ->decline_handshake() : "";
	}

	/**
	* 
	*/
	function group_handshake_action(){
		$OBJ = $this->_load_sub_module("friends_handshake");
		return is_object($OBJ) ? $OBJ->group_handshake_action() : "";
	}

	/**
	* 
	*/
	function delete_handshake(){
		$OBJ = $this->_load_sub_module("friends_handshake");
		return is_object($OBJ) ? $OBJ->delete_handshake() : "";
	}

	/**
	* 
	*/
	function group_handshake_delete(){
		$OBJ = $this->_load_sub_module("friends_handshake");
		return is_object($OBJ) ? $OBJ->group_handshake_delete() : "";
	}

	/**
	* Try to load blog sub_module
	*/
	function _load_sub_module ($module_name = "") {
		$OBJ = main()->init_class($module_name, FRIENDS_MODULES_DIR);
		if (!is_object($OBJ)) {
			trigger_error("BLOG: Cant load sub_module \"".$module_name."\"", E_USER_WARNING);
			return false;
		}
		return $OBJ;
	}
	
	/**
	* 
	*/
	function friends_groups(){
		$OBJ = $this->_load_sub_module("friends_groups");
		return is_object($OBJ) ? $OBJ->friends_groups() : "";
	}
	
	function _get_friends_groups($user_id){
		$OBJ = $this->_load_sub_module("friends_groups");
		return is_object($OBJ) ? $OBJ->_get_friends_groups($user_id) : "";
	}
	
	function _ids_to_mask($group_ids){
		$OBJ = $this->_load_sub_module("friends_groups");
		return is_object($OBJ) ? $OBJ->_ids_to_mask($group_ids) : "";
	}
	
	function _mask_to_ids($mask){
		$OBJ = $this->_load_sub_module("friends_groups");
		return is_object($OBJ) ? $OBJ->_mask_to_ids($mask) : "";
	}
	
	function check_mask_permissions($user_mask, $post_mask){
		$OBJ = $this->_load_sub_module("friends_groups");
		return is_object($OBJ) ? $OBJ->check_mask_permissions($user_mask, $post_mask) : "";
	}
	
	/**
	* Quick menu auto create
	*/
	function _quick_menu () {
		$menu = array(
			array(
				"name"	=> "Friends",
				"url"	=> "./?object=".$_GET["object"]."&action=view_all_friends",
			),
			array(
				"name"	=> "Friend Of",
				"url"	=> "./?object=".$_GET["object"]."&action=view_all_friends",
			),
			array(
				"name"	=> "All handshake requests",
				"url"	=> "./?object=".$_GET["object"]."&action=all_handshake_request",
			),
			array(
				"name"	=> "Friends posts",
				"url"	=> "./?object=".$_GET["object"]."&action=friends_posts",
			),
			array(
				"name"	=> "Friends groups",
				"url"	=> "./?object=".$_GET["object"]."&action=friends_groups",
			),
			array(
				"name"	=> "All handshake request to you",
				"url"	=> "./?object=".$_GET["object"]."&action=all_handshake_request_to_you",
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
		// Default subheader get from action name
		$subheader = _ucwords(str_replace("_", " ", $_GET["action"]));
		// Array of replacements
		$cases = array (
			//$_GET["action"] => {string to replace}
			"show"							=> "",
			"view_all_friends"				=> "User Friends List",
			"view_all_friend_of"			=> "Friend of List",
			"all_handshake_request"			=> "Manage handshake requests",
			"all_handshake_request_to_you" 	=> "Manage handshake requests to you",			
		);
		if (isset($cases[$_GET["action"]])) {
			// Rewrite default subheader
			$subheader = $cases[$_GET["action"]];
		}
		return array(
			"header"	=> $page_header ? _prepare_html($page_header) : t("Friends"),
			"subheader"	=> $subheader ? _prepare_html($subheader) : "",
		);
	}
	
	/**
	* 
	*/
	function _account_suggests(){
		$OBJ = $this->_load_sub_module("friends_handshake");
		return is_object($OBJ) ? $OBJ->_account_suggests() : "";
	}
}
