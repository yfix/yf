<?php

//-----------------------------------------------------------------------------
// Display registered users
class yf_users {

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
		$sql = search_user(array("WHERE" => array("active" => 1), "ORDER BY" => "add_date", "LIMIT" => -1), "full", true);
/*
		// Connect pager
		$sql = "SELECT * FROM `".db('user')."` WHERE `active`='1'";
		$order_by_sql = " ORDER BY `add_date` DESC";
*/
		$url = "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=all";
		list($add_sql, $pages, $total) = common()->divide_pages($sql, $url);
		// Get users from db
		$Q = db()->query($sql. $order_by_sql. $add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$users[$A["id"]] = $A;
		}
		// Process records
		foreach ((array)$users as $A) {
			$replace2 = array(
				"bg_class"		=> $i++ % 2 ? "bg1" : "bg2",
				"id"			=> intval($A["id"]),
				"name"			=> _prepare_html(_display_name($A)),
				"login"			=> _prepare_html($A["login"]),
				"email"			=> _prepare_html($A["email"]),
				"add_date"		=> _format_date($A["add_date"]),
				"profile_link"	=> _profile_link($A["id"]),
				"avatar"		=> _show_avatar($A["id"], $A, 1),
				"account_type"	=> _prepare_html($this->_account_types[$A["group"]]),
			);
			$items .= tpl()->parse($_GET["object"]."/item", $replace2);
		}
		// Process template
		$replace = array(
			"items"				=> $items,
			"pages"				=> $pages,
			"total"				=> intval($total),
		);
		return tpl()->parse($_GET["object"]."/main", $replace);
	}

	/**
	* Hook for the site_map
	*/
	function _site_map_items ($OBJ = false) {
// Temporary off
return false;
		if (!is_object($OBJ)) {
			return false;
		}
		// Connect pager
		$sql = "SELECT `id` FROM `".db('user')."` WHERE `active`='1'";
		$order_by_sql = " ORDER BY `add_date` DESC";
		$url = "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=all";
		list($add_sql, $pages, $total, $_dummy, $total_pages) = common()->divide_pages($sql, $url);
		// Process pages
		for ($i = 1; $i <= $total_pages; $i++) {
			$OBJ->_store_item(array(
				"url"	=> "./?object=users&action=show&id=all&page=".$i,
			));
		}
		// Get users from db
		$Q = db()->query($sql.$order_by_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$OBJ->_store_item(array(
				"url"	=> "./?object=users&action=show&id=".$A["id"],
			));
		}
		return true;
	}

	/**
	* Home page hook
	*/
	function _for_home_page($num = 5){
		
		$Q = db()->query("SELECT * FROM `".db('user')."` WHERE `group`=2 AND `active`=1 ORDER BY `add_date` DESC LIMIT ".intval($num*3));
		while ($A = db()->fetch_assoc($Q)) {
				
			if (!_avatar_exists($A["id"], 0)) continue;
			if ($count >= $num) break;
			$count++;
			
			$replace2 = array(
				"nick" 				=> _display_name($A["nick"]),
				"user_id"			=> $A["id"],
				"user_link"			=> "./?object=user_profile&action=show&id=".$A["id"],
				"user_avatar_src"	=> trim(_show_avatar($A["id"], $A["nick"], 0, 0, 1)),
			);			
			$items .= tpl()->parse(__CLASS__."/for_home_page_item", $replace2);		
		}
		
		if(empty($items)) return;
		
		$replace = array(
			"items"	=> $items,
		);
		return tpl()->parse(__CLASS__."/for_home_page_main", $replace);
	}
	
	/**
	* Widget hook
	*/
	function _widget_last_user ($params = array()) {
		if ($params["describe"]) {
			return array("allow_cache" => 1);
		}
		return $this->_for_home_page(1);
	}
}
