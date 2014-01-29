<?php

/**
* Administrators home page
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_admin_home {

	/** @var string @conf_skip */
	public $CACHE_NAME 			= "admin_statistics";
	/** @var int */
	public $ADMIN_HOME_CACHE_TIME  = 600;		//60sec * 10minutes 
	/** @var bool */
	public $DISPLAY_STATS			= false;
	/** @var bool */
	public $SHOW_CUR_SETTINGS		= false;
	/** @var bool */
	public $SHOW_GENERAL_INFO		= true;

	/**
	*/
	function _init () {
		$this->_admin_modules = module("admin_modules")->_get_modules();
	}

	/**
	*/
	function show () {
		if (main()->ADMIN_GROUP != 1) {
			$admin_user = db()->get('SELECT * FROM '.db('admin').' WHERE id='.(int)main()->ADMIN_ID);
			if ($admin_user['go_after_login']) {
				$url = $admin_user['go_after_login'];
			} else {
				$admin_group = db()->get('SELECT * FROM '.db('admin_groups').' WHERE id='.(int)main()->ADMIN_GROUP);
				if ($admin_group['go_after_login']) {
					$url = $admin_group['go_after_login'];
				}
			}
			if ($url) {
				return js_redirect($url);
			}
		}
		return module("manage_dashboards")->display("admin_home");
//		return module("admin_wall")->show();
	}

	/**
	*/
	function show_old () {
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

		if ($this->DISPLAY_STATS) {
			$admin_statistics_array = cache_get($this->CACHE_NAME, $this->ADMIN_HOME_CACHE_TIME);
		}
		if ($this->DISPLAY_STATS && empty($admin_statistics_array)) {
			// General info
			$db_size = 0;
			$Q = db()->query("SHOW TABLE STATUS FROM ".DB_NAME."");
			while ($A = db()->fetch_assoc($Q)) {
				$db_size += $A["Data_length"];
			}
			$admin_statistics_array["db_size"] = common()->format_file_size($db_size);
			$admin_statistics_array["project_dir_size"] =  common()->format_file_size(_class("dir")->dirsize(INCLUDE_PATH));

			// Statistics 
			$A = db()->query_fetch_all("SELECT * FROM ".db('user_groups')." WHERE active='1'");
			$sql_parts[] = "SELECT 'total_users' AS '0', COUNT(id) AS '1' FROM ".db('user')." WHERE active='1'";
			foreach ((array)$A as $V1) {
				$sql_parts[] = "SELECT 'total_".strtolower($V1["name"])."' AS '0', COUNT(id) AS '1' FROM ".db('user')." WHERE `group`='".$V1["id"]."' AND active='1'";
			}
			$sql_parts2 = array(
				"SELECT 'forum_topics' AS '0', COUNT(id) AS '1' FROM ".db('forum_topics')." WHERE 1=1",
				"SELECT 'forum_posts' AS '0', COUNT(id) AS '1' FROM ".db('forum_posts')." WHERE 1=1",
				"SELECT 'gallery_photos' AS '0', COUNT(id) AS '1' FROM ".db('gallery_photos')." WHERE 1=1",
				"SELECT 'blog_posts' AS '0', COUNT(id) AS '1' FROM ".db('blog_posts')." WHERE 1=1",
				"SELECT 'articles' AS '0', COUNT(id) AS '1' FROM ".db('articles_texts')." WHERE 1=1",
			);
			$sql_parts = array_merge($sql_parts, $sql_parts2);
			$sql = "(\r\n".implode("\r\n) UNION ALL (\r\n",$sql_parts)."\r\n)";
			$B = db()->query_fetch_all($sql);
	
			foreach ((array)$B as $V) {
				$admin_statistics_array[$V[0]] = $V[1];
			}
			cache_put($this->CACHE_NAME, $admin_statistics_array);
		}

	   	if ($this->DISPLAY_STATS) {
			$statistics = tpl()->parse($_GET["object"]."/statistics", $admin_statistics_array);
		}

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
	
	/**
	*/
	function _show_suggesting_messages () {
		$user_modules_methods = module("admin_modules")->_get_methods(array("private" => "1")); 

		$suggests = array();
		foreach ((array)$user_modules_methods as $module_name => $module_methods) {
			if (!isset($this->_admin_modules[$module_name])) {
				continue;
			}
			foreach ((array)$module_methods as $method_name) {
				if (substr($method_name, 0, 17) != "_account_suggests"){
					continue;
				}
				
				$module_suggests = module_safe($module_name)->$method_name();
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
		return _class('common_admin')->_admin_link_is_allowed($url);
	}

	/**
	* Helper method
	*/
	function clear_core_cache () {
		_class("cache")->_clear_cache_files();
		return js_redirect("./?object=".$_GET["object"]);
	}

	/**
	* Display php info
	*/
	function show_php_info () {
		main()->NO_GRAPHICS = true;
		phpinfo();
	}

	/**
	*/
	function _hook_widget__admin_home ($params = array()) {
// TODO: purge cache (memcached), disable site (maintenance), change default language, change default template, enable/disable other features here
	}

	/**
	* Custom content specific only for this project (designed to be inherited)
	*/
	function _custom_content () {
	}
}
