<?php

	/**
	* Create simple table with debug info
	*/
	function go () {
		$body = "";
		// Get debug hook from current $_GET["object"]
		$obj = module($_GET["object"]);
		if (is_object($obj) && in_array("_hook_debug", get_class_methods($obj))) {
			$hook_result = $obj->_hook_debug();
			if (is_array($hook_result)) {
				$body .= "<div class='debug_allow_close'><h5>".t($_GET["object"])."</h5><ol>";
				$_array_pairs = array(
					" "			=> "	",
					"	=>	"	=> " => ",
					"array	("	=> "array(",
				);
				foreach ((array)$hook_result as $id => $text) {
					$_prepared_text = "";
					if (is_array($text)) {
						$_prepared_text = str_replace(array_keys($_array_pairs), array_values($_array_pairs), var_export($text, 1));
						$_prepared_text = preg_replace("#=>\s+array\(#i", "=> array(", $_prepared_text);
					} else {
						$_prepared_text = "'".htmlspecialchars($text)."'";
					}
					$body .= "['".htmlspecialchars($id)."'] => <pre><small>".$_prepared_text."</small></pre>,";
				}
				$body .= "</ol></div>";
			} else {
				$body .= $hook_result;
			}
			unset($hook_result);
		}
		// Gather sub-methods
		$methods = array();
		$class_name = get_class($this);
		foreach ((array)get_class_methods($class_name) as $_method_name) {
			// Skip unwanted methods
			if (substr($_method_name, 0, strlen("_debug_")) != "_debug_" || $_method_name == $class_name || $_method_name == __FUNCTION__) {
				continue;
			}
			$methods[$_method_name] = $_method_name;
			$body .= $this->$_method_name();
		}

		// DO NOT REMOVE!!! Needed to correct display template tags in debug output
		$body = str_replace(array("{", "}"), array("&#123;", "&#125;"), $body);
		//-----------------------------------------------------------------------------
		// Do hide console if needed
		if (isset($_SESSION['hide_debug_console']) && $_SESSION['hide_debug_console']) {
			$body = "";
		}
		//-----------------------------------------------------------------------------
		// !!! Needed to be on the bottom of the page
		$i18n_vars = _class('i18n')->_I18N_VARS;
		if ($this->_SHOW_I18N_VARS && !empty($i18n_vars)) {
			// Prepare JS array
			$body .= "<script type='text/javascript'>";

			$body .= "var _i18n_for_page = {";
			ksort($i18n_vars);
			foreach ((array)$i18n_vars as $_var_name => $_var_value) {
				$_var_name	= strtolower($_var_name);
				$_var_name	= str_replace("_", " ", $_var_name);
				$_var_name	= str_replace(array("\"","",""), array("\\\"","",""), $_var_name);
				$_var_value	= str_replace(array("\"","",""), array("\\\"","",""), $_var_value);
				$body .= "\""._prepare_html($_var_name)."\":\""._prepare_html($_var_value)."\",";
			}
			$body .= "__dummy:null};";

			$not_translated = _class('i18n')->_NOT_TRANSLATED;
			if (!empty($not_translated)) {
				ksort($not_translated);
				$body .= "var _i18n_not_translated = {";
				foreach ((array)$not_translated as $_var_name => $_hits) {
					$_var_name	= strtolower($_var_name);
					$_var_name	= str_replace("_", " ", $_var_name);
					$_var_name = str_replace(array("\"","",""), array("\\\"","",""), $_var_name);
					$body .= "\""._prepare_html($_var_name)."\":\"".intval($_hits)."\",";
				}
				$body .= "__dummy:null};";
			}

			$body .= "</script>";
		}
		// Add ability to slideup/slidedown different debug blocks and remeber selection in cookie
		$body .= tpl()->parse("system/debug_info_js");

		if ($this->ADD_ADMIN_LINKS) {
			$body = "<a href='".process_url("./?object=test")."'>Test module</a>".$body;
		}
		return $body;
	}

	/**
	*/
	function _do_debug_db_shutdown_queries ($DB_CONNECTION) {
		if (!$this->_SHOW_SHUTDOWN_QUERIES || empty($DB_CONNECTION->_SHUTDOWN_QUERIES)) {
			return "";
		}
		$body = "";
		$body .= "<div class='debug_allow_close'><h5>".t("SHUTDOWN QUERIES")."</h5><ol>";
		foreach ((array)$DB_CONNECTION->_SHUTDOWN_QUERIES as $text) {
			$text = trim($text);
			// Cut comment
			if (substr($text, 0, 2) == "--") {
				$text = substr($text, strpos($text, ""));
			}
			$body .= "<li>".htmlspecialchars($text)."</li>";
		}
		$body .= "</ol></div>";
		return $body;
	}

	/**
	*/
	function _do_debug_db_cached_queries ($DB_CONNECTION) {
		if (!$this->_SHOW_CACHED_QUERIES || empty($DB_CONNECTION->_db_results_cache)) {
			return "";
		}
		$body = "";
		$body .= "<div class='debug_allow_close'><h5>".t("CACHED QUERIES")."</h5><ol>";
		foreach ((array)$DB_CONNECTION->_db_results_cache as $query => $data) {
			$body .= "<li>".htmlspecialchars($query)."</li>";
		}
		$body .= "</ol></div>";
		return $body;
	}
	
	/**
	*/
	function _debug_sphinx () {
		if (!$this->_SHOW_SPHINX) {
			return "";
		}
#		$sphinx_debug = debug('sphinx') || $GLOBALS['_SPHINX_QL_DEBUG'];
		$sphinx_debug = debug('sphinx');
		if (!$sphinx_debug) {
			return "";
		}
		$body = "";
		$body .= "<div class='debug_allow_close'><h5>".t("Sphinx Search QL")."</h5>";
		$total_time = 0;

		$body .= "".SPHINX_HOST.":".SPHINX_PORT."";
		$sphinx_connect = common()->sphinx_connect;
		if (!$sphinx_connect) {
			$sphinx_connect = $GLOBALS["sphinx_connect"];
		}
		if (!isset($sphinx_connect)) {
			$sphinx_connect = mysql_connect(SPHINX_HOST.":".SPHINX_PORT);
		}
		if ($sphinx_connect) {
			$server_version = mysql_get_server_info($sphinx_connect);
			$body .= ", SERVER VERSION: ".$server_version."";
		}

		$body .= "<table border='1'>";
		$body .= "<tr>";
		$body .= "<td>#</td>";
		$body .= "<td>Time</td>";
		$body .= "<td>Rows</td>";
		$body .= "<td>Query</td>";
		$body .= "</tr>";


		foreach ((array)$sphinx_debug as $val) {
#			$_cur_trace = $val["trace"][0];
			$_cur_trace = $val["trace"];

			preg_match('/SELECT[\s\t]+.+[\s\t]+FROM[\s\t]+([a-z0-9\_]+)[\s\t]+WHERE[\s\t]+/ims', $val["query"], $m);
			$desc = array();
			if ($m[1]) {
				$desc_raw = common()->sphinx_query("DESCRIBE ".$m[1]);
				foreach ((array)$desc_raw as $v) {
					$desc[$v['Field']] = $v['Type'];
				}
			}

			$body .= "<tr>";
			$body .= "<td><i>".++$i."</td>";
			$body .= "<td>".common()->_format_time_value($val["time"]). ($val['cached'] ? "<small style='color:grey'>(CACHED)</small>" : "")."</td>";
			$body .= "<td><i>".$val["count"]."</td>";
			$body .= "<td ".(!empty($val['error']) ? " style='color:red;font-weight:bold;' " : "").">".str_replace(",", ", ", $val["query"])
					.(!empty($val['error']) ? "<small style='color:red;'># ERROR: ".print_r($val["error"], 1)."</small>" : "")
					.(!empty($val['meta']) ? "<small style='color:grey;'># META: ".print_r($val["meta"], 1)."</small>" : "")
					.(!empty($desc) ? "<small style='color:grey;'># DESCRIBE INDEX: ".print_r($desc, 1).")</small>" : "")
#					.(!empty($_cur_trace) ? "<small style='color:blue;'># ".$this->_admin_link("edit_file", $_cur_trace["file"])." on line ".$_cur_trace["line"]." (".($_cur_trace["object"] ? $_cur_trace["object"]."->" : "").$_cur_trace["function"].")</small>" : "")
					.(!empty($_cur_trace) ? "<pre style='color:blue;'><small>"._prepare_html($_cur_trace)."</small></pre>" : "")
					."</td>";
			$body .= "</tr>";

			$total_time += $val["time"];
		}
		$body .= "</table>";
		$body .= "<i>".t("Total time").": ".common()->_format_time_value($total_time)." secs";
		$status = array();
		foreach((array)common()->sphinx_query("SHOW STATUS") as $v) {
			$status[$v['Variable_name']] = $v['Value'];
		}
		$body .= "</div>";
		if ($status) {
			$body .= $this->_show_table(t("SPHINX STATUS"), $status, 5);
		}
		return $body;
	}

	/**
	*/
	function _debug_ssh () {
		if (!$this->_SHOW_SSH) {
			return "";
		}
		$ssh_debug = _class('main')->modules['debug']->_debug;
		if (empty($ssh_debug)) {
			return "";
		}
		$body = "";
		$body .= "<div class='debug_allow_close'><h5>".t("SSH")."</h5><ul>";
		$body .= "connect_time: ".common()->_format_time_value($ssh_debug["connect_time"])." secs";
		foreach ((array)$ssh_debug["exec"] as $i => $val) {
			$body .= "<li><i>".($i + 1).". ".$val."</li>";
		}
		$body .= "<i>".t("Total time").": ".common()->_format_time_value($ssh_debug["time_sum"] + $ssh_debug["connect_time"])." secs";
		$body .= "</ul></div>";
		return $body;
	}

	/**
	*/
	function _debug_meta_tags () {
		if (!$this->_SHOW_META_TAGS) {
			return "";
		}
		$body = "";
		$body .= "<div class='debug_allow_close'><h5>".t("META Tags")."</h5><ol>";
		foreach ((array)debug('_DEBUG_META') as $id => $text) {
			$body .= "['".htmlspecialchars($id)."'] => ".(is_array($text) ? print_r($text, 1) : "'".htmlspecialchars($text)."'").",";
		}
		$body .= "</ol></div>";
		return $body;
	}

	/**
	*/
	function _debug_memcached () {
		if (!$this->_SHOW_MEMCACHED_INFO) {
			return "";
		}
		$body = "";
		$mc_obj = cache_memcached_connect();
		if (!is_object($mc_obj)) {
			return "";
		}
		if (is_object($mc_obj)) {
			$memcached_all_stats = $mc_obj->getExtendedStats();
// TODO: expand stats for all servers in the pool
			$memcached_stats = current($memcached_all_stats);
			$_num_stats = count($memcached_stats);
			$_num_cols	= 2;
			$_items_in_column	= ceil($_num_stats / $_num_cols);

			$body .= $this->_show_table(t("MEMCACHED STATS"), $memcached_stats, $_items_in_column);
		}
		return $body;
	}

	/**
	*/
	function _debug_eaccelerator () {
		if (!$this->_SHOW_EACCELERATOR_INFO || !function_exists('eaccelerator_info')) {
			return "";
		}
		$body = "";
		$eaccel_stats = eaccelerator_info();
		foreach ((array)ini_get_all('eaccelerator') as $_k => $_v) {
			$eaccel_stats[$_k] = $_v["local_value"];
		}
// TODO: eaccelerator_list_keys()
		$_num_stats = count($eaccel_stats);
		$_num_cols	= 2;
		$_items_in_column	= ceil($_num_stats / $_num_cols);
		$body .= $this->_show_table(t("EACCELERATOR STATS"), $eaccel_stats, $_items_in_column);
		return $body;
	}

	/**
	*/
	function _debug_custom_replace () {
		if (!$this->_SHOW_CUSTOM_REPLACED || empty($GLOBALS["CUSTOM_REPLACED_DEBUG"])) {
			return "";
		}
		$body = "";
		$body .= "<div class='debug_allow_close'><h5>".t("custom_replaced_items")."</h5><ol>";
		foreach ((array)$GLOBALS["CUSTOM_REPLACED_DEBUG"] as $k => $v) {
			$body .= "<li>PATTERN:  "._prepare_html($v["pattern"])
				." REPLACE_FIRST:  "._prepare_html($v["replace_first"])
				." REPLACE_WORDS:  "._prepare_html($v["replace_words"])
				." REPLACE_EVALED:  "._prepare_html($v["replace_evaled"])
				." REPLACE_LAST:  "._prepare_html($v["replace_last"])
			."</li>";
		}
		$body .= "</ol><i>".t("Custom Replace processing time")
			.": ".common()->_format_time_value($GLOBALS['custom_replace_exec_time'])
			." <span>sec</span></div>";
		return $body;
	}

	/**
	*/
	function _debug_resize_images () {
		if (!$this->_SHOW_RESIZED_IMAGES_LOG || empty($GLOBALS['_RESIZED_IMAGES_LOG'])) {
			return "";
		}
		$body = "";
		$body .= "<div class='debug_allow_close'><h5>".t("Resized images")."</h5><ol>";
		foreach ((array)$GLOBALS['_RESIZED_IMAGES_LOG'] as $v) {
			$body .= "<li><small>".nl2br(_prepare_html($v))."</small></li>";
		}
		$body .= "</ol></div>";
		return $body;
	}

	/**
	*/
	function _debug_output_cache () {
		if (!$this->_SHOW_OUTPUT_CACHE_INFO) {
			return "";
		}
		$output_cache_debug = conf('output_cache');
		if (!$output_cache_debug) {
			return "";
		}
		$body = "";
		$body .= "<div class='debug_allow_close'><h5>".t("Output cache info")."</h5><ol>";
		$body .= "<li><i>".t("Cache file size").": ".$output_cache_debug['size']." bytes</li>";
		$body .= "<li><i>".t("Cache processing time").": ".common()->_format_time_value($output_cache_debug['exec_time'])." sec</li>";
		$body .= "</ol></div>";
		return $body;
	}

	/**
	*/
	function _debug_declared_classes () {
		if (!$this->_SHOW_DECLARED_CLASSES) {
			return "";
		}
		$body = "";
		$body .= "<div class='debug_allow_close'><h5>".t("declared_classes")."</h5><ol>";
		$classes = get_declared_classes();
		foreach ((array)$classes as $name) {
			$body .= "<li>".$name."</li>";
		}
		$body .= "</ol></div>";
		return $body;
	}

	/**
	*/
	function _debug_not_translated () {
		$lang = conf("language");
		$not_translated = _class('i18n')->_NOT_TRANSLATED[$lang];
		if (!$this->_SHOW_NOT_TRANSLATED || empty($not_translated)) {
			return "";
		}
		$body = "";
		ksort($not_translated);
		$_num_items = count($not_translated);
		$_num_cols	= 4;
		$_items_in_column	= ceil($_num_items / $_num_cols);
		$data = array();
		foreach ((array)$not_translated as $k => $v) {
			$data[$this->_admin_link("edit_i18n", $k)] = $v;
		}
		$body .= $this->_show_table(t("NOT TRANSLATED VARS"), $data, $_items_in_column);
		$this->_log_not_translated_to_file();
		return $body;
	}

	/**
	*/
	function _debug_i18n () {
		$lang = conf("language");
		$i18n_vars = _class('i18n')->_I18N_VARS;
		if (!$this->_SHOW_I18N_VARS || empty($i18n_vars[$lang])) {
			return "";
		}
		$body = "";

		$t_calls = t("I18N CALLS");
		$t_vars = t("I18N VARS");

		$add_text = t("translate time").": ".common()->_format_time_value(_class('i18n')->_tr_total_time)." sec";

		ksort($i18n_vars[$lang]);
		$_num_items = count($i18n_vars[$lang]);
		$_num_cols	= 3;
		$_items_in_column	= ceil($_num_items / $_num_cols);

		$data = array();
		foreach ((array)$i18n_vars[$lang] as $k => $v) {
			$data[$this->_admin_link("edit_i18n", $k)] = $v;
		}
		$body .= $this->_show_table($t_vars, $data, $_items_in_column);

		$tmp = array();
		$tr_time	= _class('i18n')->_tr_time;
		$tr_calls	= _class('i18n')->_tr_calls;
		foreach ((array)$tr_time[$lang] as $k => $v) {
			$tmp[$this->_admin_link("edit_i18n", $k)] = $tr_calls[$lang][$k]."|".common()->_format_time_value($v);
		}
		$_num_cols	= 5;
		$_items_in_column	= ceil($_num_items / $_num_cols);
		$body .= $this->_show_table($t_calls, $tmp, $_items_in_column, $add_text);
		return $body;
	}

	/**
	*/
	function _debug_mem_usage () {
		if (!$this->_SHOW_MEM_USAGE) {
			return "";
		}
		$body = "";
		$body .= "<div class='debug_allow_close'><h5>".t("Memory Usage")."</h5><ol>";
		$body .= t("Used memory size").": ".$this->_get_mem_usage()."";
		$body .= "</ol></div>";
		return $body;
	}

	/**
	*/
	function _debug_compress_output () {
		if (!$this->_SHOW_COMPRESS_INFO || !tpl()->COMPRESS_OUTPUT || main()->NO_GRAPHICS) {
			return "";
		}
		$body = "";
		$body .= "<div class='debug_allow_close'><h5>".t("Simple compress text")."</h5><ol>";
		$body .= "<li>".t("Main content size original").": ".debug('compress_output_size_1')." bytes</li>";
		$body .= "<li>".t("Main content size compressed").": ".debug('compress_output_size_2')." bytes</li>";
		$body .= "<li>".t("Compress ratio").": ".(debug('compress_output_size_2') ? round(debug('compress_output_size_1') / debug('compress_output_size_2') * 100, 0) : 0)."%</li>";
		$body .= "</ol></div>";
		return $body;
	}

	/**
	*/
	function _debug_gzip () {
		if (!$this->_SHOW_GZIP_INFO || !conf("GZIP_ENABLED")) {
			return "";
		}
		$body = "";
		$body .= "<div class='debug_allow_close'><h5>".t("GZIP is enabled")."</h5><ol>";
		$body .= "<li>".t("Main content size original").": ".debug('page_size_original')." bytes</li>";
		$body .= "<li>".t("Main content size gzipped approx").": ".debug('page_size_gzipped')." bytes</li>";
		$body .= "<li>".t("GZIP compress ratio approx").": ".round(debug('page_size_original') / debug('page_size_gzipped') * 100, 0)."%</li>";
		$body .= "</ol></div>";
		return $body;
	}

	/**
	*/
	function _debug_not_replaced_stpl () {
		if (!$this->_NOT_REPLACED_STPL_TAGS || !isset(tpl()->CACHE["main"]["string"])) {
			return "";
		}
		$body = "";
		if (preg_match_all("/\{[a-z0-9\_\-]{1,64}\}/ims", tpl()->CACHE["main"]["string"], $m)) {
			$body .= "<div class='debug_allow_close'><h5>".t("Not processed STPL tags")."</h5><ol>";
			foreach ((array)$m[0] as $v) {
				$v = str_replace(array("{","}"), "", $v);
				$not_replaced[$v] = $v;
			}
			foreach ((array)$not_replaced as $v) {
				$stpls = array();
				// Try to find stpls where this tag appeared
				foreach ((array)tpl()->CACHE as $name => $info) {
					if (!isset($info["string"])) {
						continue;
					}
					if (false !== strpos($info["string"], $v)) {
						$stpls[] = $name;
					}
				}
				$body .= "'".htmlspecialchars($v)."' (".implode(", ", $stpls).")";
			}
			$body .= "</ol></div>";
		}
		return $body;
	}

	/**
	*/
	function _my_wrap($str, $width=40, $break="") { 
		return preg_replace('#(\S{'.$width.',})#e', "chunk_split('$1', ".$width.", '".$break."')", $str); 
	}

	/**
	* Collect unique not translated vars into log file
	*/
	function _log_not_translated_to_file () {
		$f = $this->_NOT_TRANSLATED_FILE;
		$existed_vars = array();
		if (file_exists($f)) {
			$existed_vars = eval("return ".substr(file_get_contents($f), strlen($this->_auto_header), -strlen($this->_auto_footer)).";");
		}
		$something_changed = false;
		$lang = conf("language");
		foreach ((array)_class('i18n')->_NOT_TRANSLATED[$lang] as $var_name => $_hits) {
			$var_name = addslashes(stripslashes(str_replace(" ","_",strtolower(trim($var_name)))));
			if (empty($var_name)) {
				continue;
			}
			if (!isset($existed_vars[$var_name])) {
				$existed_vars[$var_name] = $var_name;
				$something_changed = true;
			}
		}
		if (!$something_changed) {
			return false;
		}
		if (is_array($existed_vars)) {
			ksort($existed_vars);
		}
		$_dir = dirname($this->_NOT_TRANSLATED_FILE);
		if (!file_exists($_dir)) {
			_mkdir_m($_dir);
		}
		file_put_contents($f, $this->_auto_header. "\$data = ".var_export($existed_vars, 1).";". $this->_auto_footer);
	}

	/**
	* Display memory usage by the script
	*
	* Please note that you'll need the pslist.exe utility from http://www.sysinternals.com/Utilities/PsTools.html
	* This is because win/2000 itself does not provide a task list utility.
	* 
	* @access	private
	* @return	string
	*/
	function _get_mem_usage() {
		// try to use PHP build in function
		if (function_exists('memory_get_usage')) {
			return memory_get_usage();
		}
		// No memory functionality available at all
		return '<b style="color: red;">no value';
	}

	/**
	* Display templates tree
	*/
	function _show_stpls_tree($parent = "main", $level = 1) {
		$body = "";
		foreach ((array)debug('STPL_PARENTS') as $_name => $_stpl_parent) {
			if ($_stpl_parent != $parent) {
				continue;
			}
			$body .= "<li>".$this->_admin_link("edit_stpl", $_name)."</li>";
			$body .= "<ul>".$this->_show_stpls_tree($_name, $level + 1)."</ul>";
		}
		return $body;
	}

