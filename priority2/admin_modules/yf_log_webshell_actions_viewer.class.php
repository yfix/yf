<?php

/**
* Log authentification fails viewer
*/
class yf_log_webshell_actions_viewer {

	/**
	*/
	function show () {
/*
		// Prepare pager
		$sql = "SELECT * FROM ".db('log_webshell_action')."";
		$filter_sql = $this->USE_FILTER ? $this->_create_filter_sql() : "";
		$sql .= strlen($filter_sql) ? " WHERE 1=1 ". $filter_sql : " ORDER BY microtime DESC ";
		list($add_sql, $pages, $total) = common()->divide_pages($sql);
		$records = db()->query_fetch_all($sql. $add_sql);

		foreach ((array)$records as $result) {
			$server_ids[] 	= $result["server_id"];
			$user_ids[]		= $result["user_id"];
		}		
		$server_ids	= array_unique((array)$server_ids);
		$user_ids	= array_unique((array)$user_ids);

		// Find user_infos and server infos
		$user_info 		= user($user_ids, array("nick"));
		$server_info	= _server_info($server_ids);

		foreach ((array)$records as $result) {
			$replace2 = array(
				"server"		=> $server_info[$result["server_id"]]["name"],
				"user"			=> $user_info[$result["user_id"]]["nick"],
				"date"			=> _format_date($result["microtime"], "long"),
				"action"		=> _prepare_html($result["action"]),
				"details_link"	=> "./?object=".$_GET["object"]."&action=view&id=".floatval($result["microtime"]),
			);
			$items .= tpl()->parse($_GET["object"]."/item", $replace2);
		}

		// Prepare template
		$replace = array(
			"total"			=> $total,
			"pages"			=> $pages,
			"items"			=> $items,
		);
		return tpl()->parse($_GET["object"]."/main", $replace);
*/
	}

	function _hook_widget__webshell_log ($params = array()) {
// TODO
	}
}
