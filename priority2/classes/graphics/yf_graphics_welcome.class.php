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
		// For authorized admins only
		if (MAIN_TYPE_ADMIN && $_SESSION['admin_id'] && $_SESSION['admin_group']) {
			$user_info		= db()->query_fetch("SELECT * FROM `".db('admin')."` WHERE `id`=".$_SESSION['admin_id']);
			$admin_groups	= main()->get_data("admin_groups");
			$group_name		= translate($admin_groups[$_SESSION['admin_group']]);
			// Process template
			$replace = array(
				"id"		=> intval($user_info["id"]),
				"name"		=> _prepare_html($user_info['first_name']." ".$user_info['last_name']),
				"group"		=> _prepare_html($group_name),
				"time"		=> _format_date($_SESSION['admin_login_time']),
				"edit_link"	=> $_SESSION['admin_group'] == 1 ? "./?object=admin&action=edit&id=".intval($user_info["id"]) : "",
			);
			$body .= tpl()->parse("system/admin_welcome", $replace);
		// For authorized users only
		} elseif (MAIN_TYPE_USER && $_SESSION['user_id'] && $_SESSION['user_group']) {
			$user_info = user($_SESSION['user_id']);
			$user_groups	= main()->get_data("user_groups");
			$group_name 	= $user_groups[$_SESSION['user_group']];
			// Process template
			$replace = array(
				"id"	=> intval($user_info["id"]),
				"name"	=> _prepare_html(_display_name($user_info)),
				"group"	=> _prepare_html(translate($group_name)),
				"time"	=> _format_date($_SESSION['user_login_time']),
			);
			$body .= tpl()->parse("system/user_welcome", $replace);
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
