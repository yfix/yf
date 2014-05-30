<?php

/**
* Display core errors
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_log_core_errors_viewer {

	/** @var bool Filter on/off */
	public $USE_FILTER		= true;
	/** @var array @conf_skip Standard error types */
	public $_error_levels = array(
		1		=> "E_ERROR",
		2		=> "E_WARNING",
		4		=> "E_PARSE",
		8		=> "E_NOTICE",
		16		=> "E_CORE_ERROR",
		32		=> "E_CORE_WARNING",
		64		=> "E_COMPILE_ERROR",
		128		=> "E_COMPILE_WARNING",
		256		=> "E_USER_ERROR",
		512		=> "E_USER_WARNING",
		1024	=> "E_USER_NOTICE",
		2047	=> "E_ALL",
		2048	=> "E_STRICT",
		4096	=> "E_RECOVERABLE_ERROR",
	);
	/** @var array CSS classes for different error levels */
	public $_css_classes = array(
		1		=> "log_e_error",
		2		=> "log_e_warn",
		8		=> "log_e_notice",
		256		=> "log_e_error",
		512		=> "log_e_warn",
		1024	=> "log_e_notice",
	);

	/**
	* Constructor
	*/
	function _init () {
		$this->_account_types	= main()->get_data("account_types");
		$this->_sites_info = _class("sites_info");
	}

	/**
	* Default method
	*/
	function show () {
		// Prepare pager
		$sql = "SELECT * FROM ".db('log_core_errors')."";
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
			if (!empty($A["user_id"])) {
				$cur_user_info = $users_infos[$A["user_id"]];
			}
			// Process template
			$replace2 = array(
				"bg_class"		=> $i++ % 2 ? "bg1" : "bg2",
				"id"			=> intval($A["id"]),
				"error_level"	=> intval($A["error_level"]),
				"level_name"	=> _prepare_html($this->_error_levels[$A["error_level"]]),
				"message"		=> nl2br(_prepare_html($A["error_text"])),
				"date"			=> _format_date($A["date"], "long"),
				"td_class"		=> $this->_css_classes[$A["error_level"]],
				"user_id"		=> intval($cur_user_info["id"]),
				"user_name"		=> _prepare_html($cur_user_info["name"]),
				"user_nick"		=> _prepare_html($cur_user_info["nick"]),
				"user_avatar"	=> _show_avatar($A["user_id"], $cur_user_info, 1),
				"group_name"	=> t($this->_account_types[$user_info["group"]]),
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
			);
			$items .= tpl()->parse($_GET["object"]."/item", $replace2);
		}
		// Prepare teplate
		$replace = array(
			"items"					=> $items,
			"pages"					=> $pages,
			"total"					=> intval($total),
			"prune_action"			=> "./?object=".$_GET["object"]."&action=prune",
			"form_action"			=> "./?object=".$_GET["object"]."&action=multi_delete",
			"top"					=> "./?object=".$_GET["object"]."&action=top_of_errors",
		);
		return tpl()->parse($_GET["object"]."/main", $replace);
	}

	/**
	* View error log record details
	*/
	function view () {
		$_GET["id"] = intval($_GET["id"]);
		// Get record
		if (!empty($_GET["id"])) {
			$log_info = db()->query_fetch("SELECT * FROM ".db('log_core_errors')." WHERE id=".intval($_GET["id"]));
		}
		if (empty($log_info)) {
			return "No such record!";
		}
		$A = &$log_info;
		// Get user info
		if (!$A["is_admin"] && !empty($A["user_id"])) {
			$cur_user_info = db()->query_fetch("SELECT * FROM ".db('user')." WHERE id =".intval($A["user_id"]));
		}
		// Process template
		$replace = array(
			"record_id"			=> intval($A["id"]),
			"error_level"		=> intval($A["error_level"]),
			"level_name"		=> _prepare_html($this->_error_levels[$A["error_level"]]),
			"message"			=> nl2br(_prepare_html(trim($A["error_text"]))),
			"date"				=> _format_date($A["date"], "long"),
			"td_class"			=> $this->_css_classes[$A["error_level"]],
			"user_id"			=> intval($cur_user_info["id"]),
			"user_name"			=> _prepare_html($cur_user_info["name"]),
			"user_nick"			=> _prepare_html($cur_user_info["nick"]),
			"user_avatar"		=> _show_avatar($A["user_id"], $cur_user_info, 1),
			"user_group"		=> $A["user_group"] > 1 ? t($this->_account_types[$A["user_group"]]) : "GUEST",
			"member_url"		=> "./?object=account&action=show&user_id=".$cur_user_info["id"],
			"user_email"		=> _prepare_html($cur_user_info["email"]),
			"details_link"		=> "./?object=".$_GET["object"]."&action=view&id=".$A["id"],
			"delete_link"		=> "./?object=".$_GET["object"]."&action=delete&id=".$A["id"],
			"query_string"		=> _prepare_html($A["query_string"]),
			"request_uri"		=> _prepare_html($A["request_uri"]),
			"log_ip"			=> _prepare_html($A["ip"]),
			"log_browser"		=> _prepare_html($A["user_agent"]),
			"log_referer"		=> _prepare_html($A["referer"]),
			"back_link"			=> "./?object=".$_GET["object"],
			"source_file"		=> _prepare_html($A["source_file"], 0),
			"source_line"		=> _prepare_html($A["source_line"]),
			"edit_source_link"	=> file_exists($A["source_file"]) ? "./?object=file_manager&action=edit_item&f_=".basename($A["source_file"])."&dir_name=".urlencode(dirname($A["source_file"])) : "",
			"site_id"			=> intval($A["site_id"]),
			"site_name"			=> !empty($A["site_id"]) ? _prepare_html($this->_sites_info->info[$A["site_id"]]["name"]) : "",
			"site_link"			=> $this->_sites_info->info[$A["site_id"]]["WEB_PATH"],
			"section_name"		=> $A["is_admin"] ? "ADMIN" : "USER",
			"env_data"			=> !empty($A["env_data"]) ? printr(@unserialize($A["env_data"]), 1) : "",
		);
		return tpl()->parse($_GET["object"]."/view", $replace);
	}

	/**
	* Delete record
	*/
	function delete () {
		$_GET["id"] = intval($_GET["id"]);
		// Do delete record
		db()->query("DELETE FROM ".db('log_core_errors')." WHERE id=".intval($_GET["id"]));
		// Return user back
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo $_GET["id"];
		} else {
			return js_redirect($_SERVER["HTTP_REFERER"], 0);
		}
	}

	/**
	* Multi delete records
	*/
	function multi_delete () {
		$ids_to_delete = array();
		// Prepare ids to delete
		foreach ((array)$_POST["items"] as $_cur_id) {
			if (empty($_cur_id)) {
				continue;
			}
			$ids_to_delete[$_cur_id] = $_cur_id;
		}
		// Do delete ids
		if (!empty($ids_to_delete)) {
			db()->query("DELETE FROM ".db('log_core_errors')." WHERE id IN(".implode(",",$ids_to_delete).")");
		}
		// Return user back
		return js_redirect($_SERVER["HTTP_REFERER"], 0);
	}

	/**
	* Prune log table
	*/
	function prune () {
		if (isset($_POST["prune_days"])) {
			db()->query("DELETE FROM ".db('log_core_errors')."".(!empty($_POST["prune_days"]) ? " WHERE date <= ".intval(time() - $_POST["prune_days"] * 86400) : ""));
		}
		// Return user back
		return js_redirect($_SERVER["HTTP_REFERER"], 0);
	}

	/**
	* Clean log table
	*/
	function clean () {
		// Do delete record
		db()->query("TRUNCATE TABLE ".db('log_core_errors')."");
		// Return user back
		return js_redirect($_SERVER["HTTP_REFERER"], 0);
	}

	/**
	* Show data for selected user
	*/
	function show_for_user () {
		$_GET["id"] = intval($_GET["id"]);
		// Do save filter
		$_REQUEST["user_id"] = $_GET["id"];
		$this->clear_filter(1);
		$this->save_filter(1);
		return $this->show();
	}

	/**
	* Show data for selected IP address
	*/
	function show_for_ip () {
		// Do save filter
		$_REQUEST["ip"] = $_GET["id"];
		$this->clear_filter(1);
		$this->save_filter(1);
		return $this->show();
	}

	// Delete filtered records
	function delete_all_filtered () {
		// Prepare query for deleting
		$p_replace = "/(ORDER BY)(.)+/ims";
		if ($_POST["confirm"]){	
			$sql = "DELETE FROM ".db('log_core_errors')." WHERE 1=1 ".$this->_create_filter_sql();
			$sql = preg_replace($p_replace, "", $sql);
			$result = db()->query($sql);
			$this->clear_filter(1);
			return js_redirect("./?object=".$_GET["object"]._add_get());
		} else {
			$sql = "SELECT COUNT(*) AS `0` FROM ".db('log_core_errors')." WHERE 1=1 ".$this->_create_filter_sql();
			$sql = preg_replace($p_replace, "", $sql);
			list ($num_records) = db()->query_fetch($sql);
			$replace = array(
				"confirmed"		=> "./?object=".$_GET["object"]."&action=delete_all_filtered"._add_get(),
				"num_records"	=> $num_records,
				"cancel_del"	=> "./?object=".$_GET["object"]._add_get(),
			);			
			return tpl()->parse($_GET["object"]."/confirm_delete", $replace);
		}
	}	

	// Forming top of errors
	function top_of_errors () {
		$GLOBALS['PROJECT_CONF']["divide_pages"]["SQL_COUNT_REWRITE"] = false;

		$sql = "SELECT id, error_level, error_text, COUNT(error_text) AS num FROM ".db('log_core_errors')." GROUP BY error_text ORDER BY num DESC";
		list($add_sql, $pages, $total) = common()->divide_pages($sql);
		$records = db()->query_fetch_all($sql.$add_sql);

		// Process data
		foreach ((array)$records as $A) {
			// Prepare template
			$replace2 = array(
				"level_name"	=> _prepare_html($this->_error_levels[$A["error_level"]]),
				"message"		=> _prepare_html(trim($A["error_text"])),
				"num"			=> $A["num"],
				"bg_class"		=> $i++ % 2 ? "bg1" : "bg2",
				"id"			=> intval($A["id"]),
			);
			$items .= tpl()->parse($_GET["object"]."/item_top", $replace2);
		}
		$replace =array (
			"items"			=> $items,
			"pages"			=> $pages,
			"total"			=> intval($total),
			"form_action"	=> "./?object=".$_GET["object"]."&action=save_filter&go_home=1",
		);
		return tpl()->parse($_GET["object"]."/main_top", $replace);
	}

	// Process custom box
	function _box ($name = "", $selected = "") {
		if (empty($name) || empty($this->_boxes[$name])) return false;
		else return eval("return common()->".$this->_boxes[$name].";");
	}

	function _hook_widget__core_errors_log ($params = array()) {
// TODO
	}
}
