<?php

/**
* Admin "log in" info analyser
*/
class yf_log_admin_auth_view {

	/**
	*/
	function show () {
		$filter_name = $_GET['object'].'__'.$_GET['action'];
		$default_filter = array(
			'order_by' => 'date',
			'order_direction' => 'desc',
		);
		$sql = 'SELECT * FROM '.db('log_admin_auth');
		return table($sql, array(
				'filter' => (array)$_SESSION[$filter_name] + $default_filter,
				'filter_params' => array(
					'name'	=> 'like',
				),
			))
			->admin('admin_id')
			->link('ip', './?object='.$_GET['object'].'&action=show_for_ip&id=%d')
			->date('date', 'full')
			->text('user_agent')
			->text('referer')
		;
/*
		$sql = "SELECT * FROM ".db('log_admin_auth')." ";
		list($add_sql, $pages, $total) = common()->divide_pages($sql);
		$Q = db()->query($sql.$add_sql);
		while ($A = db()->fetch_assoc($Q)) {
			$records[] = $A;
		}
		foreach ((array)$records as $A) {
			$replace2 = array(
				"bg_class"		=> $i++ % 2 ? "bg1" : "bg2",
//				"user_id"		=> intval($cur_user_info["id"]),
//				"group_name"	=> t($this->_account_types[$user_info["group"]]),
				"member_url"	=> "./?object=admin&action=edit&id=".$A["admin_id"],
				"log_login"		=> _prepare_html($A["login"]),
				"log_ip"		=> _prepare_html($A["ip"]),
				"log_ua"		=> _prepare_html($A["user_agent"]),
				"log_referer"	=> _prepare_html($A["referer"]),
				"log_date"		=> _format_date($A["date"], "long"),
				"for_admin_link"=> "./?object=".$_GET["object"]."&action=show_for_admin&id=".$A["admin_id"],
				"for_ip_link"	=> "./?object=".$_GET["object"]."&action=show_for_ip&id=".$A["ip"],
			);
			$items .= tpl()->parse($_GET["object"]."/item", $replace2);
		}
		$replace = array(
			"total"				=> intval($total),
			"items"				=> $items,
			"pages"				=> $pages,
			"prune_action"		=> "./?object=".$_GET["object"]."&action=prune",
			"same_ips_action"	=> "./?object=".$_GET["object"]."&action=show_same_ips",
		);
		return tpl()->parse($_GET["object"]."/main", $replace);
*/
	}

	/**
	* Prune log table
	*/
	function prune () {
		if (isset($_POST["prune_days"])) {
			db()->query("DELETE FROM ".db('log_admin_auth')."".(!empty($_POST["prune_days"]) ? " WHERE date <= ".intval(time() - $_POST["prune_days"] * 86400) : ""));
			db()->query("OPTIMIZE TABLE ".db('log_admin_auth')."");
		}
		// Return user back
		return js_redirect($_SERVER["HTTP_REFERER"], 0);
	}

	/**
	* Show same ips for selected users
	*/
	function show_same_ips () {
		$_GET["id"] = preg_replace("/[^0-9,]/", "", trim($_REQUEST["id"]));
		// Prepare users ids to process
		$admin_ids = array();
		foreach ((array)explode(",", $_GET["id"]) as $tmp) {
			$_id = intval($tmp);
			if (empty($_id)) {
				continue;
			}
			$admin_ids[$_id] = $_id;
		}
		// Check array
		if (empty($admin_ids)) {
			return "Please specify user ids to analyse";
		}
		// Get same ips
		$Q = db()->query(
			"SELECT COUNT(DISTINCT(admin_id)) AS unique_accounts, 
				COUNT(*) AS num_logins_from_this_ip, 
				ip 
			FROM ".db('log_admin_auth')." 
			WHERE admin_id IN (".implode(",",$admin_ids).") 
			GROUP BY ip 
			HAVING unique_accounts > 1
			ORDER BY unique_accounts DESC"
		);
		while ($A = db()->fetch_assoc($Q)) {
			$items[] = array(
				"unique_accounts"	=> intval($A["unique_accounts"]),
				"num_logins"		=> intval($A["num_logins_from_this_ip"]),
				"ip"				=> _prepare_html($A["ip"]),
				"ip_link"			=> "./?object=".$_GET["object"]."&action=show_for_ip&id=".$A["ip"],
			);
		}
		// Prepare template
		$replace = array(
			"items"		=> $items,
			"admin_ids"	=> implode(",", $admin_ids),
		);
		return tpl()->parse($_GET["object"]."/same_ips", $replace);
	}

	/**
	*/
	function show_for_admin() {
		$_GET['page'] = 'clear';
		$_GET['filter'] = 'admin_id:'.$_GET['id'];
		return $this->filter_save();
	}

	/**
	*/
	function show_for_ip() {
		$_GET['page'] = 'clear';
		$_GET['filter'] = 'ip:'.$_GET['id'];
		return $this->filter_save();
	}

	/**
	*/
	function filter_save() {
		$filter_name = $_GET['object'].'__show';
		if ($_GET['page'] == 'clear') {
			$_SESSION[$filter_name] = array();
			// Example: &filter=admin_id:1,ip:127.0.0.1
			if (isset($_GET['filter'])) {
				foreach (explode(',', $_GET['filter']) as $item) {
					list($k,$v) = explode(':', $item);
					if ($k && isset($v)) {
						$_SESSION[$filter_name][$k] = $v;
					}
				}
			}
		} else {
			$_SESSION[$filter_name] = $_POST;
			foreach (explode('|', 'clear_url|form_id|submit') as $f) {
				if (isset($_SESSION[$filter_name][$f])) {
					unset($_SESSION[$filter_name][$f]);
				}
			}
		}
		return js_redirect('./?object='.$_GET['object'].'&action='. str_replace ($_GET['object'].'__', '', $filter_name));
	}

	/**
	*/
	function _show_filter() {
		if (!in_array($_GET['action'], array('show'))) {
			return false;
		}
		$filter_name = $_GET['object'].'__'.$_GET['action'];
		$r = array(
			'form_action'	=> './?object='.$_GET['object'].'&action=filter_save&id='.$filter_name,
			'clear_url'		=> './?object='.$_GET['object'].'&action=filter_save&id='.$filter_name.'&page=clear',
		);
		$order_fields = array();
		foreach (explode('|', 'admin_id|login|group|date|ip|user_agent|referer') as $f) {
			$order_fields[$f] = $f;
		}
		return form($r, array(
				'selected'	=> $_SESSION[$filter_name],
			))
			->number('admin_id')
			->text('ip')
			->select_box('order_by', $order_fields, array('show_text' => 1))
			->radio_box('order_direction', array('asc'=>'Ascending','desc'=>'Descending'))
			->save_and_clear();
		;
	}

	/**
	*/
	function _hook_widget__admin_auth_successes ($params = array()) {
// TODO
	}
}
