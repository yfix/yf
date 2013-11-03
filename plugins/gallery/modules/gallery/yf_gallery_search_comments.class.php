<?php

/**
* Manage users comments
*/
class yf_gallery_search_comments {

	/**
	* Display users comments
	*/
	function search_comments(){
		if (empty(main()->USER_ID)) {
			return _error_need_login();
		}
		if (isset($_GET["id"]) && !isset($_GET["page"])) {
			$_GET["page"] = $_GET["id"];
			unset($_GET["id"]);
		}

		if(isset($_POST["author_select_box"])){
			$_SESSION["author_select_box"] = $_POST["author_select_box"];
		}

		if(isset($_POST["cats_select_box"])){
			$_SESSION["cats_select_box"] = $_POST["cats_select_box"];
		}
		
		if(isset($_POST["sort_type_select_box"])){
			$_SESSION["sort_type_select_box"] = $_POST["sort_type_select_box"];
		}
	
		if(empty($_SESSION["sort_type_select_box"])){
			$_SESSION["sort_type_select_box"] = "DESC";
		}

		if(!empty($_SESSION["cats_select_box"])){
			$WHERE = " AND folder_id=".$_SESSION["cats_select_box"];
		}
		
		$Q = db()->query("SELECT * FROM ".db('gallery_photos')." WHERE user_id=".main()->USER_ID. $WHERE);
		while ($A = db()->fetch_assoc($Q)) {
			$posts_ids[$A["id"]] = $A["id"];
			$posts[$A["id"]] = $A;
		}
		
		if (!empty($posts_ids)) {
		
			if(module('gallery')->SEARCH_ONLY_MEMBER){
				$serch_only_member = " AND NOT (user_id = 0)";
			}

			$sql = "SELECT * 
					FROM ".db('comments')." 
					WHERE object_name='gallery' AND object_id IN(".implode(",", $posts_ids).") ".$serch_only_member;
			$order_sql	= " ORDER BY add_date ".$_SESSION["sort_type_select_box"];
			list($add_sql, $pages, $total) = common()->divide_pages($sql);
			$Q = db()->query($sql.$order_sql.$add_sql);
			while ($A = db()->fetch_assoc($Q)) {
			
				if(!empty($_SESSION["author_select_box"])){
					if($A["user_id"] == $_SESSION["author_select_box"]){
						$comments[] = $A;
					}
				}else{
					$comments[] = $A;
				}
			
				$users_ids[$A["user_id"]] = $A["user_id"];
			}
		}
		
		if (!empty($users_ids)) {
			$users = user($users_ids, array("nick","name"), array("WHERE" => array("active" => "1")));
			if(!empty($users)){
				foreach ((array)$users as $A){
					$users_info[$A["id"]] = $A;
					$author_select[$A["id"]] = _display_name($A);
				}
			}
		}

		$Q = db()->query("SELECT id,title FROM ".db('gallery_folders')." WHERE user_id=".main()->USER_ID);
		while ($A = db()->fetch_assoc($Q)) {
			$cats[$A["id"]] = $A["title"];
		}
		
		$select_box_change = "ONCHANGE='form.submit();'";
		$first_element = array("0" => t("All"));
		
		$author_select = my_array_merge((array)$first_element, (array)$author_select);
		$author_select_box = common()->select_box("author_select_box", $author_select, $_SESSION["author_select_box"], false, 2, $select_box_change, false);		
		
		$cats_select = my_array_merge((array)$first_element, (array)$cats);
		$cats_select_box = common()->select_box("cats_select_box", $cats_select, $_SESSION["cats_select_box"], false, 2, $select_box_change, false);		
		
		$sort_type_select = array("DESC" => "descending", "ASC" => "ascending");
		$sort_type_select_box = common()->select_box("sort_type_select_box", $sort_type_select, $_SESSION["sort_type_select_box"], false, 2, $select_box_change, true);

		foreach ((array)$comments as $comment){
		
			$user_name = _prepare_html(_display_name($users_info[$comment["user_id"]]));
			empty($user_name)?$user_name = _prepare_html($comment["user_name"]):"";

			$replace2 = array(
				"bg_class"		=> !(++$i % 2) ? "bg1" : "bg2",
				"user_name"		=> $user_name,
				"user_id"		=> intval($comment["user_id"]),
				"user_link"		=> $comment["user_id"] !== "0"?"./?object=user_profile&action=show&id=".$comment["user_id"]:"",
				"text"			=> $comment["text"],
				"cat"			=> $cats[$posts[$comment["object_id"]]["folder_id"]],
				"date"			=> _format_date($comment["add_date"], "long"),
				"delete"		=> module('gallery')->ALLOW_DELETE_COMMENTS ? "1":"0",
				"delete_link"	=> "./?object=".$_GET["object"]."&action=delete_gallery_comment&id=".$comment["id"],
			);
			$items.= tpl()->parse($_GET["object"]."/search_comments_item", $replace2);
		}
		
		$replace = array(
			"form_action"			=> "./?object=".$_GET["object"]."&action=".$_GET["action"],
			"items"					=> $items,
			"pages"					=> $pages,
			"author_select_box"		=> $author_select_box,
			"cats_select_box"		=> $cats_select_box,
			"sort_type_select_box"	=> $sort_type_select_box,
			"delete"				=> module('gallery')->ALLOW_DELETE_COMMENTS ? "1" : "0",
		);
		return tpl()->parse($_GET["object"]."/search_comments_main", $replace);
	}

	/**
	* Do delete comment
	*/
	function _delete(){
		// Check if user is member
		if (empty(main()->USER_ID)) {
			return _error_need_login();
		}
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return _e("Empty ID");
		}
		$comment_info = db()->query_fetch(
			"SELECT * FROM ".db('comments')." 
			WHERE object_name='".'gallery'."' 
				AND object_id IN(
					SELECT id FROM ".db('gallery_photos')." WHERE user_id = ".intval(main()->USER_ID)."
				) 
				AND id=".intval($_GET["id"])
		);
		if (empty($comment_info)) {
			return _e("You have no rights to delete this comment");
		}
		$COMMENTS_OBJ = main()->init_class("comments", USER_MODULES_DIR);
		if (is_object($COMMENTS_OBJ)) {
			$COMMENTS_OBJ->_delete(array("silent_mode" => 1));
		}
		return js_redirect("./?object=".'gallery'."&action=search_comments");
	}
}
