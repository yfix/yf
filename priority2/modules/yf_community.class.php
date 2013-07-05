<?php
class yf_community {

	/** @var int Community group number */
	public $COMMUNITY_GROUP = 99; 

	function show(){
		$sql = "SELECT * FROM ".db('community')." WHERE active='1'";
		list($add_sql, $pages, $total) = common()->divide_pages($sql);
		$Q = db()->query($sql.$add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$communitys[$A["id"]] = $A;
			$communitys_ids[$A["user_id"]] = $A["user_id"];
		}

		if(!empty($communitys_ids)){
			$Q = db()->query("SELECT * FROM ".db('interests')." WHERE user_id IN(".implode(",",$communitys_ids).")");
			while ($A = db()->fetch_assoc($Q)) {
				$output_array = array();
				foreach ((array)explode(";", trim($A["keywords"])) as $cur_word) {
					if (!strlen($cur_word)) {
						continue;
					}
					$output_array[$cur_word] = array(
						"search_link"	=> "./?object=interests&action=search&id=".$this->_prepare_keyword_for_url($cur_word),
						"keyword"		=> _prepare_html($cur_word),
					);
				}
				$interests_info[$A["user_id"]] = $output_array;
			}
		}

		$community_name = user($communitys_ids, array("nick"));
		if(!empty($communitys)){
			foreach ((array)$communitys as $community){
				$replace2 = array(
					"name"			=> $community_name[$community["user_id"]]["nick"],
					"title"			=> $community["title"],
					"community_link"=> "./?object=".$_GET["object"]."&action=view&id=".$community["id"],
					"about"			=> $community["about"],
					"interests"		=> $interests_info[$community["user_id"]],
					"adult"			=> $community["adult"],
				);

				$items .= tpl()->parse($_GET["object"]."/main_item", $replace2);
			}
		}
		
		$replace = array(
			"items"			=> $items,
			"pages"			=> $pages,
		);
		
		return tpl()->parse($_GET["object"]."/main", $replace);
	}
	
	function create(){
		if (empty($this->USER_ID)) {
			return _error_need_login();
		}
		
		if(isset($_POST["go"])){
			if($_POST["user"] == ""){
				_re(t("Account name is required!"));
			}else{
				$name = db()->query_fetch("SELECT id FROM ".db('user')." WHERE login='".$_POST["user"]."'");
				if(!empty($name)){
					_re(t("Account name")." (".$_POST["user"].") ".t("is already reserved. Please try another one."));
				}
			}
			if($_POST["title"] == ""){
				_re(t("Community title is required!"));
			}
			
			if(!common()->_error_exists()){
				// add user
				db()->INSERT("user", array(
					"group"		=> $this->COMMUNITY_GROUP,
					"nick"		=> _es($_POST["user"]),
					"login"		=> _es($_POST["user"]),
					"active"	=> "1",
					"ip"		=> common()->get_ip(),
					"add_date"	=> time(),
				));

				$community_user_id = db()->INSERT_ID();
				
				//add community
				db()->INSERT("community", array(
					"user_id"			=> $community_user_id,
					"owner_id"			=> $this->USER_ID,
					"title"				=> _es($_POST["title"]),
					"membership"		=> $_POST["membership"], 
					"nonmember_posting"	=> $_POST["nonmember_posting"],
					"postlevel"			=> $_POST["postlevel"],
					"moderated"			=> $_POST["moderated"],
					"adult"				=> $_POST["adult"],
					"active"			=> "1",
				));
				
				$community_id = db()->INSERT_ID();
				
				db()->INSERT("community_users", array(
					"user_id"		=> $this->USER_ID,
					"community_id"	=> $community_id,
					"member"		=> "1",
					"post"			=> "1",
					"unmoderated"	=> "0",
					"moderator"		=> "1",
					"maintainer"	=> "1",
				));
				
				$OBJ_FRIENDS = main()->init_class("friends");
				
				$OBJ_FRIENDS->_add_user_friends_ids($this->USER_ID, $community_user_id);
				$OBJ_FRIENDS->_add_user_friends_ids($community_user_id, $this->USER_ID);
				
				$replace2 = array(
					"form_action"		=> "./?object=".$_GET["object"]."&action=info&id=".$community_id,
					"community_href"	=> process_url("./?object=".$_GET["object"]."&action=view&id=".$community_id),
					"community_url"		=> process_url("./?object=community&action=view&id=".$community_id),
				);
				
				return tpl()->parse($_GET["object"]."/create_complete", $replace2);
			}
		}
	
		$replace = array(
			"form_action"		=> "./?object=".$_GET["object"]."&action=".$_GET["action"],
			"error_message"		=> _e(),
			"user"				=> $_POST["user"],
			"title"				=> $_POST["title"],
		);
		
		return tpl()->parse($_GET["object"]."/create", $replace);
	}
	
	function view(){
		if (empty($_GET["id"])) {
			return _e(t("id emty"));
		}
		
		$community_info = db()->query_fetch("SELECT * FROM ".db('community')." WHERE id=".intval($_GET["id"]));
		
		if(empty($community_info)){
			return _e(t("No search community"));
		}
		
		$join_link = "./?object=".$_GET["object"]."&action=join&id=".$community_info["id"];
		$post_link = "";
		
		if (!empty($this->USER_ID)) {
			$OBJ_FRIENDS = main()->init_class("friends");
			$joined = $OBJ_FRIENDS->_is_a_friend($this->USER_ID, $community_info["user_id"]);
			
			if($joined){
				$join_link = "";
				$post_link = "./?object=blog&action=add_post";
			}
		}
		
		$community_name = user($community_info["user_id"],array("nick"));

		$sql		= "SELECT user_name,poster_id,id,title,add_date,text FROM ".db('blog_posts')." WHERE user_id=".$community_info["user_id"]." AND active='1'";
		$order_sql	= " ORDER BY add_date DESC";
		list($add_sql, $pages, $total) = common()->divide_pages($sql);
		$Q = db()->query($sql.$order_sql.$add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$replace2 = array(
				"title"		=> _prepare_html($A["title"]),
				"user_name"	=> _prepare_html($A["user_name"]),
				"user_link"	=> "./?object=user_profile&action=show&id=".$A["poster_id"],
				"add_date"	=> _format_date($A["add_date"], "long"),
				"text"		=> _prepare_html($A["text"]),
				"full_link"	=> "./?object=blog&action=show_single_post&id=".$A["id"],
			);
			
			$item .= tpl()->parse($_GET["object"]."/view_item", $replace2);
		}
		
		$replace = array(
			"name"		=> _prepare_html($community_name["nick"]),
			"title"		=> _prepare_html($community_info["title"]),
			"join_link"	=> $join_link,
			"items"		=> $item,
			"pages"		=> $pages,
		);
		
		return tpl()->parse($_GET["object"]."/view", $replace);
	}
	
	function info(){
		if (empty($this->USER_ID)) {
			return _error_need_login();
		}
		$_GET["id"] = intval($_GET["id"]);
		
		$community_info = db()->query_fetch("SELECT * FROM ".db('community')." WHERE id=".$_GET["id"]);
		
		if($community_info["owner_id"] !== $this->USER_ID){
			return _e(t("only for owner"));
		}

		if(isset($_POST["go"])){
			//save data
			
			db()->UPDATE("community", array(
				"about" 	=> _es($_POST["about"]),
				"title" 	=> _es($_POST["title"]),
			), "id=".$_GET["id"]);

			$OBJ_INTERESTS = main()->init_class("interests");
			$OBJ_INTERESTS->manage($community_info["user_id"]);
		
			js_redirect("./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$_GET["id"]);
		}
		
		$interests_info = db()->query_fetch("SELECT * FROM ".db('interests')." WHERE user_id=".$community_info["user_id"]);
		
		$user_info = user($community_info["user_id"],array("login"));
		
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$_GET["id"],
			"name"			=> _prepare_html($user_info["login"]),
			"title"			=> _prepare_html($community_info["title"]),
			"about"			=> _prepare_html($community_info["about"]),
			"keywords"		=> trim(str_replace(";", "\r\n", $interests_info["keywords"])),
		);
		
		return tpl()->parse($_GET["object"]."/info", $replace);
	}
	
	function manage(){
		if (empty($this->USER_ID)) {
			return _error_need_login();
		}
		
		$Q = db()->query("SELECT id,user_id,title FROM ".db('community')." WHERE owner_id=".$this->USER_ID);
		while ($A = db()->fetch_assoc($Q)) {
			$community[$A["id"]] = $A;
			$users_ids[$A["user_id"]] = $A["user_id"];
		}
		
		$user_info = user($users_ids, array("login"));
			
		if(!empty($community)){
			foreach ((array)$community as $key => $community_data){
				$replace2 = array(
					"bg_class"		=> !(++$i % 2) ? "bg1" : "bg2",				
					"name"			=> $user_info[$community_data["user_id"]]["login"],
					"title"			=> $community_data["title"],
					"info_link"		=> "./?object=".$_GET["object"]."&action=info&id=".$key,
					"settings_link"	=> "./?object=".$_GET["object"]."&action=settings&id=".$key,
					"members_link"	=> "./?object=".$_GET["object"]."&action=members&id=".$key,
				);
				$items .= tpl()->parse($_GET["object"]."/manage_item", $replace2);
			}
		}

		$replace = array(
			"items"			=> $items,
		);
		
		return tpl()->parse($_GET["object"]."/manage", $replace);
	}
	
	function settings(){
		if (empty($this->USER_ID)) {
			return _error_need_login();
		}
		
		$_GET["id"] = intval($_GET["id"]);
		
		$community_info = db()->query_fetch("SELECT * FROM ".db('community')." WHERE id=".$_GET["id"]);
		
		if($community_info["owner_id"] !== $this->USER_ID){
			return _e(t("only for owner"));
		}
		
		if(isset($_POST["go"])){
			db()->UPDATE("community", array(
				"membership"		=> $_POST["membership"], 
				"nonmember_posting"	=> $_POST["nonmember_posting"], 
				"postlevel"			=> $_POST["postlevel"],
				"moderated"			=> $_POST["moderated"],
				"adult"				=> $_POST["adult"],
			), "id=".$_GET["id"]);
			
			js_redirect("./?object=".$_GET["object"]."&action=manage");
		}
		
		$replace = array(
			"membership"		=> $community_info["membership"],
			"nonmember_posting"	=> $community_info["nonmember_posting"],
			"postlevel"			=> $community_info["postlevel"],
			"moderated"			=> $community_info["moderated"],
			"adult"				=> $community_info["adult"],
			"form_action"		=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$_GET["id"],
		);
		
		return tpl()->parse($_GET["object"]."/settings", $replace);
	}
	
	function members(){
		if (empty($this->USER_ID)) {
			return _error_need_login();
		}

		$_GET["id"] = intval($_GET["id"]);
		
		$community_info = db()->query_fetch("SELECT * FROM ".db('community')." WHERE id=".$_GET["id"]);
		
		if($community_info["owner_id"] !== $this->USER_ID){
			return _e(t("only for owner"));
		}
		
		$OBJ_FRIENDS = main()->init_class("friends");
		$friends_ids = $OBJ_FRIENDS->_get_user_friends_ids($community_info["user_id"]);
		
		foreach ((array)$friends_ids as $id){
			if($OBJ_FRIENDS->_is_a_friend($id, $community_info["user_id"])){
				$members_ids[$id] = $id;
			}
		}
		
		if(isset($_POST["go"])){
			$Q = db()->query("SELECT * FROM ".db('community_users')." WHERE community_id=".$_GET["id"]."/* AND member = '1'*/");
			while ($A = db()->fetch_assoc($Q)) {
				$user_in_community[$A["user_id"]] = $A;
			}
		
			foreach ((array)$_POST["setting"] as $user_id => $setting){
				$data = array(
					"user_id"		=> $user_id,
					"community_id"	=> $_GET["id"],
					"member"		=> $setting["member"]?"1":"0",
					"post"			=> $setting["post"]?"1":"0",
					"maintainer"	=> $setting["maintainer"]?"1":"0",
				);
				
				if($community_info["moderated"] == "1"){
					$data["unmoderated"]	= $setting["unmoderated"]?"1":"0";
					$data["moderator"]		= $setting["moderator"]?"1":"0";
				}
			
				if(isset($user_in_community[$user_id])){
					db()->UPDATE("community_users", $data, "id=".$user_in_community[$user_id]["id"]);
				}else{
					db()->INSERT("community_users", $data);
				}
			}
			
			// add new member
			$OBJ_HANDSHAKE = $OBJ_FRIENDS->_load_sub_module("friends_handshake");
			foreach ((array)$_POST["new_member"] as $member){
				if(!empty($member["name"])){
					$new_member = db()->query_fetch("SELECT id,nick FROM ".db('user')." WHERE nick='"._es($member["name"])."' AND group != ".$this->COMMUNITY_GROUP);
					
					if(empty($new_member)){
						_re(t("user with nick '".$member["name"]."' not found!"));
					}else{
						if(isset($members_ids[$new_member["id"]])){
							_re(t("user with nick '".$member["name"]."' is in community user list!"));
						}else{
							// add member to community
							$OBJ_FRIENDS->_add_user_friends_ids($community_info["user_id"], $new_member["id"]);

							$data = array(
								"user_id"		=> $new_member["id"],
								"community_id"	=> $_GET["id"],
								"member"		=> $member["member"]?"1":"0",
								"post"			=> $member["post"]?"1":"0",
								"unmoderated"	=> $member["unmoderated"]?"1":"0",
								"moderator"		=> $member["moderator"]?"1":"0",
								"maintainer"	=> $member["maintainer"]?"1":"0",
							);
							
							if(isset($user_in_community[$new_member["id"]])){
								db()->UPDATE("community_users", $data, "id=".$user_in_community[$new_member["id"]]["id"]);
							}else{
								db()->INSERT("community_users", $data);
							}
							
							// send handshake request
							$OBJ_HANDSHAKE->_add_handshake_request($community_info["user_id"], $new_member["id"], "Join to community ".$community_info["title"]);
							
							common()->set_notice("request sent");
						}
					}
				}
			}
			
		}
		$members_name = user($members_ids, array("nick","name"));
		
		$Q = db()->query("SELECT * FROM ".db('community_users')." WHERE community_id = ".$_GET["id"]." AND user_id IN(".implode(",",array_keys($members_name)).")");
		while ($A = db()->fetch_assoc($Q)) {
			$member_info[$A["user_id"]] = $A;
		}
		
		foreach ((array)$members_name as $key => $member){
			$replace2 = array(
				"bg_class"		=> !(++$j % 2) ? "bg1" : "bg2",				
				"name"			=> _display_name($member),
				"member_id"		=> $member["id"],
				"moderated"		=> $community_info["moderated"],
				"member"		=> $member_info[$key]["member"],
				"post"			=> $member_info[$key]["post"],
				"unmoderated"	=> $member_info[$key]["unmoderated"],
				"moderator"		=> $member_info[$key]["moderator"],
				"maintainer"	=> $member_info[$key]["maintainer"],
			);
			
			$items .= tpl()->parse($_GET["object"]."/members_item", $replace2);
		}
		
		for($i=0; $i<5;$i++){
			$replace3 = array(
				"bg_class"		=> !(++$j % 2) ? "bg1" : "bg2",				
				"moderated"		=> $community_info["moderated"],
				"id"			=> $i,
			);
			
			$new_members .= tpl()->parse($_GET["object"]."/member_new_item", $replace3);
			
		}
		
		$replace = array(
			"form_action"			=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$_GET["id"],
			"items"					=> $items,
			"new_members"			=> $new_members,
			"moderated"				=> $community_info["moderated"],
			"error_message"			=> _e(),
			"notices"				=> common()->show_notices(),
			"handshake_link"		=> "./?object=".$_GET["object"]."&action=handshake_request&id=".$_GET["id"],
			"handshake_to_you_link"	=> "./?object=".$_GET["object"]."&action=handshake_request_to_you&id=".$_GET["id"],
		);
		
		return tpl()->parse($_GET["object"]."/members", $replace);
	}
	
	function handshake_request(){
		if (empty($this->USER_ID)) {
			return _error_need_login();
		}
		$_GET["id"] = intval($_GET["id"]);
		
		$community_info = db()->query_fetch("SELECT * FROM ".db('community')." WHERE id=".$_GET["id"]);
		
		if($community_info["owner_id"] !== $this->USER_ID){
			return _e(t("only for owner"));
		}

		$OBJ_FRIENDS = main()->init_class("friends");	
		return $OBJ_FRIENDS->all_handshake_request($community_info["user_id"], $_GET["object"]);
	}
	
	function handshake_request_to_you(){
		if (empty($this->USER_ID)) {
			return _error_need_login();
		}
		
		$_GET["id"] = intval($_GET["id"]);
		
		$community_info = db()->query_fetch("SELECT * FROM ".db('community')." WHERE id=".$_GET["id"]);
		
		if($community_info["owner_id"] !== $this->USER_ID){
			return _e(t("only for owner"));
		}

		$OBJ_FRIENDS = main()->init_class("friends");	
		return $OBJ_FRIENDS->all_handshake_request_to_you($community_info["user_id"], $_GET["object"]);
	}
	
	function delete_handshake(){
		if (empty($this->USER_ID)) {
			return _error_need_login();
		}
		$_GET["id"] = intval($_GET["id"]);
		
		if (!empty($_GET["id"])) {
			$handshake = db()->query_fetch("SELECT * FROM ".db('handshake')." WHERE id=".$_GET["id"]);
		}
		if (!empty($handshake)) {
			$community = db()->query_fetch("SELECT * FROM ".db('community')." WHERE user_id=".$handshake["sender"]);
		}
		if ($this->USER_ID != $community["owner_id"]){
			return _e(t("Only for owner"));
		}
		
		if (!empty($_GET["id"])){
			db()->query("DELETE FROM ".db('handshake')." WHERE id=".$_GET["id"]);
		}
		return js_redirect("./?object=".$_GET["object"]."&action=handshake_request&id=".$community["id"]);
	}
	
	function group_handshake_delete(){
		if (empty($this->USER_ID)) {
			return _error_need_login();
		}
		
		foreach ((array)$_POST["item"] as $value_id){
			if (!empty($value_id)) {
				$handshake = db()->query_fetch("SELECT * FROM ".db('handshake')." WHERE id=".$value_id);
			}
			if (!empty($handshake)) {
				$community = db()->query_fetch("SELECT * FROM ".db('community')." WHERE user_id=".$handshake["sender"]);
			}
			if ($this->USER_ID != $community["owner_id"]){
				return _e("Only for owner");
			}
		
			if (!empty($value_id)){
				db()->query("DELETE FROM ".db('handshake')." WHERE id=".$value_id);
			}
		}
		return js_redirect("./?object=".$_GET["object"]."&action=handshake_request&id=".$community["id"]);
	}
	
	function accept_handshake(){
		if (empty($this->USER_ID)) {
			return _error_need_login();
		}
		$_GET["id"] = intval($_GET["id"]);

		if (!empty($_GET["id"])) {
			$handshake = db()->query_fetch("SELECT * FROM ".db('handshake')." WHERE id=".$_GET["id"]);
		}
		if (!empty($handshake)) {
			$community = db()->query_fetch("SELECT * FROM ".db('community')." WHERE user_id=".$handshake["receiver"]);
		}
		if ($this->USER_ID != $community["owner_id"]){
			return _e(t("Only for owner"));
		}

		if(!empty($handshake["id"])){
			
			// Check if such user exists
			$target_user_info = user($handshake["sender"], "short", array("WHERE" => array("active" => 1)));
			if (empty($target_user_info)) {
				return _e(t("No such user"));
			}
			// Check if user is already a friend
			$OBJ_FRIENDS = main()->init_class("friends");
			$IS_A_FRIEND = $OBJ_FRIENDS->_is_a_friend($community["user_id"], $handshake["sender"]);
			if ($IS_A_FRIEND) {
				return _e(t("This user is already in your community list"));
			}
			// Do add user
			$OBJ_FRIENDS->_add_user_friends_ids($community["user_id"], $handshake["sender"]);
	
			// update status
			db()->UPDATE("handshake", array(
				"action_date"	=> time(),
				"status"		=> 3,
			), "id=".intval($handshake["id"]));		

			$Q = db()->query("SELECT * FROM ".db('handshake')." WHERE id!=".$handshake["id"]." AND sender=".$handshake["sender"]." AND receiver=".$handshake["receiver"]);
			while ($A = db()->fetch_assoc($Q)) {
				db()->query("DELETE FROM ".db('handshake')." WHERE id=".$A["id"]);
			}
		}
		return js_redirect("./?object=".$_GET["object"]."&action=handshake_request_to_you&id=".$community["id"]);
	}
	
	function decline_handshake(){
		if (empty($this->USER_ID)) {
			return _error_need_login();
		}
		$_GET["id"] = intval($_GET["id"]);

		if (!empty($_GET["id"])) {
			$handshake = db()->query_fetch("SELECT * FROM ".db('handshake')." WHERE id=".$_GET["id"]);
		}
		if (!empty($handshake)) {
			$community = db()->query_fetch("SELECT * FROM ".db('community')." WHERE user_id=".$handshake["receiver"]);
		}
		if ($this->USER_ID != $community["owner_id"]){
			return _e(t("Only for owner"));
		}
		
		if(!empty($handshake["id"])){
			// Check if user is already a friend
			$OBJ_FRIENDS = main()->init_class("friends");
			$IS_A_FRIEND = $OBJ_FRIENDS->_is_a_friend($community["user_id"], $handshake["sender"]);
			if ($IS_A_FRIEND) {
				return _e(t("This user is already in your community list"));
			}

			if (!empty($_GET["id"])) {
				$target_user_info = user($handshake["sender"], "short", array("WHERE" => array("active" => 1)));
			}
			if (empty($target_user_info["id"])) {
				return _e(t("No such user"));
			}
			
			db()->UPDATE("handshake", array(
				"action_date"	=> time(),
				"status"		=> 2,
			), "id=".intval($handshake["id"]));
			
			$Q = db()->query("SELECT * FROM ".db('handshake')." WHERE id!=".$handshake["id"]." AND sender=".$handshake["sender"]." AND receiver=".$handshake["receiver"]);
			while ($A = db()->fetch_assoc($Q)) {
				db()->query("DELETE FROM ".db('handshake')." WHERE id=".$A["id"]);
			}
		}
		
		return js_redirect("./?object=".$_GET["object"]."&action=handshake_request_to_you&id=".$community["id"]);
	}
	
	function group_handshake_action(){
		if (empty($this->USER_ID)) {
			return _error_need_login();
		}

		if((isset($_POST["accept"])) and (!empty($_POST["item"]))){
			foreach ((array)$_POST["item"] as $value_id){
				if (!empty($value_id)) {
					$handshake = db()->query_fetch("SELECT * FROM ".db('handshake')." WHERE id=".$value_id);
				}
				if (!empty($handshake)) {
					$community = db()->query_fetch("SELECT * FROM ".db('community')." WHERE user_id=".$handshake["receiver"]);
				}
				if ($this->USER_ID != $community["owner_id"]){
					return _e(t("Only for owner"));
				}

				if(!empty($handshake["id"])){
					// Check if such user exists
					$target_user_info = user($handshake["sender"], "short", array("WHERE" => array("active" => 1)));
					if (empty($target_user_info)) {
						return _e(t("No such user"));
					}
					// Check if user is already a friend
					$OBJ_FRIENDS = main()->init_class("friends");
					$IS_A_FRIEND = $OBJ_FRIENDS->_is_a_friend($community["user_id"], $handshake["sender"]);
					
					if ($IS_A_FRIEND) {
						db()->UPDATE("handshake", array(
							"action_date"	=> time(),
							"status"		=> 3,
						), "id=".intval($handshake["id"]));
					}else{
					// Do add user
					$this->PARENT_OBJ->_add_user_friends_ids($community["user_id"], $handshake["sender"]);
			
					// update status
						db()->UPDATE("handshake", array(
							"action_date"	=> time(),
							"status"		=> 3,
						), "id=".intval($handshake["id"]));
					}
				}
			}
		}

		if((isset($_POST["decline"])) and (!empty($_POST["item"]))){
			foreach ((array)$_POST["item"] as $value_id){
				if (!empty($value_id)) {
					$handshake = db()->query_fetch("SELECT * FROM ".db('handshake')." WHERE id=".$value_id);
				}
				if (!empty($handshake)) {
					$community = db()->query_fetch("SELECT * FROM ".db('community')." WHERE user_id=".$handshake["receiver"]);
				}
				if ($this->USER_ID != $community["owner_id"]){
					return _e(t("Only for owner!"));
				}

				if(!empty($handshake["id"])){
					$target_user_info = user($handshake["sender"], "short", array("WHERE" => array("active" => 1)));

					if (empty($target_user_info["id"])) {
						return _e(t("No such user"));
					}

					db()->UPDATE("handshake", array(
						"action_date"	=> time(),
						"status"		=> 2,
					), "id=".intval($handshake["id"]));	
				}
			}
		}
		return js_redirect("./?object=".$_GET["object"]."&action=handshake_request_to_you&id=".$community["id"]);
	}
	
	function join(){
		if (empty($this->USER_ID)) {
			return _error_need_login();
		}
		
		if (empty($_GET["id"])) {
			return _e(t("id emty"));
		}
		
		$community_info = db()->query_fetch("SELECT * FROM ".db('community')." WHERE id=".intval($_GET["id"]));
		
		if(empty($community_info)){
			return _e(t("No search community"));
		}
		
		if($community_info["membership"] == "open"){
			// join to community
			$OBJ_FRIENDS = main()->init_class("friends");
			$OBJ_FRIENDS->_add_user_friends_ids($this->USER_ID, $community_info["user_id"]);
			$OBJ_FRIENDS->_add_user_friends_ids($community_info["user_id"], $this->USER_ID);
		}
		
		if($community_info["membership"] == "moderated"){
			// send handshake request
			$OBJ_FRIENDS = main()->init_class("friends");
			$OBJ_HANDSHAKE = $OBJ_FRIENDS->_load_sub_module("friends_handshake");
			$OBJ_HANDSHAKE->_add_handshake_request($this->USER_ID, $community_info["user_id"], "Please join me to community '".$community_info["title"]."'");
			$OBJ_FRIENDS->_add_user_friends_ids($this->USER_ID, $community_info["user_id"]);
			
			common()->set_notice("request sent");
			return common()->show_notices();
		}
		
		if($community_info["membership"] == "closed"){
			return _e(t("Closed Membership. Nobody can join the community"));
		}
	}
	
	function moderate(){
		if (empty($this->USER_ID)) {
			return _error_need_login();
		}
		
		$moderated_community = $this->_get_moderated_community_for_user($this->USER_ID);
		
		if(!empty($moderated_community)){
			$community_id = array_flip($moderated_community);
			
			$Q = db()->query("SELECT id,user_id,title,user_name,poster_id,active FROM ".db('blog_posts')." WHERE user_id IN(".implode(",", $moderated_community).") AND active = '0'");
			while ($A = db()->fetch_assoc($Q)) {
				$posts[$A["id"]] = $A;
			}
			
			$Q = db()->query("SELECT id,nick FROM ".db('user')." WHERE id IN(".implode(",", $moderated_community).")");
			while ($A = db()->fetch_assoc($Q)) {
				$community_name[$A["id"]] = $A["nick"];
			}
			
			foreach ((array)$posts as $id => $post){
			
				$replace2 = array(
					"id"				=> $post["id"],
					"community_name"	=> $community_name[$post["user_id"]],
					"community_link"	=> "./?object=".$_GET["object"]."&action=view&id=".$community_id[$post["user_id"]],
					"post_title"		=> _prepare_html($post["title"]),
					"post_link"			=> "./?object=blog&action=show_single_post&id=".$post["id"],
					"user_name"			=> $post["user_name"],
					"user_link"			=> "./?object=user_profile&action=show&id=".$post["poster_id"],
					"active"			=> intval($post["active"]),
					"active_link"		=> "./?object=".$_GET["object"]."&action=activate_post&id=".$post["id"],
					"delete_link"		=> "./?object=".$_GET["object"]."&action=delete_post&id=".$post["id"],
				);
				
				$items .= tpl()->parse($_GET["object"]."/moderate_item", $replace2);
			}
		}
		
		$replace = array(
			"items"		=> $items,
		);
		
		return tpl()->parse($_GET["object"]."/moderate_main", $replace);
	}
	
	function activate_post() {
		if (empty($this->USER_ID)) {
			return _error_need_login();
		}
	
		if (!empty($_GET["id"])) {
			$post_info = db()->query_fetch("SELECT * FROM ".db('blog_posts')." WHERE id=".intval($_GET["id"]));
		}
		// Do change activity status
		if (!empty($post_info)) {
			db()->UPDATE("blog_posts", array("active" => (int)!$post_info["active"]), "id=".intval($post_info["id"]));
		}

		// Return user back
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo ($post_info["active"] ? 0 : 1);
		} else {
			return js_redirect("./?object=".$_GET["object"]);
		}
	}
	
	function delete_post(){
		if (empty($this->USER_ID)) {
			return _error_need_login();
		}

		if(empty($_GET["id"])){
			return _e(t("no id"));
		}
		
		db()->query("DELETE FROM ".db('blog_posts')." WHERE id=".intval($_GET["id"])." LIMIT 1");
		return js_redirect("./?object=".$_GET["object"]."&action=moderate");
	}
	
	function _get_moderated_community_for_user($user_id){

		$Q = db()->query("SELECT id,user_id FROM ".db('community')." WHERE active='1' AND moderated = '1'");
		while ($A = db()->fetch_assoc($Q)) {
			$community[$A["id"]] = $A["user_id"];
		}
		
		if(!empty($community)){
			$Q = db()->query("SELECT community_id FROM ".db('community_users')." WHERE community_id IN(".implode(",", array_keys($community)).") AND user_id = ".$user_id." AND moderator = '1'");
			while ($A = db()->fetch_assoc($Q)) {
				$moderated_community[$A["community_id"]] = $community[$A["community_id"]];
			}
		}
	
		return $moderated_community;
	}

	function _prepare_keyword_for_url ($cur_word = "") {
		return rawurlencode(str_replace(" ", "+", $cur_word));
	}
	
	function _get_community_with_allow_posting_for_user($user_id){
	
		$Q = db()->query("SELECT community_id FROM ".db('community_users')." WHERE user_id=".$user_id);
		while ($A = db()->fetch_assoc($Q)) {
			$community_ids[$A["community_id"]] = $A["community_id"];
		}
	
		$community_names = $this->_get_community_names($community_ids);
	
		return $community_names;
	}
	
	function _get_community_names($community_ids = array()){
		if(!empty($community_ids)){
			$Q = db()->query("SELECT id,user_id FROM ".db('community')." WHERE id IN(".implode(",", $community_ids).") AND active = '1'");
			while ($A = db()->fetch_assoc($Q)) {
				$community_user_id[$A["id"]] = $A["user_id"];
			}
		}
		
		if(!empty($community_user_id)){
			$Q = db()->query("SELECT id,nick FROM ".db('user')." WHERE id IN(".implode(",", $community_user_id).")");
			while ($A = db()->fetch_assoc($Q)) {
				$names[$A["id"]] = $A["nick"];
			}
		}

		if(!empty($community_user_id)){
			foreach ((array)$community_user_id as $id => $user_id){
				$community_name[$id] = $names[$user_id];
			}
		}
		
		return $community_name;
	}
	
	/**
	* Quick menu auto create
	*/
	function _quick_menu () {
		$menu = array(
			array(
				"name"	=> "Manage my communities",
				"url"	=> "./?object=".$_GET["object"]."&action=manage",
			),
			array(
				"name"	=> "Create new community",
				"url"	=> "./?object=".$_GET["object"]."&action=create",
			),
			array(
				"name"	=> "Add post to community",
				"url"	=> "./?object=blog&action=add_post",
			),
			array(
				"name"	=> "Moderate community",
				"url"	=> "./?object=community&action=moderate",
			),
		);
		return $menu;	
	}
	
	/**
	* account suggests
	*/
	function _account_suggests(){
	
		// for moderators
		$moderated_community = $this->_get_moderated_community_for_user($this->USER_ID);
	
		if(!empty($moderated_community)){
			$Q = db()->query("SELECT id FROM ".db('blog_posts')." WHERE user_id IN(".implode(",", $moderated_community).") AND active = '0'");
			while ($A = db()->fetch_assoc($Q)) {
				$posts[$A["id"]] = $A;
			}
		}

		if(!empty($posts)){
			$suggests[] = 'Exist '.count($posts).' unmoderated post in communiuty. For moderate click <a href="./?object=community&action=moderate">here</a>';
		}
		
		// for owner, handshake for join to community
		$Q = db()->query("SELECT user_id FROM ".db('community')." WHERE owner_id=".$this->USER_ID);
		while ($A = db()->fetch_assoc($Q)) {
			$community[$A["user_id"]] = $A["user_id"];
		}
		
		if(!empty($community)){
			$Q = db()->query("SELECT id FROM ".db('handshake')." WHERE receiver IN(".implode(",", $community).") AND status = '1'");
			while ($A = db()->fetch_assoc($Q)) {
				$handshakes[$A["id"]] = $A["id"];
			}
		}
		
		if(!empty($handshakes)){
			$suggests[] = 'Exist '.count($handshakes).' handshake to your community. Please check';		
		}
	
		return $suggests;
	}
}
