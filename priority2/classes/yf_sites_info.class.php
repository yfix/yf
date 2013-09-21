<?php

/**
* Get info about sites
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_sites_info {

	/** @var string Sites config file name */
	public $_config_file_name	= "site_vars.php";
	/** @var string Name of sites index file */
	public $_index_file_name	= "index.php";
	/** @var array Config constants to parse */
	public $_consts_to_parse	= array(
		"SITE_UPLOADS_DIR",
		"SITE_ADVERT_PHOTOS_DIR",
		"SITE_LINKS_BANNERS_DIR",
		"SITE_AVATARS_DIR",
		"SITE_BLOG_IMAGES_DIR",
		"SITE_GALLERY_DIR",
		"DEFAULT_SKIN",
	);
	/** @var string @conf_skip Patterns to get constant value */
	public $_get_const_pattern	= "/define\s*\(\s*[\"\']*##CONST_NAME##[\"\']*[\s\t]*,[\s\t]*[\"\']*([^\'\"]+)[\"\']*\s*\)/Uims";
	/** @var array @conf_skip Container for sites info */
	public $info = array();

	/**
	*/
	function __construct ($skip_files_info = false) {
		$this->_get_info_from_db ();
		if (empty($skip_files_info)) {
			$this->_get_sites_info();
		}
	}

	/**
	* Get first sites info from db
	*/
	function _get_info_from_db () {
		if (!empty($this->info)) {
			return false;
		}
		// Get sites infos from db
		$sites_info_from_db = main()->get_data("sys_sites");
		// Get users sites paths
		foreach ((array)$sites_info_from_db as $A) {
			$tmp = $A;
			foreach ((array)$tmp as $k => $v) {
				$tmp[$k] = stripslashes($v);
			}
			$A = $tmp;
			// Skip sites with empty paths
			if (empty($A["real_path"]) || empty($A["web_path"])) {
				continue;
			}
			$web_path	= eval("return '".$A["web_path"]."';")."/";
			$web_path	= str_replace("\\", "/", str_replace("//", "/", $web_path));
			$real_path	= eval("return ".$A["real_path"].";")."/";
			$real_path	= str_replace("\\", "/", str_replace("//", "/", $real_path));
			// Skip wrong sites paths
			if ($real_path == "/" || !file_exists($real_path)) {
				continue;
			}
			// Store info
			$this->info[$A["id"]]["name"]		= $A["name"];
			$this->info[$A["id"]]["WEB_PATH"]	= $web_path;
			$this->info[$A["id"]]["REAL_PATH"]	= $real_path;
		}
	}

	/**
	* Get user sites detailed info (real_paths, web_paths, paths to images etc)
	*/
	function _get_sites_info () {
		// Process config files
		foreach ((array)$this->info as $site_id => $info) {
			$tmp_string = "";
			$found		= array();
			$config_path = $info["REAL_PATH"]. $this->_config_file_name;
			// Try to get config file contents
			if (!file_exists($config_path)) {
				$config_path = $info["REAL_PATH"]. $this->_index_file_name;
			}
			if (!file_exists($config_path)) {
				continue;
			}
			$tmp_string = file_get_contents($config_path);
			if (empty($tmp_string)) {
				continue;
			}
			// Get constants
			foreach ((array)$this->_consts_to_parse as $const_name) {
				$cur_pattern = str_replace("##CONST_NAME##", $const_name, $this->_get_const_pattern);
				if (!preg_match($cur_pattern, $tmp_string, $found)) {
					continue;
				}
				$this->info[$site_id][$const_name] = $found["1"];
			}
		}
	}

	/**
	* Get site current theme
	*/
	function _get_site_current_theme ($SITE_INFO = array()) {
		$index_file_path = $SITE_INFO["REAL_PATH"]. $this->_index_file_name;
		if (!file_exists($index_file_path)) {
			return false;
		}
		$tmp_string = file_get_contents($index_file_path);
		if (empty($tmp_string)) {
			return false;
		}
		// Try to get config item value
		$cur_pattern = str_replace("##CONST_NAME##", "DEFAULT_SKIN", $this->_get_const_pattern);
		if (preg_match($cur_pattern, $tmp_string, $found)) {
			return $found["1"];
		} else {
			return false;
		}
	}
}
