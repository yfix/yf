<?php

//-----------------------------------------------------------------------------
// Module to check directory links
class yf_site_link_checker {

	var	$_links_pattern = "/(href|src)[\s\t]{0,3}?=[\s\t]{0,3}?[\"\']{0,1}?(##LINK_URL##)[\"\']{0,1}?/ims";

	//-----------------------------------------------------------------------------
	// Constructor
	function _init() {
		$this->_link_statuses	= array(
			0 => "New", 
			1 => "Waiting", 
			2 => "Active", 
			3 => "Updated", 
			4 => "Suspended", 
			5 => "Outdated"
		);
		// Try to get info about sites vars
		$this->_sites_info = main()->init_class("sites_info", "classes/");
	}

	//-----------------------------------------------------------------------------
	// Default method
	function show () {
		// Process link checking
		if (!empty($_POST["go"])) {
			// Limit checking time
			set_time_limit(3600);
			// Update all user's records where "link_url" is empty
			if (!empty($_POST["auto_update_db"])) {
				db()->query("UPDATE ".db('links_links')." SET status=4 WHERE link_url=''");
			}
			// Max number of links to process
			$LIMIT_LINKS_NUM = intval($_POST["limit_links_num"]);
			// Sites info
			$SITES_INFO = $this->_sites_info->info;
			$num_our_sites	= count($SITES_INFO);
			foreach ((array)$SITES_INFO as $site_id => $cur_site_info) {
				if (substr($cur_site_info["WEB_PATH"], -1) == "/") {
					$cur_site_info["WEB_PATH"] = substr($cur_site_info["WEB_PATH"], 0, -1);
				}
				$SITES_PATTERNS[$site_id] = str_replace("##LINK_URL##", preg_quote($cur_site_info["WEB_PATH"], "/"), $this->_links_pattern);
			}
			$_POST["link_id_1"] = intval($_POST["link_id_1"]);
			if (!empty($_POST["link_id_1"])) {
				$add_sql .= " AND id >= ".intval($_POST["link_id_1"])." \r\n";
			}
			$_POST["link_id_2"] = intval($_POST["link_id_2"]);
			if (!empty($_POST["link_id_2"])) {
				$add_sql .= " AND id <= ".intval($_POST["link_id_2"])." \r\n";
			}
			if (!empty($_POST["only_status"])) {
				$add_sql .= " AND status = ".intval($_POST["only_status"])." ";
			}
			$Q = db()->query("SELECT * FROM ".db('links_links')." WHERE 1=1 ".$add_sql.(!empty($_POST["limit_links_num"]) ? " LIMIT ".$_POST["limit_links_num"] : ""));
			$total_links_to_check = db()->num_rows($Q);
			$links_counter = 0;
			// Process records
			while ($A = db()->fetch_assoc($Q)) {
				$links_array[$A["id"]] = $A;
			}
			// Try to get users infos
			if (!empty($links_array)) {
				foreach ((array)$links_array as $item_info) {
					$links_users_ids[$item_info["user_id"]] = $item_info["user_id"];
				}
				if (!empty($links_users_ids)) {
					$Q = db()->query("SELECT id,name FROM ".db('links_users')." WHERE id IN(".implode(",", $links_users_ids).")");
					while ($A = db()->fetch_assoc($Q)) $links_users_infos[$A["id"]] = $A;
				}
			}
			// Process links
			foreach ((array)$links_array as $item_info) {
				$links				= array();
				$user_links_array	= array();
				$total_result		= 1;
				$sites_items		= "";
				// Get urls to check
				if (!empty($item_info["link_url"])) {
					$user_links_array = explode(" ", trim($item_info["link_url"]));
				} else {
					$total_result = 0;
				}
  				$check_results = array();
				// Process urls
				foreach ((array)$user_links_array as $link_num => $link_url) {
					// Check if URL is empty
					if (empty($link_url)) {
						$total_result = 0;
						continue;
					}
					$links_counter++;
					// Download page to check
					$page_to_check = $this->_get_remote_page($link_url);
					// TODO: !!! Only during testing !!!
					//$page_to_check = !(++$c%2) ? file_get_contents(INCLUDE_PATH."test.html") : "";
					// Link site header
					$replace3 = array(
						"link_url"	=> _prepare_html($link_url),
						"page_size"	=> strlen($page_to_check),
					);
					$sites_items .= tpl()->parse($_GET["object"]."/result_site_header", $replace3);
					// Check all links to our sites existance
					foreach ((array)$SITES_PATTERNS as $site_id => $cur_pattern) {
						// Check if no in link sites list
						if (empty($item_info["site".$site_id])) {
							continue;
						}
						$cur_result = preg_match($cur_pattern, $page_to_check);
						// Process template
						$replace2 = array(
							"our_site_id"	=> $site_id,
							"our_site_name"	=> $SITES_INFO[$site_id]["name"],
							"link_url"		=> _prepare_html($link_url),
							"page_size"		=> strlen($page_to_check),
							"check_result"	=> $cur_result ? "<b style='color:green;'>Found</b>" : "<b style='color:red;'>Not Found</b>",
						);
						// Try to find (if not found yet)
						if (!$check_results[$site_id]) {
							$check_results[$site_id] = $cur_result;
						}
						$sites_items .= tpl()->parse($_GET["object"]."/result_site_item", $replace2);
					}
					$is_all_found = (!empty($check_results) && array_sum($check_results) == count($check_results)) ? 1 : 0;
					// Set total check result for the current user
					if ($total_result) {
						$total_result = $is_all_found;
					}
				}
				// Force "not found" result
				if (empty($sites_items)) {
					$total_result = 0;
				}
				// Update user and set new status
				if (!$total_result && !empty($_POST["auto_update_db"])) {
					db()->query("UPDATE ".db('links_links')." SET status=4 WHERE id=".intval($item_info["id"]));
				}
				// Display result
				$replace2 = array(
					"bg_class"			=> $i++ % 2 ? "bg1" : "bg2",
					"user_id"			=> $item_info["user_id"],
					"user_name"			=> _prepare_html($links_users_infos[$item_info["user_id"]]["name"]),
					"user_profile_link"	=> "./?object=links&action=edit_user&id=".$item_info["user_id"],
					"user_account_link"	=> "./?object=links&action=account&user_id=".$item_info["user_id"],
					"edit_link"			=> "./?object=links&action=admin_edit_link&id=".$item_info["id"],
					"link_title"		=> _prepare_html($item_info["title"]),
					"link_desc"			=> _prepare_html($item_info["description"]),
					"target_url"		=> $item_info["url"],
					"link_url"			=> $item_info["link_url"],
					"sites_items"		=> $sites_items,
					"link_approved"		=> intval((bool) $total_result),
					"total_result"		=> $total_result,
				);
				$items .= tpl()->parse($_GET["object"]."/result_item", $replace2);
				// Check max number of links to check
				if (!empty($LIMIT_LINKS_NUM) && $links_counter >= $LIMIT_LINKS_NUM) {
					break;
				}
			}
			// Show result
			$replace = array(
				"items"		=> $items,
				"num_links" => $total_links_to_check,
				"pages"		=> $pages,
			);
			$body = tpl()->parse($_GET["object"]."/result_main", $replace);
		// Start form
		} else {
			// Count info about users with links
			list($num_users, $min_user_id, $max_user_id) = db()->query_fetch("SELECT COUNT(id) AS `0`, MIN(id) AS 1, MAX(id) AS 2 FROM ".db('links_users')."");
			$replace = array(
				"form_action"	=> "./?object=".$_GET["object"]."&action=".$_GET["action"],
				"status_box"	=> common()->select_box("only_status", array_merge(array("" => "All"), $this->_link_statuses), 2, null, 2, "", false),
				"num_users"		=> intval($num_users),
				"min_user_id"	=> intval($min_user_id),
				"max_user_id"	=> intval($max_user_id),
			);
			$body = tpl()->parse($_GET["object"]."/start_form", $replace);
		}
		return $body;
	}

	//-----------------------------------------------------------------------------
	// Get remote page (using CURL)
	function _get_remote_page ($page_url = "") {
		$page_to_check = "";
		$page_url	= str_replace(" ", "%20", trim($page_url));
		$user_agent = "Mozilla/4.0 (compatible; MSIE 6.01; Windows NT 5.1)";
		$referer	= $page_url;
		if ($ch = curl_init()) {
			curl_setopt($ch, CURLOPT_URL, $page_url);
			curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
			curl_setopt($ch, CURLOPT_REFERER, $referer);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$page_to_check = curl_exec ($ch);
			curl_close ($ch);
		}
		return $page_to_check;
	}
}
