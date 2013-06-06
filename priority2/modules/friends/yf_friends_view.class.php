<?php

/**
* Friends view methods container
*
* @package		YF
* @author		Yuri Vysotskiy <profy.net@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_friends_view {

	/**
	* Framework constructor
	*/
	function _init () {
		// Reference to parent object
		$this->PARENT_OBJ	= module(FRIENDS_CLASS_NAME);
	}

	//-----------------------------------------------------------------------------
	// All friends list for the given user
	function view_all_friends () {
		$_GET["id"] = intval($_GET["id"]);
		if (!empty($this->PARENT_OBJ->USER_ID) && empty($_GET["id"])) {
			$_GET["id"] = $this->PARENT_OBJ->USER_ID;
			$user_info = &$this->PARENT_OBJ->_user_info;
		}
		if (empty($_GET["id"])) {
			return _e("No id!");
		}
		if (empty($user_info)) {
			$user_info = user($_GET["id"], "full", array("WHERE" => array("active" => 1)));
		}
		if (empty($user_info)) {
			return _e("No such user!");
		}
		// Get number of records per page
		$PER_PAGE = $this->PARENT_OBJ->ALL_FRIENDS_PER_PAGE;
		// Get user friends ids array
		$friends_ids_1 = $this->PARENT_OBJ->_get_user_friends_ids($user_info["id"]);
		$GLOBALS['user_friends_ids'] = $friends_ids_1;
		// Process them
		if (is_array($friends_ids_1) && !empty($friends_ids_1)) {
			$total = count($friends_ids_1);
			list(,$pages,) = common()->divide_pages(null, null, null, $PER_PAGE, $total);
			// Get a slice from the whole array
			if (count($friends_ids_1) > $PER_PAGE) {
				$friends_ids_1 = array_slice($friends_ids_1, (empty($_GET["page"]) ? 0 : intval($_GET["page"]) - 1) * $PER_PAGE, $PER_PAGE);
			}
			// Try to get users details
			$users_array = user($friends_ids_1, array("id","name","nick","photo_verified","group"), array("WHERE" => array("active" => 1)));
		}
		$IS_OWN_PAGE = $this->PARENT_OBJ->USER_ID && $this->PARENT_OBJ->USER_ID == $user_info["id"];
		// Get users friend of ids
		$friend_of_ids = $this->PARENT_OBJ->_get_users_where_friend_of($user_info["id"]);
		// Process users
		foreach ((array)$users_array as $cur_user_info) {
		
			if($cur_user_info["group"] == "99"){
				$total -= 1;
				continue;
			}
		
			$replace2 = array(
				"bg_class"			=> !(++$i % 2) ? "bg1" : "bg2",
				"user_id"			=> $cur_user_info["id"],
				"user_name"			=> _prepare_html(_display_name($cur_user_info)),
				"user_avatar"		=> _show_avatar($cur_user_info["id"], $cur_user_info, 1),
				"user_details_link"	=> _profile_link($cur_user_info["id"]),
				"delete_link"		=> $IS_OWN_PAGE ? "./?object=".FRIENDS_CLASS_NAME."&action=delete&id=".$cur_user_info["id"] : "",
				"handshake_link"	=> "./?object=".FRIENDS_CLASS_NAME."&action=request_handshake_form&id=".$cur_user_info["id"],
				"is_mutual_friend"	=> isset($friend_of_ids[$cur_user_info["id"]]) ? 1 : 0,
				"need_div"			=> !(++$c2 % $this->PARENT_OBJ->VIEW_ALL_PER_LINE) ? 1 : 0,
			);
			$items .= tpl()->parse(FRIENDS_CLASS_NAME."/view_all_friends_item", $replace2);
		}
		// Process template
		$replace = array(
			"items"					=> $items,
			"pages"					=> trim($pages),
			"total"					=> intval($total),
			"is_own_page"			=> intval($IS_OWN_PAGE),
			"user_name"				=> _prepare_html(_display_name($user_info)),
			"user_avatar"			=> _show_avatar($user_info["id"], $user_info, 1),
			"user_details_link"		=> _profile_link($user_info["id"]),
			"all_handshake_request"	=> "./?object=".FRIENDS_CLASS_NAME."&action=all_handshake_request",
			"friends_posts"			=> "./?object=".FRIENDS_CLASS_NAME."&action=friends_posts",
			"friends_groups"		=> "./?object=".FRIENDS_CLASS_NAME."&action=friends_groups",
		);
		return tpl()->parse(FRIENDS_CLASS_NAME."/view_all_friends_main", $replace);
	}

	//-----------------------------------------------------------------------------
	// All friends list for the given user
	function view_all_friend_of () {
		$_GET["id"] = intval($_GET["id"]);
		if (!empty($this->PARENT_OBJ->USER_ID) && empty($_GET["id"])) {
			$_GET["id"] = $this->PARENT_OBJ->USER_ID;
			$user_info = &$this->PARENT_OBJ->_user_info;
		}
		if (empty($_GET["id"])) {
			return _e("No id!");
		}
		if (empty($user_info)) {
			$user_info = user($_GET["id"], "full", array("WHERE" => array("active" => 1)));
		}
		if (empty($user_info)) {
			return _e("No such user!");
		}
		// Get number of records per page
		$PER_PAGE = $this->PARENT_OBJ->ALL_FRIEND_OF_PER_PAGE;
		// Get users friend of ids
		$friend_of_ids = $this->PARENT_OBJ->_get_users_where_friend_of($user_info["id"]);
		// Process them
		if (is_array($friend_of_ids) && !empty($friend_of_ids)) {
			$total = count($friend_of_ids);
			list(,$pages,) = common()->divide_pages(null, null, null, $PER_PAGE, $total);
			// Get a slice from the whole array
			if (count($friend_of_ids) > $PER_PAGE) {
				$friend_of_ids = array_slice($friend_of_ids, (empty($_GET["page"]) ? 0 : intval($_GET["page"]) - 1) * $PER_PAGE, $PER_PAGE);
			}
			// Try to get users details
			$users_array = user($friend_of_ids, array("id","name","nick","photo_verified","group"), array("WHERE" => array("active" => 1)));
		}
		$IS_OWN_PAGE = $this->PARENT_OBJ->USER_ID && $this->PARENT_OBJ->USER_ID == $user_info["id"];
		// Get my friends ids
		$my_friends_ids = $this->PARENT_OBJ->_get_user_friends_ids($user_info["id"]);
		// Process users
		foreach ((array)$users_array as $cur_user_info) {
		
			if($cur_user_info["group"] == "99"){
				$total -= 1;
				continue;
			}

			$replace2 = array(
				"bg_class"				=> !(++$i % 2) ? "bg1" : "bg2",
				"user_id"				=> $cur_user_info["id"],
				"user_name"				=> _prepare_html(_display_name($cur_user_info)),
				"user_avatar"			=> _show_avatar($cur_user_info["id"], $cur_user_info, 1),
				"user_details_link"		=> _profile_link($cur_user_info["id"]),
				"add_to_friends_link"	=> $IS_OWN_PAGE && !isset($my_friends_ids[$cur_user_info["id"]]) ? "./?object=".FRIENDS_CLASS_NAME."&action=add&id=".$cur_user_info["id"] : "",
				"is_mutual_friend"		=> isset($my_friends_ids[$cur_user_info["id"]]) ? 1 : 0,
				"need_div"				=> !(++$c2 % $this->PARENT_OBJ->VIEW_ALL_PER_LINE) ? 1 : 0,
			);
			$items .= tpl()->parse(FRIENDS_CLASS_NAME."/view_all_friend_of_item", $replace2);
		}
		$replace = array(
			"items"							=> $items,
			"pages"							=> trim($pages),
			"total"							=> intval($total),
			"is_own_page"					=> intval($IS_OWN_PAGE),
			"user_name"						=> _prepare_html(_display_name($user_info)),
			"user_avatar"					=> _show_avatar($user_info["id"], $user_info, 1),
			"user_details_link"				=> _profile_link($user_info["id"]),
			"all_handshake_request_to_you"	=> "./?object=".FRIENDS_CLASS_NAME."&action=all_handshake_request_to_you",
		);
		return tpl()->parse(FRIENDS_CLASS_NAME."/view_all_friend_of_main", $replace);
	}

	//-----------------------------------------------------------------------------
	// Show "friends" info for user profile
	function _show_friends_for_profile ($user_info = array(), $MAX_SHOW_ITEMS = 0) {
		if (empty($user_info)) {
			return false;
		}
		// Get number of records to display
		$NUM_ITEMS = !empty($MAX_SHOW_ITEMS) ? $MAX_SHOW_ITEMS : $this->PARENT_OBJ->FOR_PROFILE_NUM_FRIEND_OF;
		// Get user friends ids array
		$friends_ids_1 = $this->PARENT_OBJ->_get_user_friends_ids ($user_info["id"]);
		$GLOBALS['user_friends_ids'] = $friends_ids_1;
		$GLOBALS['profile_total_friends'] = count($friends_ids_1);
		if (is_array($friends_ids_1) && !empty($friends_ids_1)) {
			// Try to get users details
			$users_array = user($friends_ids_1, array("id","name","login","nick","photo_verified"), array("WHERE" => array("active" => 1)));
		}
		// Process items
		foreach ((array)$users_array as $cur_user_info) {
			if ($i >= $NUM_ITEMS) {
				break;
			}
			$replace2 = array(
				"bg_class"			=> !(++$i % 2) ? "bg1" : "bg2",
				"user_id"			=> $cur_user_info["id"],
				"user_name"			=> _prepare_html(_display_name($cur_user_info)),
				"user_avatar"		=> _show_avatar($cur_user_info["id"], $cur_user_info, 1),
				"user_details_link"	=> _profile_link($cur_user_info["id"]),
				"more_photos_link"	=> "./?object=gallery&action=view&id=".$cur_user_info["id"],
				"need_div"			=> !(++$c2 % $this->PARENT_OBJ->FOR_PROFILE_PER_LINE) ? 1 : 0,
			);
			$items .= tpl()->parse(FRIENDS_CLASS_NAME."/for_profile_friends_item", $replace2);
		}
		// Stop here if no items is found
		$items = trim($items);
		if (empty($items)) {
			return "";
		}
		// Process template
		$replace = array(
			"items"		=> $items,
			"total"		=> count($friends_ids_1),
			"more_link"	=> "./?object=".FRIENDS_CLASS_NAME."&action=view_all_friends&id=".intval($user_info["id"]),
		);
		return tpl()->parse(FRIENDS_CLASS_NAME."/for_profile_friends_main", $replace);
	}

	//-----------------------------------------------------------------------------
	// Show "friend_of" info for user profile
	function _show_friend_of_for_profile ($user_info = array(), $MAX_SHOW_ITEMS = 0) {
		if (empty($user_info)) {
			return false;
		}
		// Get number of records to display
		$NUM_ITEMS = !empty($MAX_SHOW_ITEMS) ? $MAX_SHOW_ITEMS : $this->PARENT_OBJ->FOR_PROFILE_NUM_FRIENDS;
		// Get users friend of ids
		$friend_of_ids = $this->PARENT_OBJ->_get_users_where_friend_of($user_info["id"]);
		$GLOBALS['user_friend_of_ids'] = $friend_of_ids;
		$GLOBALS['profile_total_friend_of'] = count($friend_of_ids);
		if (is_array($friend_of_ids) && !empty($friend_of_ids)) {
			// Try to get users details
			$users_array = user($friend_of_ids, array("id","name","login","nick","photo_verified"), array("WHERE" => array("active" => 1)));
		}
		// Process items
		foreach ((array)$users_array as $cur_user_info) {
			if ($i >= $NUM_ITEMS) {
				break;
			}
			$replace2 = array(
				"bg_class"			=> !(++$i % 2) ? "bg1" : "bg2",
				"user_id"			=> $cur_user_info["id"],
				"user_name"			=> _prepare_html(_display_name($cur_user_info)),
				"user_avatar"		=> _show_avatar($cur_user_info["id"], $cur_user_info, 1),
				"user_details_link"	=> _profile_link($cur_user_info["id"]),
				"more_photos_link"	=> "./?object=gallery&action=view&id=".$cur_user_info["id"],
				"need_div"			=> !(++$c2 % $this->PARENT_OBJ->FOR_PROFILE_PER_LINE) ? 1 : 0,
			);
			$items .= tpl()->parse(FRIENDS_CLASS_NAME."/for_profile_friend_of_item", $replace2);
		}
		// Stop here if no items is found
		$items = trim($items);
		if (empty($items)) {
			return "";
		}
		// Process template
		$replace = array(
			"items"		=> $items,
			"total"		=> count($friend_of_ids),
			"more_link"	=> "./?object=".FRIENDS_CLASS_NAME."&action=view_all_friend_of&id=".intval($user_info["id"]),
		);
		return tpl()->parse(FRIENDS_CLASS_NAME."/for_profile_friend_of_main", $replace);
	}

	/**
	* View rool of friends posts
	*/
	function friends_posts(){
		if (empty($this->PARENT_OBJ->USER_ID)) {
			return _error_need_login();
		}
		
		if(empty($_POST["sort_type_select_box"])){
			$_POST["sort_type_select_box"] = "DESC";
		}

		if(isset($_POST["author_select_box"])){
			$_SESSION["author_select_box"] = $_POST["author_select_box"];
		}

		if(isset($_POST["post_type_select_box"])){
			$_SESSION["post_type_select_box"] = $_POST["post_type_select_box"];
		}
		
		if(isset($_POST["sort_type_select_box"])){
			$_SESSION["sort_type_select_box"] = $_POST["sort_type_select_box"];
		}
		
		$all_friends = implode(",", (array)$this->PARENT_OBJ->_get_user_friends_ids($this->PARENT_OBJ->USER_ID));
		
		if(!empty($_SESSION["author_select_box"])){
			$friends_ids = $_SESSION["author_select_box"];
		}else{
			$friends_ids = $all_friends;
		}
			
		if(empty($friends_ids)){
			return "<b>".t("No friends yet")."</b>";
		}
		
		$Q = db()->query("SELECT * FROM `".db('user')."` WHERE `id` IN(".$all_friends.")");
		while ($A = db()->fetch_assoc($Q)) {
			$users_info[$A["id"]] = $A;
			$author_select[$A["id"]] = _prepare_html(_display_name($A));
		}
		
		$this->PARENT_OBJ->sql_array = array(
			"blog"		=> "SELECT 'name' AS `name`, `id`, CONVERT(SUBSTRING(`title` FROM 1 FOR 100) USING utf8) AS `title`, `add_date`, `user_id` FROM `".db('blog_posts')."` WHERE `user_id` IN(".$friends_ids.")",
			"forum"		=> "SELECT 'name' AS `name`, `id`, CONVERT(SUBSTRING(`text` FROM 1 FOR 100) USING utf8) AS `title`, `created` AS `add_date`, `user_id`  FROM `".db('forum_posts')."` WHERE `user_id` IN(".$friends_ids.")",
			"articles"	=> "SELECT 'name' AS `name`, `id`, CONVERT(SUBSTRING(`title` FROM 1 FOR 100) USING utf8) AS `title`,`add_date`, `user_id` FROM `".db('articles_texts')."` WHERE `user_id` IN(".$friends_ids.")",
			"gallery"	=> "SELECT 'name' AS `name`, `id`, CONVERT(SUBSTRING(`name` FROM 1 FOR 100) USING utf8) AS `title`,`add_date`, `user_id` FROM `".db('gallery_photos')."` WHERE `user_id` IN(".$friends_ids.")",
			"comment"	=> "SELECT 'name' AS `name`, `id`, CONVERT(SUBSTRING(`text` FROM 1 FOR 100) USING utf8) AS `title`,`add_date`, `user_id` FROM `".db('comments')."` WHERE `user_id` IN(".$friends_ids.") AND `object_name` != 'help'",
		);
		
		foreach ((array)$this->PARENT_OBJ->sql_array as $key => $value){
			$post_type_select[$key] = $key;
		}
		
		if(!empty($_SESSION["post_type_select_box"])){
			$sql = str_replace("SELECT 'name'", "SELECT '".$_SESSION["post_type_select_box"]."' ", $this->PARENT_OBJ->sql_array[$_SESSION["post_type_select_box"]]);
		}else{
			foreach ((array)$this->PARENT_OBJ->sql_array as $_k => $_v) {
				$sql[$_k] = str_replace("SELECT 'name'", "SELECT '".$_k."' ", $_v);
			}
		}
		
		if(!empty($_SESSION["post_type_select_box"])){
			$sql = $sql." ORDER BY `add_date` ".$_SESSION["sort_type_select_box"]; 
		}else{
			$sql = "SELECT * FROM ((".implode(") UNION ALL (", $sql).")) AS `tmp` ORDER BY `add_date` ".$_SESSION["sort_type_select_box"]; 
		}
		
		if(!empty($_SESSION["post_type_select_box"])){
			$GLOBALS["PROJECT_CONF"]["divide_pages"]["SQL_COUNT_REWRITE"] = false;
		}
		list($add_sql, $pages, $total) = common()->divide_pages($sql);
		$Q = db()->query($sql.$add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$friends_posts[] = $A;
			// Gather comments details
			if ($A["name"] == "comment") {
				$_comments_ids[$A["id"]] = $A["id"];
			}
		}
		
		// Get comments details
		if (!empty($_comments_ids)) {
			$Q = db()->query("SELECT `id`,`object_id`,`object_name` FROM `".db('comments')."` WHERE `id` IN(".implode(",", $_comments_ids).")");
			while ($A = db()->fetch_assoc($Q)) {
				$_comments_details[$A["id"]] = $A;
			}
		}
		
		if (!empty($_comments_details)){
			foreach ((array)$_comments_details as $value){
				if($value["object_name"] == "articles"){
					$comments_in_articles[$value["id"]] = $value["object_id"];
				}
				
				if($value["object_name"] == "blog"){
					$comments_in_blog[$value["id"]] = $value["object_id"];
				}
				
				if($value["object_name"] == "gallery"){
					$comments_in_gallery[$value["id"]] = $value["object_id"];
				}
			}
		}
		
		$title = array();
		if (!empty($comments_in_articles)) {
			$Q = db()->query("SELECT `id`,`title` FROM `".db('articles_texts')."` WHERE `id` IN(".implode(",",$comments_in_articles).")");
			while ($A = db()->fetch_assoc($Q)) {
				$title[$A["id"]] = "Re: ".$A["title"];
			}
			foreach ((array)$comments_in_articles as $id => $obj_id){
				$_comments_details[$id]["_force_title"] = $title[$obj_id];
			}
		}

		$title = array();		
		if (!empty($comments_in_blog)) {
			$Q = db()->query("SELECT `id`,`title` FROM `".db('blog_posts')."` WHERE `id` IN(".implode(",",$comments_in_blog).")");
			while ($A = db()->fetch_assoc($Q)) {
				$title[$A["id"]] = "Re: ".$A["title"];
			}
			foreach ((array)$comments_in_blog as $id => $obj_id){
				$_comments_details[$id]["_force_title"] = $title[$obj_id];
			}
		}
		
		$title = array();
		if ($comments_in_gallery) {
			$Q = db()->query("SELECT `id`,`name` FROM `".db('gallery_photos')."` WHERE `id` IN(".implode(",",$comments_in_gallery).")");
			while ($A = db()->fetch_assoc($Q)) {
				if(!empty($A["name"])){
					$title[$A["id"]] = "Re: ".$A["name"];
				}else{
					$title[$A["id"]] = "Re: Untitled";
				}
			}
			foreach ((array)$comments_in_gallery as $id => $obj_id){
				$_comments_details[$id]["_force_title"] = $title[$obj_id];
			}
		}
		
		$select_box_change = "ONCHANGE='form.submit();'";
		$first_element = array("0" => "All");
		
		$author_select = my_array_merge($first_element, $author_select);
		$author_select_box = common()->select_box("author_select_box", $author_select, $_SESSION["author_select_box"], false, 2, $select_box_change, false);		
		
		$post_type_select = my_array_merge($first_element, $post_type_select);
		$post_type_select_box = common()->select_box("post_type_select_box", $post_type_select, $_SESSION["post_type_select_box"], false, 2, $select_box_change, false);		
		
		$sort_type_select = array("DESC" => "descending", "ASC" => "ascending");
		$sort_type_select_box = common()->select_box("sort_type_select_box", $sort_type_select, $_SESSION["sort_type_select_box"], false, 2, $select_box_change, false);
		
		// Process posts		
		foreach ((array)$friends_posts as $post){
			$post_link = "";
			$force_title	= "";
			if ($post["name"] == "comment") {
				$comment_info = $_comments_details[$post["id"]];
				$force_title	= $comment_info["_force_title"];
				$post_link = "./?object=".$comment_info["object_name"]."&action=".$this->PARENT_OBJ->_comments_actions[$comment_info["object_name"]]."&id=".$comment_info["object_id"]."#cid_".$comment_info["id"];
			} elseif (!empty($post["name"])) {
				$post_link = $this->PARENT_OBJ->_map_post_urls[$post["name"]].$post["id"];
			}
			$replace2 = array(
				"bg_class"	=> !(++$i % 2) ? "bg1" : "bg2",
				"id"		=> intval($post["id"]),
				"user_name"	=> _prepare_html(_display_name($users_info[$post["user_id"]])),
				"user_id"	=> intval($post["user_id"]),
				"user_link"	=> "./?object=user_profile&action=show&id=".$post["user_id"],
				"title"		=> $force_title ? common()->_cut_bb_codes($force_title) : ($post["title"] ? common()->_cut_bb_codes($post["title"]) : "Untitled"),
				"where"		=> $post["name"],
				"date"		=> _format_date($post["add_date"], "long"),
				"post_link"	=> $post_link,
			);
			$items.= tpl()->parse(FRIENDS_CLASS_NAME."/friends_posts_item", $replace2);
		}
		
		$replace = array(
			"form_action"			=> "./?object=".FRIENDS_CLASS_NAME."&action=".$_GET["action"],
			"items"					=> $items,
			"pages"					=> $pages,
			"author_select_box"		=> $author_select_box,
			"post_type_select_box"	=> $post_type_select_box,
			"sort_type_select_box"	=> $sort_type_select_box,
		);
		return tpl()->parse(FRIENDS_CLASS_NAME."/friends_posts_main", $replace);
	}
}
