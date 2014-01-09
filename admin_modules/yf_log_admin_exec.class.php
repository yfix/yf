<?php

class yf_log_admin_exec{

	/**
	*
	*/
	function show() {
/*
		$sql = "SELECT * FROM ".db('log_admin_exec')."";
		$filter_sql = $this->USE_FILTER ? $this->_create_filter_sql() : "";
		$sql .= strlen($filter_sql) ? " WHERE 1 ". $filter_sql : " ORDER BY date ASC ";
		list($add_sql, $pages, $total) = common()->divide_pages($sql);
		$Q = db()->query($sql. $add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$admin_info[$A["admin_id"]] = db()->query_fetch("SELECT login FROM ".db('admin')." WHERE id = '".$A["admin_id"]."' ORDER BY id ASC ");
			$items[] = array(
				"ip_country"	=> strtolower(common()->_get_country_by_ip($A["ip"])),
				"id"			=> $A["id"],
				"date"			=> _format_date($A["date"], full),
				"ip"			=> _prepare_html($A["ip"]),
				"admin"			=> _prepare_html($admin_info[$A["admin_id"]]["login"]),
				"query_string"	=> _prepare_html($A["query_string"]),
				"request_uri"	=> _prepare_html($A["request_uri"]),
				"referer"		=> _prepare_html($A["referer"]),
				"exec_time"		=> _prepare_html($A["exec_time"]),

//				"view_url"		=> "./?object=".$_GET["object"]."&action=view&id=".$A["id"],
			);
		}
		$replace = array(
			"form_action"		=> "./?object=".$_GET["object"]."&action=".$_GET["action"]. ($_GET["id"] ? "&id=".$_GET["id"] : ""),
			"error"				=> _e(),
			"items"				=> $items,
			"pages"				=> $pages,
			"total"				=> $total,
			"prune_action"		=> "./?object=".$_GET["object"]."&action=prune",
		);
		return tpl()->parse($_GET["object"]."/main", $replace);
*/
	}

	function _hook_widget__admin_access_log ($params = array()) {
// TODO
	}

}