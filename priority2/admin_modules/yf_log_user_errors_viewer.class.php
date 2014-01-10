<?php

/**
* Display core errors
*/
class yf_log_user_errors_viewer {

	/**
	*/
	function show () {
/*
		// Prepare pager
		$sql = "SELECT * FROM ".db('log_user_errors')."";
		$filter_sql = $this->USE_FILTER ? $this->_create_filter_sql() : "";
		$sql .= strlen($filter_sql) ? " WHERE 1=1 ". $filter_sql : " ORDER BY date DESC ";
		list($add_sql, $pages, $total) = common()->divide_pages($sql);
		// Get records from db
		$Q = db()->query($sql.$add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$records[] = $A;
			$users_ids[$A["user_id"]] = $A["user_id"];
		}
		// Get users infos
		if (!empty($users_ids)) {
			$Q = db()->query("SELECT id,group,login,nick,email,photo_verified FROM ".db('user')." WHERE id IN(".implode(",", $users_ids).")");
			while ($A = db()->fetch_assoc($Q)) $users_infos[$A["id"]] = $A;
		}
		// Process data
		foreach ((array)$records as $A) {
			$cur_user_info = array();
			if (!empty($A["user_id"])) {
				$cur_user_info = $users_infos[$A["user_id"]];
			}
			// Process template
			$replace2 = array(
				"bg_class"		=> $i++ % 2 ? "bg1" : "bg2",
				"id"			=> intval($A["id"]),
				"message"		=> nl2br(_prepare_html(trim($A["error_text"]))),
				"date"			=> _format_date($A["date"], "long"),
				"user_id"		=> intval($cur_user_info["id"]),
				"account_link"	=> $cur_user_info["id"] ? ($A["is_admin"] ? "./?object=admin&action=edit&id=".$cur_user_info["id"] : "./?object=account&user_id=".$cur_user_info["id"]) : "",
				"user_name"		=> _prepare_html(_display_name($cur_user_info)),
				"user_nick"		=> _prepare_html($cur_user_info["nick"]),
				"user_avatar"	=> _show_avatar($A["user_id"], $cur_user_info, 1),
				"group_name"	=> t($A["is_admin"] ? $this->_admin_groups[$A["user_group"]] : $this->_account_types[$user_info["group"]]),
				"member_url"	=> "./?object=account&action=show&user_id=".$cur_user_info["id"],
				"user_email"	=> _prepare_html($cur_user_info["email"]),
				"details_link"	=> "./?object=".$_GET["object"]."&action=view&id=".$A["id"],
				"delete_link"	=> "./?object=".$_GET["object"]."&action=delete&id=".$A["id"],
				"by_user_link"	=> $A["user_id"] ? "./?object=".$_GET["object"]."&action=show_for_user&id=".$A["user_id"] : "",
				"by_ip_link"	=> "./?object=".$_GET["object"]."&action=show_for_ip&id=".$A["ip"],
				"query_string"	=> _prepare_html($A["query_string"]),
				"request_uri"	=> _prepare_html($A["request_uri"]),
				"log_ip"		=> _prepare_html($A["ip"]),
				"log_browser"	=> _prepare_html($A["user_agent"]),
				"log_referer"	=> _prepare_html($A["referer"]),
				"section_name"	=> $A["is_admin"] ? "ADMIN" : "USER",
			);
			$items .= tpl()->parse($_GET["object"]."/item", $replace2);
		}
		// Prepare teplate
		$replace = array(
			"items"			=> $items,
			"pages"			=> $pages,
			"total"			=> intval($total),
			"prune_action"	=> "./?object=".$_GET["object"]."&action=prune",
			"form_action"	=> "./?object=".$_GET["object"]."&action=multi_delete",
			"top"			=> "./?object=".$_GET["object"]."&action=top_of_errors",
		);
		return tpl()->parse($_GET["object"]."/main", $replace);
*/
	}

	function _hook_widget__user_errors_log ($params = array()) {
// TODO
	}
}
