<?php

//-----------------------------------------------------------------------------
// Display registered users
class yf_users_search {

	//-----------------------------------------------------------------------------
	// Framework constructor
	function _init () {
		// Get user account type
		$this->_account_types	= main()->get_data("account_types");
		// Get user levels
		$this->_user_levels		= main()->get_data("user_levels");
	}

	//-----------------------------------------------------------------------------
	// Default method
	function show () {
	
		$_GET["id"] = 1;

		isset($_POST["user_search_name"])?$_SESSION["user_search_name"] = $_POST["user_search_name"]:$_POST["user_search_name"] = $_SESSION["user_search_name"];
		isset($_POST["user_search_sex"])?$_SESSION["user_search_sex"] = $_POST["user_search_sex"]:$_POST["user_search_sex"] = $_SESSION["user_search_sex"];
		isset($_POST["user_search_interests"])?$_SESSION["user_search_interests"] = $_POST["user_search_interests"]:$_POST["user_search_interests"] = $_SESSION["user_search_interests"];
	
			
		if (!empty($_POST["user_search_name"])){
			$nick_sql = " AND nick LIKE '%".$_POST["user_search_name"]."%'";
		}
		
		if (!empty($_POST["user_search_sex"])){
			$sex_sql = " AND sex='".$_POST["user_search_sex"]."'";
		}
		
		if (!empty($_POST["user_search_interests"])){
			$Q = db()->query("SELECT * FROM ".db('interests')." WHERE keywords LIKE '%".$_POST["user_search_interests"]."%'");
			while ($A = db()->fetch_assoc($Q)) $interest_user_id[$A["user_id"]] = $A["keywords"];			
			
			$interest_user_id?$interest_sql = " AND id IN(".implode(",",array_keys($interest_user_id)).")":$interest_sql = " AND id = 0";			
		}else{
			$Q = db()->query("SELECT * FROM ".db('interests')."");
			while ($A = db()->fetch_assoc($Q)) $interest_user_id[$A["user_id"]] = $A["keywords"];			
		}
		
		$sql = "SELECT * FROM ".db('user')." WHERE active='1'".$nick_sql.$sex_sql.$interest_sql;
		
	
		$order_by_sql = " ORDER BY add_date DESC";
		list($add_sql, $pages, $total) = common()->divide_pages($sql);
		// Get users from db
		$Q = db()->query($sql. $order_by_sql. $add_sql);
		while ($A = db()->fetch_assoc($Q)) $users[$A["id"]] = $A;
		// Process records
		foreach ((array)$users as $A) {
			
			$user_interests = $interest_user_id[$A["id"]];
			
			$user_interests = str_replace(";", " ", $user_interests);
		
			$replace2 = array(
				"bg_class"			=> $i++ % 2 ? "bg1" : "bg2",
				"id"				=> intval($A["id"]),
				"name"				=> _prepare_html(_display_name($A)),
				"login"				=> _prepare_html($A["login"]),
				"email"				=> _prepare_html($A["email"]),
				"sex"				=> _prepare_html($A["sex"]),
				"interest"			=> $user_interests,
				"add_date"			=> _format_date($A["add_date"]),
				"profile_link"		=> _profile_link($A["id"]),
				"avatar"			=> _show_avatar($A["id"], $A, 1),
				"account_type"		=> _prepare_html($this->_account_types[$A["group"]]),
			);
			$items .= tpl()->parse($_GET["object"]."/item", $replace2);
		}
		
		
		$sex_box = array(
			"0"	=> "all",
			"Male"	=> "Male",
			"Female"	=> "Female",		
		);
		
		$sex = common()->select_box("user_search_sex", $sex_box, $_POST["user_search_sex"], false, 2, "", false);		
		
		// Process template
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"],
			"items"			=> $items,
			"pages"			=> $pages,
			"total"			=> intval($total),
			"search"		=> $_POST["user_search_name"],
			"sex"			=> $sex	,
			"interests"		=> $_POST["user_search_interests"],
		);
		return tpl()->parse($_GET["object"]."/main", $replace);
	}

}
