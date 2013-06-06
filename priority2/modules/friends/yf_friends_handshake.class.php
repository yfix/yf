<?php

/**
* Friends utils container
*
* @package		YF
* @author		Yuri Vysotskiy <profy.net@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_friends_handshake {

	/**
	* Framework constructor
	*/
	function _init () {
		// Reference to parent object
		$this->PARENT_OBJ	= module(FRIENDS_CLASS_NAME);
	}

	/**
	* 
	*/
	function request_handshake_form(){
		if (empty($this->PARENT_OBJ->USER_ID)) {
			return _error_need_login();
		}

		$_GET["id"] = intval($_GET["id"]);

		$receiver_info = user($_GET["id"]);
		if (empty($receiver_info["id"])) {
			return _e("No such user in database!");
		}

		if ($receiver_info["id"] == $this->PARENT_OBJ->USER_ID) {
			return _e("You are trying to send handshake to yourself!");
		}

		$replace = array(
			"form_action"	=> "./?object=".FRIENDS_CLASS_NAME."&action=send_request_handshake&id=".$receiver_info["id"],
			"receiver_name"	=> _display_name($receiver_info),
			"captcha_block"	=> $this->PARENT_OBJ->_captcha_block(),
			"error"			=> "",
			"message"		=> "",
		);
		return tpl()->parse(FRIENDS_CLASS_NAME."/send_handshake_form", $replace);
	}
	
	/**
	* 
	*/
	function send_request_handshake(){
		if (empty($this->PARENT_OBJ->USER_ID)) {
			return _error_need_login();
		}

		$_GET["id"] = intval($_GET["id"]);

		$receiver_info = user($_GET["id"]);
		if (empty($receiver_info["id"])) {
			return _e("No such user in database!");
		}

		if ($receiver_info["id"] == $this->PARENT_OBJ->USER_ID) {
			return _e("You are trying to send handshake to yourself!");
		}

		// Validate captcha
		$this->PARENT_OBJ->CAPTCHA->check("captcha");

		if (!common()->_error_exists()) {

			$this->_add_handshake_request(intval($this->PARENT_OBJ->_user_info["id"]), intval($receiver_info["id"]), $_POST["message"]);
		
			$replace = array(
				"receiver_name"	=> _display_name($receiver_info),
			);
			return tpl()->parse(FRIENDS_CLASS_NAME."/send_handshake_complete", $replace);

		}else{
			$replace = array(
				"form_action"	=> "./?object=".FRIENDS_CLASS_NAME."&action=send_request_handshake&id=".$receiver_info["id"],
				"error"			=> _e(),
				"captcha_block"	=> $this->PARENT_OBJ->_captcha_block(),
				"receiver_name"	=> _display_name($receiver_info),
				"message"		=> $_POST["message"],
			);
			return tpl()->parse(FRIENDS_CLASS_NAME."/send_handshake_form", $replace);
		}
	}
	
	function _add_handshake_request($sender, $receiver, $message){
		db()->INSERT("handshake", array(
			"sender"	=> $sender,
			"receiver"	=> $receiver,
			"text"		=> _es($message),
			"add_date"	=> time(),
			"status"	=> 1,
		));
	}

	/**
	* 
	*/
	function all_handshake_request($sender = 0, $object = ""){
		
		$this->PARENT_OBJ->USER_ID = $sender?$sender:$this->PARENT_OBJ->USER_ID;
		$object = $object?$object:FRIENDS_CLASS_NAME;
	
		if (empty($this->PARENT_OBJ->USER_ID)) {
			return _error_need_login();
		}

		$sql = "SELECT * FROM `".db('handshake')."` WHERE `sender`=".$this->PARENT_OBJ->USER_ID;
		list($add_sql, $pages, $total) = common()->divide_pages($sql);

		$Q = db()->query($sql.$add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$handshake[] = $A;
			$users_id[$A["receiver"]] = $A["receiver"];
		}

		if (!empty($users_id)) {
			foreach ((array)user($users_id) as $A) {
				$user_name[$A["id"]] = $A;
			}
		}

		if(!empty($handshake)) {
			foreach ((array)$handshake as $A){
				$replace2 = array(
					"bg_class"			=> !(++$i % 2) ? "bg1" : "bg2",
					"id"				=> $A["id"],
					"name"				=> _display_name($user_name[$A["receiver"]]),
					"user_link"			=> _profile_link($A["receiver"]),
					"description"		=> _prepare_html($A["text"]),
					"date"				=> _format_date($A["add_date"], "long"),
					"action_date"		=> _format_date($A["action_date"], "long"),
					"status"			=> $this->PARENT_OBJ->status[$A["status"]],
					"delete_link"		=> "./?object=".$object."&action=delete_handshake&id=".$A["id"],
				);
				$items .= tpl()->parse(FRIENDS_CLASS_NAME."/sender_handshake_item", $replace2);
			}
		}
		// Process template
		$replace = array(
			"items"					=> $items,
			"delete_form_action"	=> "./?object=".FRIENDS_CLASS_NAME."&action=group_handshake_delete",
			"pages"					=> $pages,
			"group_handshake_action"=> "./?object=".$object."&action=group_handshake_delete",

		);
		return tpl()->parse(FRIENDS_CLASS_NAME."/sender_handshake_main", $replace);
	}

	/**
	* 
	*/
	function all_handshake_request_to_you($receiver = 0, $object = ""){
		$this->PARENT_OBJ->USER_ID = $receiver?$receiver:$this->PARENT_OBJ->USER_ID;
		$object = $object?$object:FRIENDS_CLASS_NAME;
	
		if (empty($this->PARENT_OBJ->USER_ID)) {
			return _error_need_login();
		}

		$sql = "SELECT * FROM `".db('handshake')."` WHERE `receiver`=".$this->PARENT_OBJ->USER_ID." ORDER BY `status`";
		list($add_sql, $pages, $total) = common()->divide_pages($sql);

		$Q = db()->query($sql.$add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$handshake[] = $A;
			$users_id[$A["sender"]] = $A["sender"];
		}

		if (!empty($users_id)) {
			$user_name = user($users_id);
		}

		if(!empty($handshake)) {
			foreach ((array)$handshake as $A){
				$replace2 = array(
					"bg_class"		=> !(++$i % 2) ? "bg1" : "bg2",
					"name"			=> _display_name($user_name[$A["sender"]]),
					"id"			=> $A["id"],
					"user_link"		=> _profile_link($A["sender"]),
					"description"	=> _prepare_html($A["text"]),
					"date"			=> _format_date($A["add_date"], "long"),
					"action_date"	=> _format_date($A["action_date"], "long"),
					"status"		=> $this->PARENT_OBJ->status[$A["status"]],
					"accept_link"	=> "./?object=".$object."&action=accept_handshake&id=".$A["id"],
					"decline_link"	=> "./?object=".$object."&action=decline_handshake&id=".$A["id"],
				);
				$items .= tpl()->parse(FRIENDS_CLASS_NAME."/receiver_handshake_item", $replace2);
			}
		}
		// Process template
		$replace = array(
			"group_form_action"	=> "./?object=".$object."&action=group_handshake_action",
			"items"					=> $items,		
			"pages"					=> $pages,
		);
		return tpl()->parse(FRIENDS_CLASS_NAME."/receiver_handshake_main", $replace);
	}

	/**
	* 
	*/
	function accept_handshake(){
		if (empty($this->PARENT_OBJ->USER_ID)) {
			return _error_need_login();
		}
		$_GET["id"] = intval($_GET["id"]);

		if (!empty($_GET["id"])) {
			$handshake = db()->query_fetch("SELECT * FROM `".db('handshake')."` WHERE `id`=".$_GET["id"]);
		}

		if($this->PARENT_OBJ->USER_ID != $handshake["receiver"]){
			return _e("Only for owner!");
		}
		
		if(!empty($handshake["id"])){
			// Check if such user exists
			$target_user_info = user($handshake["sender"], "short", array("WHERE" => array("active" => 1)));
			if (empty($target_user_info)) {
				return _e("No such user!");
			}
			
			// update status
			db()->UPDATE("handshake", array(
				"action_date"	=> time(),
				"status"		=> 3,
			), "`id`=".intval($handshake["id"]));			
			
			// Check if user is already a friend
			$IS_A_FRIEND = $this->PARENT_OBJ->_is_a_friend($this->PARENT_OBJ->USER_ID, $handshake["sender"]);
			if ($IS_A_FRIEND) {
				return _e("This user is already in your friends list");
			}
			// Do add user
			$this->PARENT_OBJ->_add_user_friends_ids($this->PARENT_OBJ->USER_ID, $handshake["sender"]);
	
		}
		return js_redirect("./?object=".FRIENDS_CLASS_NAME."&action=all_handshake_request_to_you");
	}

	/**
	* 
	*/
	function decline_handshake(){
		if (empty($this->PARENT_OBJ->USER_ID)) {
			return _error_need_login();
		}
		$_GET["id"] = intval($_GET["id"]);

		if (!empty($_GET["id"])) {
			$handshake = db()->query_fetch("SELECT * FROM `".db('handshake')."` WHERE `id`=".$_GET["id"]);
		}

		if($this->PARENT_OBJ->USER_ID != $handshake["receiver"]){
			return _e("Only for owner!");
		}
		
		if(!empty($handshake["id"])){

			if (!empty($_GET["id"])) {
				$target_user_info = user($handshake["sender"], "full", array("WHERE" => array("active" => 1)));
			}
			if (empty($target_user_info["id"])) {
				return _e("No such user!");
			}
			// Check if user is already a friend
			$IS_A_FRIEND = $this->PARENT_OBJ->_is_a_friend($this->PARENT_OBJ->USER_ID, $handshake["sender"]);
			if ($IS_A_FRIEND) {
				$this->PARENT_OBJ->_del_user_friends_ids($this->PARENT_OBJ->USER_ID, $target_user_info);
			}
			
			db()->UPDATE("handshake", array(
				"action_date"	=> time(),
				"status"		=> 2,
			), "`id`=".intval($handshake["id"]));			
		}
		return js_redirect("./?object=".FRIENDS_CLASS_NAME."&action=all_handshake_request_to_you");
	}

	/**
	* 
	*/
	function group_handshake_action(){
		if (empty($this->PARENT_OBJ->USER_ID)) {
			return _error_need_login();
		}

		if((isset($_POST["accept"])) and (!empty($_POST["item"]))){
			foreach ((array)$_POST["item"] as $value_id){
				if (!empty($value_id)) {
					$handshake = db()->query_fetch("SELECT * FROM `".db('handshake')."` WHERE `id`=".$value_id);
				}
				if($this->PARENT_OBJ->USER_ID != $handshake["receiver"]){
					return _e("Only for owner!");
				}

				if(!empty($handshake["id"])){
					// Check if such user exists
					$target_user_info = user($handshake["sender"], "full", array("WHERE" => array("active" => 1)));
					if (empty($target_user_info)) {
						return _e("No such user!");
					}
					// Check if user is already a friend
					$IS_A_FRIEND = $this->PARENT_OBJ->_is_a_friend($this->PARENT_OBJ->USER_ID, $handshake["sender"]);
					if ($IS_A_FRIEND) {
						db()->UPDATE("handshake", array(
							"action_date"	=> time(),
							"status"		=> 3,
						), "`id`=".intval($handshake["id"]));
					}else{
					// Do add user
					$this->PARENT_OBJ->_add_user_friends_ids($this->PARENT_OBJ->USER_ID, $handshake["sender"]);
			
					// update status
						db()->UPDATE("handshake", array(
							"action_date"	=> time(),
							"status"		=> 3,
						), "`id`=".intval($handshake["id"]));
					}
				}
			}
		}

		if((isset($_POST["decline"])) and (!empty($_POST["item"]))){
			foreach ((array)$_POST["item"] as $value_id){
				if (!empty($value_id)) {
					$handshake = db()->query_fetch("SELECT * FROM `".db('handshake')."` WHERE `id`=".$value_id);
				}
				if($this->PARENT_OBJ->USER_ID != $handshake["receiver"]){
					return _e("Only for owner!");
				}

				if(!empty($handshake["id"])){
					$target_user_info = user($handshake["sender"], "full", array("WHERE" => array("active" => 1)));

					if (empty($target_user_info["id"])) {
						return _e("No such user!");
					}
					// Check if user is already a friend
					db()->UPDATE("handshake", array(
						"action_date"	=> time(),
						"status"		=> 2,
					), "`id`=".intval($handshake["id"]));	
				}
			}
		}

		return js_redirect("./?object=".FRIENDS_CLASS_NAME."&action=all_handshake_request_to_you");
	}

	/**
	* 
	*/
	function delete_handshake(){
		if (empty($this->PARENT_OBJ->USER_ID)) {
			return _error_need_login();
		}
		$_GET["id"] = intval($_GET["id"]);

		if (!empty($_GET["id"])) {
			$handshake = db()->query_fetch("SELECT * FROM `".db('handshake')."` WHERE `id`=".$_GET["id"]);
		}

		if ($this->PARENT_OBJ->USER_ID != $handshake["sender"]){
			return _e("Only for owner!");
		}
		if (!empty($_GET["id"])){
			db()->query("DELETE FROM `".db('handshake')."` WHERE `id`=".$_GET["id"]);
		}
		return js_redirect("./?object=".FRIENDS_CLASS_NAME."&action=all_handshake_request");
	}

	/**
	* 
	*/
	function group_handshake_delete(){
		if (empty($this->PARENT_OBJ->USER_ID)) {
			return _error_need_login();
		}
		foreach ((array)$_POST["item"] as $value_id){
			if (!empty($value_id)) {
				$handshake = db()->query_fetch("SELECT * FROM `".db('handshake')."` WHERE `id`=".$value_id);
			}

			if ($this->PARENT_OBJ->USER_ID != $handshake["sender"]){
				return _e("Only for owner!");
			}
			if (!empty($value_id)){
				db()->query("DELETE FROM `".db('handshake')."` WHERE `id`=".$value_id);
			}
		}
		return js_redirect("./?object=".FRIENDS_CLASS_NAME."&action=all_handshake_request");
	}
	
	/**
	* 
	*/
	function _account_suggests(){
		// Check handshakes
		$Q = db()->query("SELECT * FROM `".db('handshake')."` WHERE `receiver`=".$this->USER_ID." AND `status`=1");
		while ($A = db()->fetch_assoc($Q)) {
			$count_handshake++;
		}
		
		if ($count_handshake > 0) {
			$suggests[]	= '{t(You have)} '.$count_handshake.' {t(handshake request with status waiting. Click)} <a href="./?object=friends&action=all_handshake_request_to_you">{t(here)}</a>';
		}
		
		return $suggests;
	}
}
