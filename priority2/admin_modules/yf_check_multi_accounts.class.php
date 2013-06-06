<?php

/**
* Multi-accounts checker administration panel
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_check_multi_accounts {

	/** @var bool Filter on/off */
	var $USE_FILTER				= true;
	/** @var array @conf_skip */
	var $_skip_users_ids = array(
//		1,
	);
	/** @var array */
	var $_known_ban_items = array(
		"ban_ads",
		"ban_email",
		"ban_reviews",
		"ban_images",
		"ban_forum",
		"ban_comments",
		"ban_blog",
		"ban_bad_contact",
		"ban_reput",
	);
	/** @var bool */
	var $HIDE_SINGLE_IPS		= 1;
	/** @var bool */
	var $HIDE_NON_MATCHED_USERS	= 1;
	/** @var bool */
	var $SHOW_ONLY_COOKIE_MATCH	= 0;
	/** @var array IPs to ignore */
	var $IGNORE_IPS = array(
/*
		"205.188.116.*",
		"195.93.21.*",
		"152.163.100.*",
		"64.12.116.*",
*/
	);
	/** @var int */
	var $LIMIT_MATCHED_USERS	= 15;

	/**
	* Constructor
	*/
	function yf_check_multi_accounts() {
		ini_set("memory_limit", "192M");
	}

	/**
	* Framework constructor
	*/
	function _init() {
		define("CHECK_MULTI_ACCOUNTS_CLASS", "check_multi_accounts");
		// Get current account types
		$this->_account_types	= main()->get_data("account_types");
		// Array of boxes
		$this->_boxes = array(
		);
		// Prepare filter data
		if ($this->USE_FILTER) {
			$this->_prepare_filter_data();
		}
	}

	/**
	* Default method
	*/
	function show ($force_not_hide = false) {
		if ($force_not_hide) {
			$this->HIDE_SINGLE_IPS = false;
		}
		$ips_array = array();
		// Connect pager
		$sql = "SELECT * FROM `".db('check_multi_accounts')."` WHERE 1=1 ";
		$filter_sql = $this->USE_FILTER ? $this->_create_filter_sql() : "";
		if ($this->HIDE_NON_MATCHED_USERS) {
			$sql .= " AND (`ip_match` = '1' OR `cookie_match` = '1') ";
		}
		if ($this->SHOW_ONLY_COOKIE_MATCH) {
			$sql .= " AND `cookie_match` = '1' ";
		}
		if (!empty($this->IGNORE_IPS)) {
			foreach ((array)$this->IGNORE_IPS as $_ip_mask) {
				$sql .= " AND `matching_ips` NOT LIKE '"._es(str_replace("*", "%", $_ip_mask))."'";
			}
		}
		$sql .= strlen($filter_sql) ? $filter_sql : " ORDER BY `user_id` ASC";
		list($add_sql, $pages, $total) = common()->divide_pages($sql);
		// Get data from db
		$Q = db()->query($sql.$add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			// Skip some users if needed
			if (!empty($this->_skip_users_ids) && in_array($A["user_id"], (array)$this->_skip_users_ids)) {
				continue;
			}
			// Convert IPs list into array
			$_tmp_matching_ips = explode(",", $A["matching_ips"]);
			natsort($_tmp_matching_ips);
			foreach ((array)$_tmp_matching_ips as $_cur_ip) {
				// Check for correct IP address
				if (empty($_cur_ip) || !preg_match("/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\$/", $_cur_ip)) {
					continue;
				}
				// Skip IP if needed
				if ($this->_is_ip_to_skip($_cur_ip)) {
					continue;
				}
				// We store IPs in array for get their info later in bulk mode
				$ips_array[$_cur_ip] = 1;
			}
			// Convert into array
			$A["matching_ips"] = $_tmp_matching_ips;
			// Save for further processing
			$data_array[$A["user_id"]]	= $A;
			$users_ids[$A["user_id"]]	= $A["user_id"];
			// Add cookie matched users (possibly they will not intersect by IP address)
			if (!empty($A["matching_users"])) {
				foreach ((array)explode(",",$A["matching_users"]) as $_user_id) {
					$users_ids[$_user_id] = $_user_id;
				}
			}
		}
		// Get matching users with these IPs
		if (!empty($ips_array)) {
			ksort($ips_array);
			$Q = db()->query("SELECT * FROM `".db('check_multi_ips')."` WHERE `ip` IN('".implode("','",array_keys($ips_array))."')");
			while ($A = db()->fetch_assoc($Q)) {
				$_tmp_matching_users = explode(",",$A["matching_users"]);
				sort($_tmp_matching_users);
				foreach ((array)$_tmp_matching_users as $_cur_user) {
					$_cur_user = intval($_cur_user);
					if (empty($_cur_user)) {
						continue;
					}
					// Skip some users if needed
					if (!empty($this->_skip_users_ids) && in_array($_cur_user, (array)$this->_skip_users_ids)) {
						continue;
					}
					$users_ids[$_cur_user] = $_cur_user;
				}
				// Save for further processing
				$users_by_ips[$A["ip"]]		= $_tmp_matching_users;
				// Cache number of users (to prevent multiple counts)
				$num_users_by_ips[$A["ip"]]	= intval($A["num_m_users"]);
			}
			// Free some memory
			unset($ips_array);
		}
		// Skip empty user ids
		if (isset($users_ids[""])) {
			unset($users_ids[""]);
		}
		// Do get users infos
		$users_infos = array();
		if (!empty($users_ids)) {
			$Q = db()->query("SELECT `id`,`login`,`nick`,`photo_verified`, ".implode(",", $this->_known_ban_items)." FROM `".db('user')."` WHERE `id` IN(".implode(",",$users_ids).")");
			while ($A = db()->fetch_assoc($Q)) {
				$users_infos[$A["id"]] = $A;
			}
		}
		$num_m_ips_by_users	= array();
		$with_cookie_match	= array();
		// Last get num_m_ips for all fetched users
		if (!empty($users_infos)) {
//			$_add_sql = " AND (`cookie_match` = '1' OR `ip_match` = '1') ";
			$Q = db()->query("SELECT `user_id`, `num_m_ips`, `cookie_match`, `ip_match` FROM `".db('check_multi_accounts')."` WHERE `user_id` IN(".implode(",",array_keys($users_infos)).")".$_add_sql);
			while ($A = db()->fetch_assoc($Q)) {
				$num_m_ips_by_users[$A["user_id"]]		= $A["num_m_ips"];
				if ($A["cookie_match"]) {
					$with_cookie_match[$A["user_id"]]	= 1;
				}
				if ($A["ip_match"]) {
					$with_ip_match[$A["user_id"]]		= 1;
				}
			}
		}
		// Process records
		foreach ((array)$data_array as $A) {
			$matching_ips_stpl		= array();
			$intersected_users_ids	= array();
			$cookie_matched_ids		= array();
			$num_m_ips				= intval($num_m_ips_by_users[$A["user_id"]]);
			// Skip non-existed accounts
			if (!isset($users_infos[$A["user_id"]])) {
				continue;
			}
			// Prepare IPs list for template
			foreach ((array)$A["matching_ips"] as $_ip) {
				if ($this->HIDE_SINGLE_IPS && empty($num_users_by_ips[$_ip])) {
					continue;
				}
				// Skip IP if needed
				if ($this->_is_ip_to_skip($_ip)) {
					continue;
				}
				// Prepare array of intersected users for current one
				foreach ((array)$users_by_ips[$_ip] as $_user_id) {
					$intersected_users_ids[$_user_id] = $_user_id;
				}
				// Array for template
				$matching_ips_stpl[$_ip] = array(
					"ip"			=> $_ip,
					"link"			=> "./?object=".CHECK_MULTI_ACCOUNTS_CLASS."&action=show_by_ip&id=".$_ip,
					"num_m_users"	=> intval($num_users_by_ips[$_ip]),
				);
			}
			// Add cookie matched users to the intersected ones
			if (!empty($A["matching_users"])) {
				foreach ((array)explode(",",$A["matching_users"]) as $_user_id) {
					$intersected_users_ids[$_user_id]	= $_user_id;
					$cookie_matched_ids[$_user_id]		= $_user_id;
				}
			}
			$_users_names		= array();
			$_tmp_users_by_ips	= array();
			$num_matching_users = 0;
			// Needed for stpl to show link for all matching users
			$limit_reached		= false;
			// Merge matching users (both by IPs and by Cookies)
			foreach ((array)$intersected_users_ids as $_user_id) {
				// Skip self accounts and non-existed accounts
				if (!isset($users_infos[$_user_id]) || $_user_id == $A["user_id"]) {
					continue;
				}
				// Skip users without cookie match or ip match records
				if (!isset($with_cookie_match[$_user_id]) && !isset($with_ip_match[$_user_id])) {
					continue;
				}
				// Count number of matching users for the current one
				$num_matching_users++;
				// Check if needed to limit number of displaying math
				if ($this->LIMIT_MATCHED_USERS && $num_matching_users > $this->LIMIT_MATCHED_USERS && empty($_POST["user_id"])) {
					$limit_reached = true;
					continue;
				}
				$_user_name = strtolower(_display_name($users_infos[$_user_id]));
				$_users_names[$_user_id] = $_user_name;
				// Prepare users ids array for further sorting by name
				$_prepend_name = "";
				if (isset($cookie_matched_ids[$_user_id])) {
					$_prepend_name = "aaaa_";
				} elseif (isset($with_cookie_match[$_user_id])) {
					$_prepend_name = "aaaaaa_";
				}
				$_tmp_users_by_ips[$_prepend_name.$_user_name] = $_user_id;
			}
			// Move to top users having cookie matches
			foreach ((array)$intersected_users_ids as $_user_id) {
				if (!isset($with_cookie_match[$_user_id])) {
					continue;
				}
				if (isset($cookie_matched_ids[$_user_id])) {
					continue;
				}
				$_user_name = strtolower(_display_name($users_infos[$_user_id]));
				$_users_names[$_user_id] = $_user_name;
				$_tmp_users_by_ips["aaaaaa_".$_user_name] = $_user_id;
			}
			// Move to top matched by cookie with current one
			foreach ((array)$cookie_matched_ids as $_user_id) {
				if (!in_array($_user_id, $_tmp_users_by_ips)) {
					$_user_name = strtolower(_display_name($users_infos[$_user_id]));
					$_users_names[$_user_id] = $_user_name;
					$_tmp_users_by_ips["aaaa_".$_user_name] = $_user_id;
				}
			}
			@ksort($_tmp_users_by_ips);
			$users_by_ips_stpl	= "";
			// Process template for the matching users
			foreach ((array)$_tmp_users_by_ips as $_cur_user_id) {
				$_cur_user_info = $users_infos[$_cur_user_id];
				$_cur_user_name = $_users_names[$_cur_user_id];
				$replace3 = array(
					"id"				=> $_cur_user_id,
					"link"				=> "./?object=".CHECK_MULTI_ACCOUNTS_CLASS."&action=show_by_user&id=".$_cur_user_id,
					"nick"				=> _prepare_html($_cur_user_name),
					"ban_info"			=> $this->_prepare_ban_info($_cur_user_info),
					"profile_link"		=> "./?object=account&user_id=".$_cur_user_id,
					"num_m_ips"			=> intval($num_m_ips_by_users[$_cur_user_id]),
					"has_cookie_match"	=> isset($with_cookie_match[$_cur_user_id]) ? 1 : 0,
					"this_cookie_match"	=> isset($cookie_matched_ids[$_cur_user_id]) ? 1 : 0,
					"ban_popup_link"	=> main()->_execute("manage_auto_ban", "_popup_link", array("user_id" => intval($_cur_user_id), "force_text" => "ban")),
				);
				$users_by_ips_stpl .= tpl()->parse($_GET["object"]."/user_by_ip_item", $replace3);
			}
			// Process template for the current user
			$replace2 = array(
				"bg_class"			=> $i++ % 2 ? "bg1" : "bg2",
				"user_id"			=> $A["user_id"],
				"user_name"			=> _prepare_html(_display_name($users_infos[$A["user_id"]])),
				"avatar"			=> _show_avatar($A["user_id"], $users_infos[$A["user_id"]], 1),
				"profile_link"		=> "./?object=account&user_id=".$A["user_id"],
				"last_update"		=> _format_date($A["last_update"], "long"),
				"matching_ips"		=> $matching_ips_stpl,
				"users_by_ips"		=> $users_by_ips_stpl,
				"ban_info"			=> $this->_prepare_ban_info($users_infos[$A["user_id"]]),
				"account_type"		=> _prepare_html($this->_account_types[$users_infos[$A["user_id"]]["group"]]),
				"num_m_ips"			=> intval($num_m_ips),
				"num_m_users"		=> intval($num_matching_users),
				"has_cookie_match"	=> intval((bool)$A["cookie_match"]),
				"limit_reached"		=> $limit_reached ? 1 : 0,
				"limit_users"		=> intval($this->LIMIT_MATCHED_USERS),
				"show_by_user_link"	=> "./?object=".CHECK_MULTI_ACCOUNTS_CLASS."&action=show_by_user&id=".$A["user_id"],
				"del_by_user_link"	=> "./?object=".CHECK_MULTI_ACCOUNTS_CLASS."&action=del_by_user&id=".$A["user_id"],
			);
			$items .= tpl()->parse($_GET["object"]."/item", $replace2);
		}
		// Process template
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"]."&action=delete",
			"items"			=> $items,
			"pages"			=> $pages,
			"total"			=> intval($total),
			"filter"		=> $this->USE_FILTER ? $this->_show_filter() : "",
		);
		return tpl()->parse($_GET["object"]."/main", $replace);
	}

	/**
	* Display info for selected user
	*/
	function show_by_user () {
		$_POST["user_id"] = $_GET["id"];
		$this->clear_filter(1);
		$this->save_filter(1);
		return $this->show(1);
	}

	/**
	* Display info for selected IP
	*/
	function show_by_ip () {
		$_POST["ip"] = $_GET["id"];
		$this->clear_filter(1);
		$this->save_filter(1);
		return $this->show();
	}

	/**
	* Show links for given user ids (if needed)
	*/
	function _show_links_for_users ($users_ids = array()) {
		$links = array();
		if (empty($users_ids)) {
			return $links;
		}
		$tmp_ids = array();
		// Cleanup users ids
		foreach ((array)$users_ids as $_cur_id) {
			$_cur_id = intval($_cur_id);
			if (empty($_cur_id)) {
				continue;
			}
			$tmp_ids[$_cur_id] = $_cur_id;
		}
		// Return cleaned
		$users_ids = $tmp_ids;
		// Check if left something to process
		if (empty($users_ids)) {
			return $links;
		}
		// Get data from db
		if ($this->HIDE_NON_MATCHED_USERS) {
			$add_sql = " AND (`ip_match` = '1' AND `cookie_match` = '1') ";
		}
		$Q = db()->query("SELECT `user_id` FROM `".db('check_multi_accounts')."` WHERE `user_id` IN(".implode(",",$users_ids).")".$add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$links[$A["user_id"]] = "./?object=".CHECK_MULTI_ACCOUNTS_CLASS."&action=show_by_user&id=".$A["user_id"];
		}
		return $links;
	}

	/**
	* Prepare ban info
	*/
	function _prepare_ban_info ($user_info = array()) {
		$body = array();
		foreach ((array)$this->_known_ban_items as $_field_name) {
			$body[] = $user_info[$_field_name] ? "<span style='color:red;'>X</span>" : "<span style='color:green;'>0</span>";
		}
		return "<b>".implode(" ", $body)."</b>";
	}

	/**
	* Check if this IP is in skip list
	*/
	function _is_ip_to_skip ($ip) {
		$ip = trim($ip);
		if (empty($ip)) {
			return true;
		}
		// Quick stop if ignore list is empty
		if (empty($this->IGNORE_IPS)) {
			return false;
		}
		// Singleton
		if (!isset($this->_IGNORE_IPS_PATTERN)) {
			$this->_IGNORE_IPS_PATTERN = "/^(".implode("|", str_replace(array(".", "*"), array("\.", "[0-9\.]*"), $this->IGNORE_IPS)).")\$/";
		}
		return preg_match($this->_IGNORE_IPS_PATTERN, $ip) ? 1 : 0;
	}

	/**
	* Do delete selected records
	*/
	function delete () {
		$ids_to_delete = array();
		foreach ((array)$_POST["ids"] as $cur_id) {
			$cur_id = intval($cur_id);
			if (empty($cur_id)) {
				continue;
			}
			$ids_to_delete[$cur_id] = $cur_id;
		}
		// Do delete record
		if (!empty($ids_to_delete)) {
// TODO: maybe we just need to set flag "ignore this user in results"
//			db()->query("DELETE FROM `".db('check_multi_accounts')."` WHERE `user_id` IN(".implode(",", $ids_to_delete).")");
		}
		// Return user back
		return js_redirect("./?object=".$_GET["object"]);
	}

	/**
	* Delete this user from all matching users' mult-account records
	*/
	function del_by_user () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return "No id";
		}
		// First get multi-account info about this user
		$info = db()->query_fetch("SELECT * FROM `".db('check_multi_accounts')."` WHERE `user_id` = ".intval($_GET["id"]));
		if (empty($info)) {
			return "Not found in multi-accounts";
		}
		// Direct matches
		$matching_users = explode(",", $info["matching_users"]);
		// Get possible callbacks from db
		$found_users = array();
		$Q = db()->query("SELECT `user_id`, `matching_users` FROM `".db('check_multi_accounts')."` WHERE `matching_users` != ''");
		while ($A = db()->fetch_assoc($Q)) {
			$_tmp = array();
			foreach (explode(",", $A["matching_users"]) as $_id) {
				$_tmp[$_id] = $_id;
			}
			if (isset($_tmp[""])) {
				unset($_tmp[""]);
			}
			$_not_found = true;
			// Main checked user
			if (isset($_tmp[$_GET["id"]])) {
				$_not_found = false;
			}
			// Try to find sub-users
			if ($_not_found) {
				foreach ((array)$matching_users as $_user_id) {
					if (isset($_tmp[$_user_id])) {
						$_not_found = false;
						break;
					}
				}
			}
			// Skip if no matches with main user or sub-users
			if ($_not_found) {
				continue;
			}
			$found_users[$A["user_id"]] = $_tmp;
		}
		if (!empty($found_users)) {
			$ids_to_delete = array_keys($found_users);
		}
		// Now save cleaned records
		foreach ((array)$found_users as $_user_id => $_matches) {
			foreach ((array)$_matches as $_sub_id) {
				if (!in_array($_sub_id, $ids_to_delete)) {
					continue;
				}
				unset($found_users[$_user_id][$_sub_id]);
				unset($_matches[$_sub_id]);
			}
			db()->UPDATE("check_multi_accounts", array(
				"matching_users"	=> _es(!empty($_matches) ? implode(",", $_matches) : ""),
				"cookie_match"		=> 0,
			), "`user_id`=".intval($_user_id));
		}
		// Clean main user record
		db()->UPDATE("check_multi_accounts", array(
			"matching_users"	=> "",
			"cookie_match"		=> 0,
		), "`user_id`=".intval($_GET["id"]));
		// Return user back
		return js_redirect($_SERVER["HTTP_REFERER"], 0);
	}

	//-----------------------------------------------------------------------------
	// Prepare required data for filter
	function _prepare_filter_data () {
		// Filter session array name
		$this->_filter_name	= $_GET["object"]."_filter";
		// Prepare boxes
		$this->_boxes = array_merge((array)$this->_boxes, array(
			"active"		=> 'select_box("active",		$this->_active_statuses,$selected, 0, 2, "", false)',
			"account_type"	=> 'select_box("account_type",	$this->_account_types2,	$selected, 0, 2, "", false)',
			"sort_by"		=> 'select_box("sort_by",		$this->_sort_by,		$selected, 0, 2, "", false)',
			"sort_order"	=> 'select_box("sort_order",	$this->_sort_orders,	$selected, 0, 2, "", false)',
		));
		// Connect common used arrays
		if (file_exists(INCLUDE_PATH."common_code.php")) {
			include (INCLUDE_PATH."common_code.php");
		}
		// Get user account type
		$this->_account_types2[" "]	= t("-- All --");
		foreach ((array)$this->_account_types as $k => $v) {
			$this->_account_types2[$k]	= $v;
		}
		// Sort orders
		$this->_sort_orders = array("DESC" => "Descending", "ASC" => "Ascending");
		// Sort fields
		$this->_sort_by = array(
			"",
			"user_id",
			"last_update",
			"num_m_ips",
			"cookie_match",
			"ip_match",
		);
		// Fields in the filter
		$this->_fields_in_filter = array(
			"nick",
			"user_id",
			"account_type",
			"ip",
			"sort_by",
			"sort_order",
			"cookie_match",
		);
	}

	//-----------------------------------------------------------------------------
	// Generate filter SQL query
	function _create_filter_sql () {
		$SF = &$_SESSION[$this->_filter_name];
		foreach ((array)$SF as $k => $v) $SF[$k] = trim($v);
		// Generate filter for the common fileds
		if ($SF["user_id"])			$sql .= " AND `user_id` = ".intval($SF["user_id"])." \r\n";
		if ($SF["ip"])				$sql .= " AND `matching_ips` LIKE '%"._es($SF["ip"])."%' \r\n";

		if ($SF["account_type"])	$users_sql .= " AND `group` = ".intval($SF["account_type"])." \r\n";
		if (strlen($SF["nick"]))	$users_sql .= " AND `nick` LIKE'"._es($SF["nick"])."' \r\n";
		// Add subquery to users table
		if (!empty($users_sql)) {
			$sql .= " AND `user_id` IN( SELECT `id` FROM `".db('user')."` WHERE 1=1 ".$users_sql.") \r\n";
		}
		if (isset($SF["cookie_match"])) {
			$this->SHOW_ONLY_COOKIE_MATCH = $SF["cookie_match"];
		}
		// Sorting here
		if ($SF["sort_by"])			 	$sql .= " ORDER BY `".$this->_sort_by[$SF["sort_by"]]."` \r\n";
		if ($SF["sort_by"] && strlen($SF["sort_order"])) 	$sql .= " ".$SF["sort_order"]." \r\n";
		return substr($sql, 0, -3);
	}

	//-----------------------------------------------------------------------------
	// Session - based filter
	function _show_filter () {
		$replace = array(
			"save_action"	=> "./?object=".$_GET["object"]."&action=save_filter"._add_get(),
			"clear_url"		=> "./?object=".$_GET["object"]."&action=clear_filter"._add_get(),
		);
		foreach ((array)$this->_fields_in_filter as $name) {
			$replace[$name] = $_SESSION[$this->_filter_name][$name];
		}
		// Process boxes
		foreach ((array)$this->_boxes as $item_name => $v) {
			$replace[$item_name."_box"] = $this->_box($item_name, $_SESSION[$this->_filter_name][$item_name]);
		}
		return tpl()->parse($_GET["object"]."/filter", $replace);
	}

	//-----------------------------------------------------------------------------
	// Filter save method
	function save_filter ($silent = false) {
		// Process featured countries
		if (FEATURED_COUNTRY_SELECT && !empty($_POST["country"]) && substr($_POST["country"], 0, 2) == "f_") {
			$_POST["country"] = substr($_POST["country"], 2);
		}
		if (is_array($this->_fields_in_filter)) {
			foreach ((array)$this->_fields_in_filter as $name) $_SESSION[$this->_filter_name][$name] = $_POST[$name];
		}
		if (!$silent) {
			js_redirect($_SERVER["HTTP_REFERER"]);
		}
	}

	//-----------------------------------------------------------------------------
	// Clear filter
	function clear_filter ($silent = false) {
		if (is_array($_SESSION[$this->_filter_name])) {
			foreach ((array)$_SESSION[$this->_filter_name] as $name) unset($_SESSION[$this->_filter_name]);
		}
		if (!$silent) {
			js_redirect("./?object=".$_GET["object"]._add_get());
		}
	}

	//-----------------------------------------------------------------------------
	// Process custom box
	function _box ($name = "", $selected = "") {
		if (empty($name) || empty($this->_boxes[$name])) return false;
		else return eval("return common()->".$this->_boxes[$name].";");
	}

	/**
	* Try to detect if current user have multi-accounts hack
	*/
	function _do_cron_job () {
		db()->query("SET GLOBAL group_concat_max_len = 60000");

		// ####### Get multi-accounts ##############

		// Prepare temporary table
		$tmp_table_name = db()->_get_unique_tmp_table_name();
		db()->query(
			"CREATE TEMPORARY TABLE `".$tmp_table_name."` ( 
				`user_id`	int(10) unsigned NOT NULL, 
				`ips_list`	longtext NOT NULL default '', 
				`num_ips`	int(10) NOT NULL default '0', 
				PRIMARY KEY (`user_id`)
			)"
		);
		// Collect unique user_ids logged in from more than 1 IPs
		db()->query(
			"INSERT INTO `".$tmp_table_name."` ( 
				`num_ips`
				,`user_id`
				,`ips_list`
			) 
			SELECT 
				COUNT(DISTINCT(`ip`)) AS `multi_ips`
				, `user_id`
				, CAST(GROUP_CONCAT(DISTINCT `ip` ORDER BY `ip` ASC) AS CHAR) AS `ips_list`
			FROM `".db('log_auth')."` 
			WHERE `user_id` IN (SELECT `id` FROM `".db('user')."`)
			GROUP BY `user_id` 
			HAVING `multi_ips` > 1
			ORDER BY `multi_ips` DESC"
		);
		// Create missed records
		db()->query(
			"INSERT IGNORE INTO `".db('check_multi_accounts')."` ( 
				`user_id`
				,`matching_ips`
				,`num_m_ips`
			) 
			SELECT 
				`user_id`
				, `ips_list` 
				, `num_ips` 
			FROM `".$tmp_table_name."` 
			WHERE `user_id` NOT IN(
				SELECT `user_id` FROM `".db('check_multi_accounts')."`
			)"
		);
		// Update cache table
		db()->query(
			"UPDATE `".db('check_multi_accounts')."` AS `t1`
					, `".$tmp_table_name."` AS `tmp` 
			SET `t1`.`matching_ips` = `tmp`.`ips_list`
				, `t1`.`num_m_ips` = `tmp`.`num_ips`
			WHERE `tmp`.`user_id` = `t1`.`user_id`"
		);
		// Drop temp table
		db()->query("DROP TEMPORARY TABLE `".$tmp_table_name."`");

		// ####### Get multi-IPs ##############

		// Prepare temporary table
		$tmp_table_name = db()->_get_unique_tmp_table_name();
		db()->query(
			"CREATE TEMPORARY TABLE `".$tmp_table_name."` ( 
				`ip`			varchar(15) NOT NULL, 
				`users_list`	longtext NOT NULL default '', 
				`num_users`		int(10) NOT NULL default '0', 
				INDEX (`ip`)
			)"
		);
		// Collect unique IPs having used for log in for more than 1 user_id
		db()->query(
			"INSERT INTO `".$tmp_table_name."` ( 
				`num_users`
				,`ip`
				,`users_list`
			) 
			SELECT 
				COUNT(DISTINCT(`user_id`)) AS `unique_accounts`
				, `ip`
				, CAST(GROUP_CONCAT(DISTINCT `user_id` ORDER BY `user_id` ASC) AS CHAR) AS `users_list`
			FROM `".db('log_auth')."` 
			WHERE `user_id` IN (SELECT `id` FROM `".db('user')."`) 
			GROUP BY `ip` 
			HAVING `unique_accounts` > 1
			ORDER BY `unique_accounts` DESC" 
		);
		// Create missed records
		db()->query(
			"INSERT IGNORE INTO `".db('check_multi_ips')."` ( 
				`ip`
				,`matching_users`
				,`num_m_users`
			) 
			SELECT 
				`ip`
				, `users_list` 
				, `num_users` 
			FROM `".$tmp_table_name."`
			WHERE `ip` NOT IN(
				SELECT `ip` FROM `".db('check_multi_ips')."`
			)"
		);
		// Update cache table
		db()->query(
			"UPDATE `".db('check_multi_ips')."` AS `t1`
					, `".$tmp_table_name."` AS `tmp` 
			SET `t1`.`matching_users` = `tmp`.`users_list`
				, `t1`.`num_m_users` = `tmp`.`num_users`
			WHERE `tmp`.`ip` = `t1`.`ip`"
		);
		// Drop temp table
		db()->query("DROP TEMPORARY TABLE `".$tmp_table_name."`");

		// ####### Sync cookie matched users ##############

		$matched_accounts = array();

		$Q = db()->query("SELECT * FROM `".db('check_multi_accounts')."` WHERE `cookie_match` = '1'");
		while ($A = db()->fetch_assoc($Q)) {
			$_tmp = array();
			foreach (explode(",", $A["matching_users"]) as $_id) {
				$_tmp[$_id] = $_id;
			}
			if (isset($_tmp[""])) {
				unset($_tmp[""]);
			}
			$base_users[$A["user_id"]] = $_tmp;
		}
		// Try to add missing matching users
		if (!empty($base_users)) {
			$data = $base_users;
			foreach ((array)$base_users as $_cur_user => $_match_ids) {
				$data[$_cur_user] = $_match_ids;
				foreach ((array)$base_users as $_cur_user2 => $_match_ids2) {
					if ($_cur_user == $_cur_user2) {
						continue;
					}
					if (empty($_match_ids2)) {
						continue;
					}
					foreach ((array)$_match_ids2 as $_id3) {
						if (empty($_id3) || $_id3 == $_cur_user2) {
							continue;
						}
						if ($_id3 == $_cur_user) {
							$data[$_cur_user][$_cur_user2] = $_cur_user2;
						}
					}
				}
				// Remove self references
				if (isset($data[$_cur_user][$_cur_user])) {
					unset($data[$_cur_user][$_cur_user]);
				}
			}
			// Free some memory ...
			unset($base_users);
			// Save result
			foreach ((array)$data as $_user_id => $_matches) {
				db()->UPDATE("check_multi_accounts", array(
					"matching_users"	=> _es(implode(",", $_matches)),
					"cookie_match"		=> 1,
				), "`user_id`=".intval($_user_id));
			}
		}

		// ####### Sync users with other users matched by IP ##############

		db()->query(
			"UPDATE `".db('check_multi_accounts')."` 
			SET `ip_match` = '1'
			WHERE FIND_IN_SET(
				`user_id` , 
				(SELECT GROUP_CONCAT(`matching_users`) FROM `".db('check_multi_ips')."`)
			) > 0"
		);
	}

	/**
	* Page header hook
	*/
	function _show_header() {
		$pheader = t("Check multi-accounts");
		// Default subheader get from action name
		$subheader = _ucwords(str_replace("_", " ", $_GET["action"]));

		// Array of replacements
		$cases = array (
			//$_GET["action"] => {string to replace}
			"show"					=> "",
		);              		
		if (isset($cases[$_GET["action"]])) {
			// Rewrite default subheader
			$subheader = $cases[$_GET["action"]];
		}

		return array(
			"header"	=> $pheader,
			"subheader"	=> $subheader ? _prepare_html($subheader) : "",
		);
	}
}
