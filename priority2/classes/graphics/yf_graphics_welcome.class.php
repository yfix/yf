<?php

/**
* Welcome messages handler
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_graphics_welcome {

	/**
	* Welcome message method
	*
	* @access	private
	* @return	string	Output
	*/
	function _show_welcome () {
		$admin_id	= (int)$_SESSION['admin_id'];
		$admin_group= (int)$_SESSION['admin_group'];
		$user_id	= (int)main()->USER_ID;
		$user_group	= (int)main()->ADMIN_ID;
		$login_time = MAIN_TYPE_ADMIN ? $_SESSION['admin_login_time'] : $_SESSION['user_login_time'];
		// For authorized admins only
		if (MAIN_TYPE_ADMIN && $admin_id && $admin_group) {
			$admin_info		= db()->query_fetch("SELECT * FROM `".db('admin')."` WHERE `id`=".$admin_id);
			$admin_groups	= main()->get_data("admin_groups");
			$body .= tpl()->parse("system/admin_welcome", array(
				"id"		=> intval($admin_id),
				"name"		=> _prepare_html($admin_info['first_name']." ".$admin_info['last_name']),
				"group"		=> _prepare_html(t($admin_groups[$admin_group])),
				"time"		=> _format_date($login_time),
				"edit_link"	=> $admin_group == 1 ? "./?object=admin&action=edit&id=".intval($admin_id) : "",
			));
		// For authorized users only
		} elseif (MAIN_TYPE_USER && $user_id && $user_group) {
			$user_info = user($user_id);
			$user_groups	= main()->get_data("user_groups");
			$body .= tpl()->parse("system/user_welcome", array(
				"id"	=> intval($user_info["id"]),
				"name"	=> _prepare_html(_display_name($user_info)),
				"group"	=> _prepare_html(t($user_groups[$user_group])),
				"time"	=> _format_date($login_time),
			));
		}
		return $body;
	}

	/**
	* Welcome message for the admin section
	*
	* @access	private
	* @return	string	Output
	*/
	function _show_welcome2 () {
		if (MAIN_TYPE_ADMIN) {
			$body .= t("you_logged_in_at")." ".date("H:i:s", $_SESSION["admin_login_time"])." ".t("as")." ".t("admin");
		}
		return $body;
	}
}
