<?php

/**
* Log ssh viewer
*/
class yf_log_ssh_viewer {

	/**
	*/
	function show () {
/*
		// Do save filter if needed
		if (!empty($_GET["server"])) {
			$_POST["server"] = $_GET["server"];
			$this->clear_filter(1);
			$this->save_filter(1);
		}

		// Prepare pager
		$sql = "SELECT * FROM ".db('log_ssh_action')."";
		$filter_sql = $this->USE_FILTER ? $this->_create_filter_sql() : "";
		$sql .= strlen($filter_sql) ? " WHERE 1=1 ". $filter_sql : " ORDER BY microtime DESC ";
		list($add_sql, $pages, $total) = common()->divide_pages($sql);
		$records = db()->query_fetch_all($sql. $add_sql);

		foreach ((array)$records as $result) {
			$server_ids[] 	= $result["server_id"];
			$user_ids[]		= $result["user_id"];
		}		
		$user_ids	= array_unique((array)$user_ids);

		// Find user_infos and server infos
		$user_info 		= user($user_ids, array("nick"));

		foreach ((array)$records as $result) {
			$replace2 = array(
				"server"		=> $result["server_id"],
				"date"			=> _format_date($result["microtime"], "long"),
				"ip"			=> $result["ip"],
				"comment"		=> _prepare_html($result["comment"]),
				"get_object"	=> $result["get_object"],
				"get_action"	=> $result["get_action"],
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

	/**
	*/
	function _hook_widget__ssh_actions_log ($params = array()) {
// TODO
	}
}
