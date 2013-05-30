<?php

/**
* Administrators home page
* 
* @package		Profy Framework
* @author		Yuri Vysotskiy <profy.net@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class profy_admin_home {

	/** @var string @conf_skip */
	var $CACHE_NAME 			= "admin_statistics";
	/** @var int */
	var $ADMIN_HOME_CACHE_TIME  = 600;		//60sec * 10minutes 
	/** @var bool */
	var $DISPLAY_STATS			= false;
	/** @var bool */
	var $SHOW_CUR_SETTINGS		= false;
	/** @var bool */
	var $SHOW_GENERAL_INFO		= true;

	/**
	* Framework constructor
	*/
	function _init () {
		$this->_admin_modules = module("admin_modules")->_get_modules();
	}

	/**
	* Default method
	*/
	function show () {
		// Path to project.conf.php
		$proj_conf_path = INCLUDE_PATH."project_conf.php";
		
		if ($this->SHOW_CUR_SETTINGS && $_SESSION["admin_group"] == 1) {
			// Current settings
			$replace2 = array(
				"rewrite_mode" 		=> (int)conf("rewrite_mode"),
				"output_caching" 	=> (int)conf("output_caching"),
//				"gzip_compress"		=> (int)conf("gzip_compress"),
//				"compress_output"	=> (int)conf("compress_output"),
				"language"			=> _prepare_html(strtoupper(conf("language"))),
				"charset"			=> _prepare_html(strtoupper(conf("charset"))),
				"admin_email"		=> _prepare_html(conf("admin_email")),
				"mail_debug"		=> (int)conf("mail_debug"),
				"site_enabled"		=> (int)conf("site_enabled"),
				"settings_link"		=> $this->_url_allowed("./?object=settings"),
			);
			$cur_settings = tpl()->parse($_GET["object"]."/cur_settings", $replace2);
		} else {
			$this->DISPLAY_STATS = false;
		}

		if ($this->SHOW_GENERAL_INFO && $_SESSION["admin_group"] == 1) {
			$replace3 = array(
				"php_ver" 				=> phpversion(),
				"mysql_serv_ver" 		=> db()->get_server_version(),
				"mysql_host_info"		=> db()->get_host_info(),
				"db_name"				=> DB_NAME,
				"db_size"				=> $admin_statistics_array["db_size"],
				"project_dir_size"		=> $admin_statistics_array["project_dir_size"],
			);
			$general_info = tpl()->parse($_GET["object"]."/general_info", $replace3);
		}

		if ($this->DISPLAY_STATS && main()->USE_SYSTEM_CACHE) {
			$admin_statistics_array = cache()->get($this->CACHE_NAME, $this->ADMIN_HOME_CACHE_TIME);
		}
		if ($this->DISPLAY_STATS && empty($admin_statistics_array)) {
			// General info
			$db_size = 0;
			$Q = db()->query("SHOW TABLE STATUS FROM `".DB_NAME."`");
			while ($A = db()->fetch_assoc($Q)) {
				$db_size += $A["Data_length"];
			}
			$admin_statistics_array["db_size"] = common()->format_file_size($db_size);
			$admin_statistics_array["project_dir_size"] =  common()->format_file_size(_class("dir")->dirsize(INCLUDE_PATH));

			// Statistics 
			$A = db()->query_fetch_all("SELECT * FROM `".db('user_groups')."` WHERE `active`='1'");
			$sql_parts[] = "SELECT 'total_users' AS '0', COUNT(`id`) AS '1' FROM `".db('user')."` WHERE `active`='1'";
			foreach ((array)$A as $V1) {
				$sql_parts[] = "SELECT 'total_".strtolower($V1["name"])."' AS '0', COUNT(`id`) AS '1' FROM `".db('user')."` WHERE `group`='".$V1["id"]."' AND `active`='1'";
			}
			$sql_parts2 = array(
				"SELECT 'forum_topics' AS '0', COUNT(`id`) AS '1' FROM `".db('forum_topics')."` WHERE 1=1",
				"SELECT 'forum_posts' AS '0', COUNT(`id`) AS '1' FROM `".db('forum_posts')."` WHERE 1=1",
				"SELECT 'gallery_photos' AS '0', COUNT(`id`) AS '1' FROM `".db('gallery_photos')."` WHERE 1=1",
				"SELECT 'blog_posts' AS '0', COUNT(`id`) AS '1' FROM `".db('blog_posts')."` WHERE 1=1",
				"SELECT 'articles' AS '0', COUNT(`id`) AS '1' FROM `".db('articles_texts')."` WHERE 1=1",
			);
			$sql_parts = array_merge($sql_parts, $sql_parts2);
			$sql = "(\r\n".implode("\r\n) UNION ALL (\r\n",$sql_parts)."\r\n)";
			$B = db()->query_fetch_all($sql);
    
			foreach ((array)$B as $V) {
				$admin_statistics_array[$V[0]] = $V[1];
			}

			if (main()->USE_SYSTEM_CACHE) {
				cache()->put($this->CACHE_NAME, $admin_statistics_array);
			}
		}

		// Statistics
       	if ($this->DISPLAY_STATS) {
			$statistics = tpl()->parse($_GET["object"]."/statistics", $admin_statistics_array);
		}

		// Process main template
		$replace = array(
			"proj_conf_link"	=> file_exists($proj_conf_path) ? "./?object=file_manager&action=edit_item&f_=".basename($proj_conf_path)."&dir_name=".urlencode(dirname($proj_conf_path)) : "",
			"current_date"		=> _format_date(time(), "long"),
			"my_id"				=> $_SESSION["admin_id"],
			"cur_settings"		=> $cur_settings,
			"general_info"		=> $general_info,
			"statistics"		=> $statistics,
			"cache_time"		=> ceil($this->ADMIN_HOME_CACHE_TIME / 60),
			"custom_content"	=> $this->_custom_content(),
			"custom_content"	=> $this->_custom_content(),
			"suggests"			=> $this->_show_suggesting_messages(),
			// Common actions here
		);
		
		return tpl()->parse($_GET["object"]."/main", $replace);
	}
	
	
	// Display suggesting messages
	function _show_suggesting_messages () {
		$user_modules_methods = main()->call_class_method("admin_modules", "admin_modules/", "_get_methods", array("private" => "1")); 

		$suggests = array();
		foreach ((array)$user_modules_methods as $module_name => $module_methods) {
			if (!isset($this->_admin_modules[$module_name])) {
				continue;
			}
			foreach ((array)$module_methods as $method_name) {
				if (substr($method_name, 0, 17) != "_account_suggests"){
					continue;
				}
				
				$module_suggests = main()->_execute($module_name, $method_name);
				foreach ((array)$module_suggests as $val){
					$suggests[] = $val;
				}
			}
		}
		
		if (!empty($suggests)){
			$replace = array(
				"suggests"		=> $suggests,
			);
			
			return tpl()->parse(__CLASS__."/suggests", $replace);
		}
	}
	
	/**
	* 
	*/
	function edit_account () {
		return js_redirect("./?object=admin&action=edit&id=".$_SESSION["admin_id"]);
	}

	/**
	* Do display home block filled from menu
	*/
	function _home_block_from_menu () {
		$STPL_MENU_MAIN = "admin_home/menu";
		$STPL_MENU_ITEM	= $STPL_MENU_MAIN."_item";

		$items = _class("graphics")->_show_menu(array(
			"name"	=> "admin_home_menu",
			"return_array"	=> 1,
			"force_stpl_name"	=> $STPL_MENU_MAIN,
		));

		foreach ((array)$items as $id => $item) {
			$item["need_clear"] = 0;
			if ($item["type_id"] == 3 && !($i++ % 3)) {
				$item["need_clear"] = 1;
			}
			if ($item["type_id"] == 1 && !$this->_url_allowed($item["link"])) {
				unset($items[$id]);
				continue;
			}
			$items[$id] = tpl()->parse($STPL_MENU_ITEM, $item);
		}
		return tpl()->parse($STPL_MENU_MAIN, array("items" => implode("", (array)$items)));
	}

	/**
	*/
	function _url_allowed ($url = "") {
		$tmp_url = $url;
		$params = array();
		if (substr($tmp_url, 0, 3) == "./?") {
			$tmp_url = substr($tmp_url, 3);
		}
		parse_str($tmp_url, $params);
		if ($params["task"]) {
			return $url;
		}
		if (!isset($this->_admin_modules[$params["object"]])) {
			return "";
		}
		$center_block_id = _class("graphics")->_get_center_block_id();
		if ($center_block_id && !_class("graphics")->_check_block_rights($center_block_id, $params["object"], $params["action"])) {
			return "";
		}
		return $url;
	}

	/**
	* Custom content specific only for this project (designed to be inherited)
	*/
	function _custom_content () {
	}

	/**
	* Hook for the site_nav_bar module
	*/
	function _nav_bar_items ($params = array()) {
		$OBJ = $params["nav_bar_obj"];
		if (!is_object($OBJ)) {
			return false;
		}
		$items = array();
//		$items[]	= $OBJ->_nav_item($OBJ->_decode_from_url($_GET["action"]));
		$items[]	= $OBJ->_nav_item("Administration home");
		return $items;
	}

	/**
	* Helper method
	*/
	function clear_core_cache () {
		$CORE_CACHE_OBJ = main()->init_class("cache", "classes/");
		if (is_object($CORE_CACHE_OBJ)) {
			$CORE_CACHE_OBJ->_clear_cache_files();
		}
		return js_redirect("./?object=".$_GET["object"]);
	}

	/**
	* Helper method
	*/
	function clear_output_cache () {
		// Init dir obj
		$DIR_OBJ = main()->init_class("dir", "classes/");
		// Init output cache class
		$OC_OBJ = main()->init_class("output_cache", "classes/");
		// Switch between separate sites dirs or storing cache in one dir
		if ($OC_OBJ->USE_SITES_DIRS) {
			// Try to get info about sites vars
			$this->_sites_info = main()->init_class("sites_info", "classes/");
			foreach ((array)$this->_sites_info->info as $_site_id => $_site_info) {
				$site_cache_dir = $_site_info["REAL_PATH"].$OC_OBJ->OUTPUT_CACHE_DIR;
				// Check if cache dir exists
				if (!file_exists($site_cache_dir)) {
					continue;
				}
				// Check if we can read contents
				if (!($dh = opendir($site_cache_dir))) {
					continue;
				}
				// Do clear cache
				while (($f = readdir($dh)) !== false) {
					if (in_array($f, array(".","..",".svn",".git","index.html"))) {
						continue;
					}
					$cur_path = $site_cache_dir.$f;
					// If this is a file - just unlink it
					if (is_file($cur_path)) {
						unlink($cur_path);
					} elseif (is_dir($cur_path)) {
						$DIR_OBJ->delete_dir($cur_path, true);
					}
				}
				closedir($dh);
			}
		} else {
// TODO
			echo "Not done yet";
		}
		return js_redirect("./?object=".$_GET["object"]);
	}

	/**
	* Helper method
	*/
	function prepare_installer_db_files () {
		$struct_array = array();

		$INSTALLER_DB_OBJ = main()->init_class("installer_db", "classes/installer/");
		$INSTALLER_DB_OBJ->_create_struct_files(1);

		db()->query("SELECT `name` FROM `".db('settings_category')."` WHERE `id`=1");

//		$struct_array = $INSTALLER_DB_OBJ->_get_all_struct_array();
//		echo $INSTALLER_DB_OBJ->_format_struct_array($struct_array);

//printr($struct_array);
// TODO
//		return js_redirect("./?object=".$_GET["object"]);
	}

	/**
	* Helper method
	*/
	function check_graphic_libs () {
// TODO
	}

	/**
	* Helper method
	*/
	function clear_temp_dir () {
// TODO
	}

	/**
	* List all available email templates with links to edit them
	*/
	function show_email_templates () {
		$email_stpls = array(
			// Init type
			"admin"	=> array(
				// Theme name
				"admin"	=> array(
					"advert/admin/order_email",
					"approve_recip/email_approve",
					"approve_recip/email_suspend",
					"confirm_reg/email",
					"email/send_mail",
					"help_tickets/email_to_user",
					"links/email_approve",
				),
			),
			// Init type
			"user"	=> array(
				// Theme name
				"new_1"	=> array(
					"account/change_email/email_to_old",
					"account/change_email/email_to_new",
					"account/change_email/email_changed",
					"account/notify/email_admin",
					"account/notify/user_email",
					"account/link_recip/email_admin",
					"email/send_mail",
					"forum/register/confirm_email",
					"friends/email_when_added",
					"friends/email_when_deleted",
					"get_pswd/email",
					"help/email_to_user",
					"links/email_register",
					"links/email_get_pswd",
					"links/email_add_link",
					"register/email_confirm_agency",
					"register/email_confirm_escort",
					"register/email_confirm_visitor",
					"register/email_success_agency",
					"register/email_success_escort",
					"register/email_success_visitor",
				),
			),
		);
		// Process templates
		foreach ((array)$email_stpls as $_init_type => $_themes) {
			foreach ((array)$_themes as $_theme_name => $_stpls) {
				foreach ((array)$_stpls as $_stpl_name) {
					$items[] = array(
						"bg_class"			=> !(++$i % 2) ? "bg1" : "bg2",
						"stpl_edit_link"	=> "./?object=template_editor&action=edit_stpl&name=".$_stpl_name."&theme=".$_theme_name,
						"theme_edit_link"	=> "./?object=template_editor&action=show_stpls_in_theme&theme=".$_theme_name,
						"stpl_name"			=> $_stpl_name,
						"init_type"			=> $_init_type,
						"theme_name"		=> $_theme_name,
					);
				}
			}
		}
		// Prepare template
		$replace = array(
			"items"	=> $items,
		);
		return tpl()->parse(__CLASS__."/email_templates", $replace);
	}

	/**
	* Re-build packed code
	*/
	function rebuild_packed_code () {
		return "Sorry, this feature is currently disabled";

//		$PP_OBJ = main()->init_class("project_packer");
//		return $PP_OBJ->go();
	}

	/**
	* Display php info
	*/
	function show_php_info () {
		main()->NO_GRAPHICS = true;
		phpinfo();
	}

	/**
	* Page header hook
	*/
	function _show_header() {
		$pheader = _ucfirst(t("Administration home"));
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
