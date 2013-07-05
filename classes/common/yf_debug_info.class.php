<?php

/**
* Show debug info
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_debug_info {

	/** @var string */
	public $_file_prefix				= "logs/not_translated_";
	/** @var string @conf_skip */
	public $_file_ext					= ".php";
	/** @var string @conf_skip */
	public $_auto_header				= "<? die('go away!');\n";
	/** @var string @conf_skip */
	public $_auto_footer				= "\n?>";
	/** @var bool */
	public $_SHOW_QUERY_LOG			= 1;
	/** @var bool */
	public $_SHOW_SHUTDOWN_QUERIES		= 1;
	/** @var bool */
	public $_SHOW_CACHED_QUERIES		= 1;
	/** @var bool */
	public $_SHOW_DB_SESSION_STATS		= 1;
	/** @var bool */
	public $_SHOW_DB_EXPLAIN_QUERY		= 1;
	/** @var bool */
	public $_SHOW_SPHINX				= 1;
	/** @var bool */
	public $_SHOW_SSH					= 1;
	/** @var bool */
	public $_SHOW_TPLS					= 1;
	/** @var bool */
	public $_SHOW_STPLS				= 1;
	/** @var bool */
	public $_SHOW_REWRITE_INFO			= 1;
	/** @var bool */
	public $_SHOW_CUSTOM_REPLACED		= 1;
	/** @var bool */
	public $_SHOW_OUTPUT_CACHE_INFO	= 1;
	/** @var bool */
	public $_SHOW_RESIZED_IMAGES_LOG	= 1;
	/** @var bool */
	public $_SHOW_INCLUDED_FILES		= 1;
	/** @var bool */
	public $_SHOW_LOADED_MODULES		= 1;
	/** @var bool */
	public $_INCLUDED_SKIP_CACHE		= 0;
	/** @var bool */
	public $_SHOW_META_TAGS			= 1;
	/** @var bool */
	public $_SHOW_MEMCACHED_INFO		= 0;
	/** @var bool */
	public $_SHOW_EACCELERATOR_INFO	= 1;
	/** @var bool */
	public $_SHOW_XCACHE_INFO			= 1;
	/** @var bool */
	public $_SHOW_MAIN_GET_DATA		= 1;
	/** @var bool */
	public $_SHOW_CORE_CACHE			= 1;
	/** @var bool */
	public $_SHOW_MAIN_EXECUTE			= 1;
	/** @var bool */
	public $_SHOW_SEND_MAIL			= 1;
	/** @var bool */
	public $_SHOW_DECLARED_CLASSES		= 0;
	/** @var bool */
	public $_SHOW_NOT_TRANSLATED		= 0;
	/** @var bool */
	public $_SHOW_I18N_VARS			= 0;
	/** @var bool */
	public $_SHOW_COMPRESS_INFO		= 1;
	/** @var bool */
	public $_SHOW_GZIP_INFO			= 1;
	/** @var bool */
	public $_SHOW_MEM_USAGE			= 1;
	/** @var string */
	public $_NOT_TRANSLATED_FILE		= "";
	/** @var bool */
	public $_NOT_REPLACED_STPL_TAGS	= 1;
	/** @var bool */
	public $_SHOW_GET_DATA				= 1;
	/** @var bool */
	public $_SHOW_POST_DATA			= 1;
	/** @var bool */
	public $_SHOW_COOKIE_DATA			= 1;
	/** @var bool */
	public $_SHOW_REQUEST_DATA			= 0;
	/** @var bool */
	public $_SHOW_SESSION_DATA			= 1;
	/** @var bool */
	public $_SHOW_FILES_DATA			= 1;
	/** @var bool */
	public $_SHOW_SERVER_DATA			= 1;
	/** @var bool */
	public $_SHOW_ENV_DATA				= 0;
	/** @var bool */
	public $_SHOW_SETTINGS				= 1;
	/** @var bool */
	public $_SHOW_PHP_INI				= 0;
	/** @var bool Store db queries to file */
	public $LOG_QUERIES_TO_FILE		= 0;
	/** @var bool Store slow db queries to file */
	public $LOG_SLOW_QUERIES_TO_FILE	= 0;
	/** @var bool Log queries file name */
	public $LOG_QUERIES_FILE_NAME		= "db_queries.log";
	/** @var bool Log slow queries file name */
	public $LOG_SLOW_QUERIES_FILE_NAME	= "slow_queries.log";
	/** @var float */
	public $SLOW_QUERIES_TIME_LIMIT	= 0.2;
	/** @var bool */
	public $SORT_TEMPLATES_BY_NAME		= 1;
	/** @var bool */
	public $ADD_ADMIN_LINKS			= true;
	/** @var bool */
	public $ADMIN_PATHS				= array(
		"edit_stpl"		=> "object=template_editor&action=edit_stpl&location=framework&theme={{THEME}}&name={{ID}}",
		"edit_i18n"		=> "object=locale_editor&action=edit_var&id={{ID}}",
		"edit_file"		=> "object=file_manager&action=edit_item&id={{ID}}",
		"show_db_table"	=> "object=db_parser&table={{ID}}",
		"sql_query"		=> "object=db_manager&action=import&id={{ID}}",
		"link"			=> "{{ID}}",
	);

	/**
	* Catch missing method call
	*/
	function __call($name, $arguments) {
		trigger_error(__CLASS__.": No method ".$name, E_USER_WARNING);
		return false;
	}

	/**
	* Constructor
	*/
	function _init () {
		// Add full path to the log file
		$this->_NOT_TRANSLATED_FILE = INCLUDE_PATH. $this->_file_prefix. conf('language'). $this->_file_ext;
	}

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
	function _debug_db_queries () {
		$body = "";
		$instances_trace = debug('db_instances_trace');
		$instances_trace = debug('db_instances_trace2');
		foreach ((array)debug('db_instances') as $k => $v) {
			$connect_trace = array();
			if (isset($instances_trace[$k][0])) {
				$connect_trace = $instances_trace[$k][0];
			}
			$connect_trace2 = array();
			if (isset($instances_trace2[$k][0])) {
				$connect_trace2 = $instances_trace2[$k][0];
			}
			if ($this->_SHOW_QUERY_LOG) {
				$body .= $this->_do_debug_db_connection_queries($v, $connect_trace, $connect_trace2);
			}
			if ($this->_SHOW_SHUTDOWN_QUERIES) {
				$body .= $this->_do_debug_db_shutdown_queries($v, $connect_trace, $connect_trace2);
			}
			if ($this->_SHOW_CACHED_QUERIES) {
				$body .= $this->_do_debug_db_cached_queries($v, $connect_trace, $connect_trace2);
			}
			if ($this->_SHOW_DB_SESSION_STATS) {
				$body .= $this->_do_debug_db_session_stats($v, $connect_trace, $connect_trace2);
			}
		}
		return $body;
	}

	/**
	*/
	function _do_debug_db_connection_queries ($DB_CONNECTION, $connect_trace = array(), $connect_trace2 = "") {
		if (!$this->_SHOW_QUERY_LOG || !is_object($DB_CONNECTION) || !is_array($DB_CONNECTION->QUERY_LOG)) {
			return "";
		}
		$body = "";
//TODO:
//print_r(db()->query_fetch_all("SHOW WARNINGS"));
//print_r(db()->query_fetch_all("SHOW ERRORS"));
//print_r(db()->query_fetch_all("SELECT @@warning_count"));
		// Save queries list (for skipping queries with "explain")
		$db_queries_list = $DB_CONNECTION->QUERY_LOG;
		// Get explain info about queries
		if ($this->_SHOW_DB_EXPLAIN_QUERY && !empty($db_queries_list) && substr($DB_CONNECTION->DB_TYPE, 0, 5) == "mysql") {
			foreach ((array)$db_queries_list as $id => $_query_text) {
				$_query_text = trim($_query_text);
				// Cut comment
				if (substr($_query_text, 0, 2) == "--") {
					$_query_text = substr($_query_text, strpos($_query_text, ""));
				}
				$_query_text = preg_replace("/[\s]{2,}/ims", " ", str_replace("\t", " ", trim($_query_text)));
				if (preg_match("/^[\(]*select/ims", $_query_text)) {
					$db_explain_results[$id] = $DB_CONNECTION->query_fetch_all("EXPLAIN ".$_query_text, -1);
				}
			}
		}
		$total_queries_exec_time = 0;
		$body .= "<div class='debug_allow_close'><h5>".t("QUERY_LOG")."  ("
			.($DB_CONNECTION->DB_SSL ? "SSL " : "")
			.$DB_CONNECTION->DB_TYPE
			."://".$DB_CONNECTION->DB_USER
			."@".$DB_CONNECTION->DB_HOST
			.($DB_CONNECTION->DB_PORT ? ":".$DB_CONNECTION->DB_PORT : "")
			."/".$DB_CONNECTION->DB_NAME
			.($DB_CONNECTION->DB_CHARSET ? "?charset=".$DB_CONNECTION->DB_CHARSET : "")
			.($DB_CONNECTION->DB_SOCKET ? "?socket=".$DB_CONNECTION->DB_SOCKET : "")
			.")</h5>";
#		$body .= $connect_trace ? "<small>".$this->_admin_link("edit_file", $connect_trace["file"])." on line ".$connect_trace["line"]." (".$connect_trace["object"]."->".$connect_trace["function"].(!empty($connect_trace["inside_method"]) ? " inside ".$connect_trace["inside_method"] : "").")</small>" : "";
		$body .= $connect_trace2 ? "<small>"._prepare_html($connect_trace2)."</small>" : "";
		$body .= "<ol>";
		foreach ((array)$db_queries_list as $id => $text) {
			$text = trim($text);
			// Cut comment
			if (substr($text, 0, 2) == "--") {
				$text = substr($text, strpos($text, ""));
			}
			$total_queries_exec_time += $DB_CONNECTION->QUERY_EXEC_TIME[$id];
			$_cur_trace = $DB_CONNECTION->QUERY_BACKTRACE_LOG[$id];
			$_cur_trace2 = $DB_CONNECTION->QUERY_BACKTRACE_LOG2[$id];
			$_cur_explain = isset($db_explain_results[$id]) ? $this->_format_db_explain_result($db_explain_results[$id]) : "";
			$_sql_type = strtoupper(rtrim(substr(ltrim($text), 0, 7)));

			$orig_sql = $text;
			$text = htmlspecialchars($text);
			$replace = array(
				','	=> ', ', 
			);
			$text = str_replace(array_keys($replace), array_values($replace), $text);
			$text = preg_replace("#`(".$DB_CONNECTION->DB_PREFIX."[a-z0-9_]+)`#imse", "'`'.\$this->_admin_link('show_db_table', '\\1').'`'", $text);

			$exec_time = common()->_format_time_value($DB_CONNECTION->QUERY_EXEC_TIME[$id]);
			$admin_link = $this->_admin_link("sql_query", rawurlencode($orig_sql), true);
			if ($admin_link && $this->ADD_ADMIN_LINKS) {
				$exec_time = "<a href='".$admin_link."'>".$exec_time."</a>";
			}

			$body .= "<li>"
					."/* <small><b style='".($DB_CONNECTION->QUERY_EXEC_TIME[$id] >= $this->SLOW_QUERIES_TIME_LIMIT ? "color:red;" : "")."'>".$exec_time." sec</small>;*/ "
					.$text."; "
					.(isset($DB_CONNECTION->QUERY_AFFECTED_ROWS[$orig_sql]) ? "<small># ".($_sql_type == "SELECT" ? "num" : "affected")." rows: ".intval($DB_CONNECTION->QUERY_AFFECTED_ROWS[$orig_sql])."</small>" : "")
					.(!empty($_cur_trace2) ? "<small><pre>"._prepare_html($_cur_trace2)."</pre></small>" : "")
#					.(!empty($_cur_trace) ? "<small># "/*.$this->_pretty_trace($_cur_trace)."#"*/.$this->_admin_link("edit_file", $_cur_trace["file"])." on line ".$_cur_trace["line"]." (db->".$_cur_trace["function"].(!empty($_cur_trace["inside_method"]) ? " inside ".$_cur_trace["inside_method"] : "").")</small>" : "")
					.(!empty($_cur_explain) ? "<div>".$_cur_explain."</div>" : "")
				."</li>";
		}
		$body .= "</ol>";
		if (!$DB_CONNECTION->_tried_to_connect) {
			$body .= t("db not used")."";
		} else {
			$body .= "<i>".t("total_exec_time").": ".common()->_format_time_value($total_queries_exec_time)."<span> sec";
			$body .= "<i>".t("connect_time").": ".common()->_format_time_value($DB_CONNECTION->_connection_time)."<span> sec";
		}
		$body .= "</span></div>";
		// Memory is useful
		unset($db_explain_results);
		unset($db_queries_list);

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
	function _do_debug_db_session_stats ($DB_CONNECTION) {
// Temporary disabled by Yuri due to rarely usage
/*
		if (!$this->_SHOW_DB_SESSION_STATS || (empty($DB_CONNECTION->QUERY_LOG) && empty($DB_CONNECTION->_SHUTDOWN_QUERIES))) {
			return "";
		}
		if (substr($DB_CONNECTION->DB_TYPE, 0, 5) != "mysql" || !version_compare($DB_CONNECTION->get_server_version(), "5.0.0", ">")) {
			return "";
		}
		$body = "";
		$_skip_stats = array(
		//	"innodb_",
			"ssl_",
			"com_",
		);
		$db_session_stats = array();
		foreach ((array)$DB_CONNECTION->query_fetch_all("SHOW SESSION STATUS") as $_info) {
			foreach ((array)$_skip_stats as $_skip_prefix) {
				if (strtolower(substr($_info["Variable_name"], 0, strlen($_skip_prefix))) == strtolower($_skip_prefix)) {
					continue 2;
				}
			}
			$db_session_stats[$_info["Variable_name"]] = $_info["Value"];
		}
		$_num_stats = count($db_session_stats);
		$_num_cols	= 5;
		$_items_in_column	= ceil($_num_stats / $_num_cols);

		$body .= $this->_show_table(t("QUERIES STATS"), $db_session_stats, $_items_in_column);
		return $body;
*/
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
	function _debug_xcache () {
		if (!$this->_SHOW_XCACHE_INFO) {
			return "";
		}
		$body = "";
// TODO
	}

	/**
	*/
	function _debug_stpls () {
		if (!$this->_SHOW_STPLS || !count(tpl()->CACHE)) {
			return "";
		}
		$body = "";
		$total_size = 0;
		$counter	= 1;
		$total_stpls_exec_time = 0;
		$body .= "<div class='debug_allow_close'><h5>".t("simple_templates")."</h5>";
		$body .= "<table class='table table-bordered table-striped table-hover'>";
		$body .= 
			"<thead>
				<th></th>
				<th>".t("name")."</th>
				<th>".t("storage")."</th>
				<th>".t("calls")."</th>
				<td>".t("size")."</th>
				<td>".t("exec_time_sum")."</th>
				<td>".t("trace")."</th>
			</thead>";
		// Do sort templates by name if needed
		if ($this->SORT_TEMPLATES_BY_NAME && !empty(tpl()->CACHE)) {
			ksort(tpl()->CACHE);
		}
		// Process templates
		foreach ((array)tpl()->CACHE as $k => $v) {
			if (empty($v['calls'])) {
				continue;
			}
			$stpl_inline_edit = "";
			// Inline templates debug mode
			if (MAIN_TYPE_USER && tpl()->ALLOW_INLINE_DEBUG) {
				$stpl_inline_edit = " stpl_name='".$k."' ";
			}
			$cur_size = strlen($v['string']);
			$total_size += $cur_size;
			$total_stpls_exec_time += (float)$v["exec_time"];
			$cur_trace = "";
			foreach ((array)debug('STPL_TRACES::'.$k) as $_cur_trace) {
				if (isset($_last_sources[$k][$_cur_trace["file"].":".$_cur_trace["line"]])) {
					continue;
				}
				$_last_sources[$k][$_cur_trace["file"].":".$_cur_trace["line"]] = 1;
				$cur_trace .= $this->_admin_link("edit_file", $_cur_trace["file"])." on line ".$_cur_trace["line"].($_cur_trace["inside_method"] ? " (".$_cur_trace["inside_method"].")" : "")."";
			}
			$body .= 
				"<tr>
					<td>".$counter++."</td>
					<td ".$stpl_inline_edit.">".$this->_admin_link("edit_stpl", $k)."</td>
					<td>".$v["storage"]."</td>
					<td>".$v['calls']."</td>
					<td>".$cur_size."</td>
					<td>".common()->_format_time_value($v["exec_time"])."</td>
					<td>".$cur_trace."</td>
				</tr>";
		}
		$body .= "</table>
			<div>".t("used_templates_size").": ".$total_size." bytes, 
				".t("total_exec_time").": ".common()->_format_time_value($total_stpls_exec_time)." seconds
			</div>
		</div>";

		// Display calls tree
		if (debug('STPL_PARENTS')) {
			$body .= "<div class='debug_allow_close'><h5>".t("STPL Tree")."</h5>";
			$body .= "<ul><li>main</li><ul>";
			$body .= $this->_show_stpls_tree();
			$body .= "</ul></ul></div>";
		}

		// Debug output of the template vars
		if (debug('STPL_REPLACE_VARS')) {
			$body .= "<div class='debug_allow_close'><h5>".t("Templates vars")."</h5>";
			foreach ((array)debug('STPL_REPLACE_VARS') as $stpl_name => $calls) {
				$body .= "".$stpl_name."";
				$body .= "<div>";
				foreach ((array)$calls as $num => $vars) {
					ksort($vars);
					$body .= "<div>";
					if (count($calls) > 1) {
						$body .= "<i>".$num."";
					}
					$body .= "<table class='table table-bordered table-striped table-hover'>";
					foreach ((array)$vars as $n => $v) {
						$body .= "<tr><td>".$n."</td><td>".htmlspecialchars(print_r($v, 1))."</td></tr>";
					}
					$body .= "</table>";
					$body .= "</div>";
				}
				$body .= "<br style='clear:both' />";
				$body .= "</div>";
			}
			$body .= "</div>";
		}
		return $body;
	}

	/**
	*/
	function _debug_rewrite () {
		if (!$this->_SHOW_REWRITE_INFO) {
			return "";
		}
#		$data = debug('rewrite') || $GLOBALS["REWRITE_DEBUG"];
		$data = $GLOBALS["REWRITE_DEBUG"];
		if (empty($data)) {
			return "";
		}
		$body = "";
		$body .= "<div class='debug_allow_close'><h5>".t("rewrite_links_info")."</h5><ol>";
		$data["SOURCE"]		= array_unique($data["SOURCE"]);
		$data["REWRITED"]	= array_unique($data["REWRITED"]);
		foreach ((array)$data["SOURCE"] as $k => $v) {
			$body .= "<li>".$v." =&gt; ".$this->_admin_link("link", $data["REWRITED"][$k])."</li>";
		}
		$body .= "</ol><i>".t("Rewrite processing time").": ".common()->_format_time_value($GLOBALS['rewrite_exec_time'])." <span>sec</span></div>";
		return $body;
	}

	/*
	*/
	function _debug_force_get_url () {
		if (!debug("_force_get_url")) {
			return "";
		}
		$data = debug("_force_get_url");
		if (empty($data)) {
			return "";
		}
		$_time = 0;
		$body = "";

		$body .= "<div class='debug_allow_close'><h5>".t("force get url")."</h5>";
		$body .= $this->_build_wide_table($data, 1);
		$total_time = 0;
		foreach ((array)$data as $v) {
			$total_time += $v["time"];
		}
		$body .= "<i>".t("total_time").": ".common()->_format_time_value($total_time)." <span>sec</span>";
		$body .= "</div>";
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
	function _debug_loaded_modules () {
		if (!$this->_SHOW_LOADED_MODULES) {
			return "";
		}
		$body = "";
		$body .= "<div class='debug_allow_close'><h5>".t("loaded_modules")."</h5>";

		$counter	= 1;
		$total_size = 0;
		$total_load_time = 0;
		$body .= "<table class='table table-bordered table-striped table-hover'>";
		$body .= 
			"<thead>
				<th></th>
				<th>".t("class_name")."</th>
				<th>".t("loaded_class_name")."</th>
				<th>".t("loaded_path")."</th>
				<th>".t("storage")."</th>
				<th>".t("size")."</th>
				<th>".t("time")."</th>
			</thead>";
		foreach ((array)debug("_MAIN_LOAD_CLASS_DEBUG") as $data) {
			$cur_size = file_exists($data["loaded_path"]) ? filesize($data["loaded_path"]) : "";
			$total_size += $cur_size;
			$total_load_time += (float)$data["time"];
			$body .= "<tr>
					<td >".$counter++.". </td>
					<td> ".$data["class_name"]."</td>
					<td> ".$data["loaded_class_name"]."</td>
					<td >".$this->_admin_link("edit_file", $data["loaded_path"])."</td>
					<td> ".$data["storage"]."</td>
					<td> ".$cur_size."</td>
					<td> ".common()->_format_time_value($data["time"])."</td>
				</tr>";
		}
		$body .= "</table>".t("total_included_size").": ".$total_size." <span>bytes,</span> ".t("total_time").": ".common()->_format_time_value($total_load_time)." <span>sec</span></div>";
		return $body;
	}

	/**
	*/
	function _debug_included_files () {
		if (!$this->_SHOW_INCLUDED_FILES) {
			return "";
		}
		$body = "";
		$body .= "<div class='debug_allow_close'><h5>".t("included_files")."</h5>";
		$total_size = 0;
		$counter	= 1;
		$total_include_time = 0;
		$included_files = get_included_files();
		$exec_time = debug('include_files_exec_time');
		$body .= "<table class='table table-bordered table-striped table-hover'>";
		$body .= "<thead>
				<th></th>
				<th>".t("name")."</th>
				<th>".t("size")."</th>
				<th>".t("time")."</th>
			</thead>";
		foreach ((array)$included_files as $file_name) {
			if ($this->_INCLUDED_SKIP_CACHE && false !== strpos($file_name, "core_cache")) {
				continue;
			}
			$cur_size = file_exists($file_name) ? filesize($file_name) : "";
			$total_size += $cur_size;
			$_fname = strtolower(str_replace(DIRECTORY_SEPARATOR, "/", $file_name));
			$cur_include_time = isset($exec_time[$_fname]) ? $exec_time[$_fname] : 0;
			$total_include_time += (float)$cur_include_time;
			$body .= "<tr>
				<td >".$counter++.". </td>
				<td >".$this->_admin_link("edit_file", $file_name)."</td>
				<td> ".$cur_size."</td>
				<td> ".common()->_format_time_value($cur_include_time)."</td>
			</tr>";
		}
		$body .= "</table>".t("total_included_size").": ".$total_size." <span>bytes,</span> ".t("total_include_time").": ".common()->_format_time_value($total_include_time)." <span>sec</span></div>";
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
	*/
	function _build_wide_table ($data = array(), $trace_num = 0) {
		if (!$data) {
			return "";
		}
		$body = "";
		$body .= "<table class='table table-bordered table-striped table-hover'>";
		// Header
		$body .= "<td  width='1%'>#</td>";
		foreach ((array)$data[0] as $k => $v) {
			$body .= "<td  ".($k == "trace" ? " width='40%'" : "").">".$k."</td>";
		}
		// Data
		$i = 0;
		foreach ((array)$data as $_num => $_data) {
			$body .= "<tr>";
			$body .= "<td>".++$i."</td>";
			foreach ((array)$_data as $k => $v) {
				if ($k == "time") {
					$v = common()->_format_time_value($v, 5);
				}
				if ($k == "data") {
					$v = $this->_my_wrap($v, 40, "");
				}
				if ($k == "trace") {
					$_cur_trace		= $v[$trace_num];
					$_prev_trace	= $v[$trace_num + 1];
					if (!empty($_cur_trace)) {
						$v = "<small>".$this->_admin_link("edit_file", $_cur_trace["file"]).":".$_cur_trace["line"]."(".$_prev_trace["function"].")</small>";
					}
				}
				if (is_array($v)) {
					if (empty($v)) {
						$v = "";
					} else {
						$v = str_replace("", "", var_export($v, 1));
					}
				}
				$body .= "<td>".(strlen($v) ? $v : "")."</td>";
			}
			$body .= "</tr>";
		}
		$body .= "</table>";
		return $body;
	}

	/**
	*/
	function _debug_get_data () {
		$data = debug("_main_get_data_debug");
		if (!$this->_SHOW_MAIN_GET_DATA || !$data) {
			return "";
		}
		$body = "";

		$body .= "<div class='debug_allow_close'><h5>".t("Main Get Data")."</h5>";
		$body .= $this->_build_wide_table($data);
		$total_time = 0;
		foreach ((array)$data as $v) {
			$total_time += $v["time"];
		}
		$body .= "".t("total_time").": ".common()->_format_time_value($total_time)." <span>sec</span>";
		$body .= "</div>";

		return $body;
	}

	/**
	*/
	function _debug_core_cache () {
		if (!$this->_SHOW_CORE_CACHE) {
			return "";
		}
		$body = "";

		$body .= "<div class='debug_allow_close'><h5>".t("Core cache get")."</h5>";
		$cache_debug = debug("_core_cache_debug::get");
		if ($cache_debug) {
			$body .= $this->_build_wide_table($cache_debug);
			$total_time = 0;
			foreach ((array)$cache_debug as $v) {
				$total_time += $v["time"];
			}
			$body .= "".t("total_time").": ".common()->_format_time_value($total_time)." <span>sec</span>";
		}
		$body .= "</div>";

		$cache_debug = debug("_core_cache_debug::set");
		if ($cache_debug) {
			$body .= "<div class='debug_allow_close'><h5>".t("Core cache set")."</h5>";
			$body .= $this->_build_wide_table($cache_debug);
			$total_time = 0;
			foreach ((array)$cache_debug as $v) {
				$total_time += $v["time"];
			}
			$body .= "".t("total_time").": ".common()->_format_time_value($total_time)." <span>sec</span>";
			$body .= "</div>";
		}

		$cache_debug = debug("_core_cache_debug::refresh");
		if ($cache_debug) {
			$body .= "<div class='debug_allow_close'><h5>".t("Core cache refresh")."</h5>";
			$body .= $this->_build_wide_table($cache_debug);
			$total_time = 0;
			foreach ((array)$cache_debug as $v) {
				$total_time += $v["time"];
			}
			$body .= "".t("total_time").": ".common()->_format_time_value($total_time)." <span>sec</span>";

			$body .= "</div>";
		}
		return $body;
	}

	/**
	*/
	function _debug_main_execute () {
		if (!$this->_SHOW_MAIN_EXECUTE || !$GLOBALS['main_execute_block_time']) {
			return "";
		}

		$body = "";
		$body .= "<div class='debug_allow_close'><h5>".t("Main execute")."</h5>";
		$times = debug('main_execute_block_time');
		if (isset($times)) {
			$body .= $this->_build_wide_table($times);

			$total_time = 0;
			foreach ((array)$times as $v) {
				$total_time += $v["time"];
			}
			$body .= "".t("total_time").": ".common()->_format_time_value($total_time)." <span>sec</span>";
		}
		$body .= "</div>";
		return $body;
	}

	/**
	*/
	function _debug_get () {
		$body = "";
		if (!$this->_SHOW_GET_DATA) {
			return "";
		}
		$body .= "<div class='debug_allow_close'><h5>".t("GET data")."</h5><ul>";
		foreach ((array)$_GET as $id => $text) {
			$body .= "<li>['".htmlspecialchars($id)."'] => ".(is_array($text) ? print_r($text, 1) : "'".htmlspecialchars($text)."'")."</li>";
		}
		$body .= "</ul></div>";
		return $body;
	}

	/**
	*/
	function _debug_post () {
		$body = "";
		if (!$this->_SHOW_POST_DATA) {
			return "";
		}
		$body .= "<div class='debug_allow_close'><h5>".t("POST data")."</h5><ul>";
		foreach ((array)$_POST as $id => $text) {
			$body .= "<li>['".htmlspecialchars($id)."'] => ".(is_array($text) ? print_r($text, 1) : "'".htmlspecialchars($text)."'")."</li>";
		}
		$body .= "</ul></div>";
		return $body;
	}

	/**
	*/
	function _debug_cookie () {
		if (!$this->_SHOW_COOKIE_DATA) {
			return "";
		}
		$body = "";
		$body .= "<div class='debug_allow_close'><h5>".t("COOKIE data")."</h5><ul>";
		foreach ((array)$_COOKIE as $id => $text) {
			$body .= "<li>['".htmlspecialchars($id)."'] => ".(is_array($text) ? print_r($text, 1) : "'".htmlspecialchars($text)."'")."</li>";
		}
		$body .= "</ul></div>";
		return $body;
	}

	/**
	*/
	function _debug_request () {
		if (!$this->_SHOW_REQUEST_DATA) {
			return "";
		}
		$body = "";
		$body .= "<div class='debug_allow_close'><h5>".t("REQUEST data")."</h5><ul>";
		foreach ((array)$_REQUEST as $id => $text) {
			$body .= "<li>['".htmlspecialchars($id)."'] => ".(is_array($text) ? print_r($text, 1) : "'".htmlspecialchars($text)."'")."</li>";
		}
		$body .= "</ul></div>";
		return $body;
	}

	/**
	*/
	function _debug_session () {
		if (!$this->_SHOW_SESSION_DATA) {
			return "";
		}
		$body = "";
		$body .= "<div class='debug_allow_close'><h5>".t("SESSION data")."</h5><ul>";
		if (is_array($_SESSION)) {
			ksort($_SESSION);
		}
		foreach ((array)$_SESSION as $id => $text) {
			$body .= "<li>['".htmlspecialchars($id)."'] => ".(is_array($text) ? print_r($text, 1) : "'".htmlspecialchars($text)."'")."</li>";
		}
		$body .= "</ul></div>";
		// Additional session stats
		foreach ((array)ini_get_all('session') as $_k => $_v) {
			$_session_params[$_k] = $_v["local_value"];
		}
		$_num_stats = count($_session_params);
		$_num_cols	= 3;
		$_items_in_column	= ceil($_num_stats / $_num_cols);
		$body .= $this->_show_table(t("SESSION PARAMS"), $_session_params, $_items_in_column);
		return $body;
	}

	/**
	*/
	function _debug_files () {
		if (!$this->_SHOW_FILES_DATA) {
			return "";
		}
		$body = "";
		$body .= "<div class='debug_allow_close'><h5>".t("FILES data")."</h5><ul>";
		foreach ((array)$_FILES as $id => $text) {
			$body .= "<li>['".htmlspecialchars($id)."'] => ".(is_array($text) ? print_r($text, 1) : "'".htmlspecialchars($text)."'")."</li>";
		}
		$body .= "</ul></div>";
		return $body;
	}

	/**
	*/
	function _debug_server () {
		if (!$this->_SHOW_SERVER_DATA) {
			return "";
		}
		$body = "";
		$body .= "<div class='debug_allow_close'><h5>".t("SERVER data")."</h5><ul>";
		if (is_array($_SERVER)) {
			ksort($_SERVER);
		}
		foreach ((array)$_SERVER as $id => $text) {
			$body .= "<li>['".htmlspecialchars($id)."'] => ".(is_array($text) ? print_r($text, 1) : "'".htmlspecialchars($text)."'")."</li>";
		}
		$body .= "</ul></div>";
		return $body;
	}

	/**
	*/
	function _debug_env () {
		if (!$this->_SHOW_ENV_DATA) {
			return "";
		}
		$body = "";
		$body .= "<div class='debug_allow_close'><h5>".t("ENV data")."</h5><ul>";
		if (is_array($_ENV)) {
			ksort($_ENV);
		}
		foreach ((array)$_ENV as $id => $text) {
			$body .= "<li>['".htmlspecialchars($id)."'] => ".(is_array($text) ? print_r($text, 1) : "'".htmlspecialchars($text)."'")."</li>";
		}
		$body .= "</ul></div>";
		return $body;
	}

	/**
	*/
	function _debug_settings () {
		if (!$this->_SHOW_SETTINGS) {
			return "";
		}
		$data = array(
			"DEBUG_MODE"	=> DEBUG_MODE,
			"DEV_MODE"		=> (int)conf('DEV_MODE'),
			"MAIN_TYPE"		=> MAIN_TYPE,
			"USE_CACHE"		=> (int)conf('USE_CACHE'),
			"HOSTNAME"		=> main()->HOSTNAME,
			"SITE_ID"		=> (int)conf('SITE_ID'),
			"SERVER_ID"		=> (int)conf('SERVER_ID'),
			"@LANG"			=> conf("language"),
			"SITE_PATH"		=> SITE_PATH,
			"PROJECT_PATH"	=> PROJECT_PATH,
			"YF_PATH"		=> YF_PATH,
			"WEB_PATH"		=> WEB_PATH,
			"MEDIA_PATH"	=> MEDIA_PATH,
			"IS_SPIDER"		=> (int)conf('IS_SPIDER'),
		);
		$body = "";
		$body .= "<div class='debug_allow_close'><h5>".t("FRAMEWORK SETTINGS")."</h5><ul>";
		foreach ((array)$data as $id => $text) {
			$body .= "<li>['".htmlspecialchars($id)."'] => ".(is_array($text) ? print_r($text, 1) : "'".htmlspecialchars($text)."'")."</li>";
		}
		$body .= "</ul></div>";
		return $body;
	}

	/**
	*/
	function _debug_php_ini () {
		if (!$this->_SHOW_PHP_INI) {
			return "";
		}
		$body .= "<div class='debug_allow_close'><h5>".t("PHP INI")."</h5><ol>";
		foreach (ini_get_all() as $id => $text) {
			$body .= "['".htmlspecialchars($id)."'] => '".htmlspecialchars($text["local_value"])."',";
		}
		$body .= "</ol></div>";
		return $body;
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
	* Format result returned by db query "EXPLAIN ..."
	* 
	* @access	private
	* @return	string
	*/
	function _format_db_explain_result($explain_result = array()) {
		if (empty($explain_result)) {
			return false;
		}
		$body = "<table class='table table-bordered table-striped table-hover'>";
		// Header
		foreach ((array)$explain_result[0] as $k => $v) {
			$body .= "<td>".$k."</td>";
		}
		// Data
		foreach ((array)$explain_result as $_num => $_data) {
			$body .= "<tr>";
			foreach ((array)$_data as $k => $v) {
				$body .= "<td>".(strlen($v) ? $v : "")."</td>";
			}
			$body .= "</tr>";
		}
		$body .= "</table>";
		return $body;
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

	/**
	* Display data formatted as table
	*/
	function _show_table ($title = "", $data = array(), $_items_in_column = 0, $add_text = "") {
		$_tbl_start	= "<table class='table table-bordered table-striped table-hover'>";

		$body = "";
		$body .= "<div class='debug_allow_close'>"
			."<h5>".$title."</h5>"
			."<table>
				<tr valign='top'>
					<td>"
			.$_tbl_start;

		$i = 0;
		foreach ((array)$data as $_var_name => $_var_value) {
			++$i;
			$body .= "<tr>
						<td>".$_var_name."</td>
						<td>".$_var_value."</td>
					</tr>";
			if (!($i % $_items_in_column)) {
				$body .= "</table>
						</td>
						<td>"
					.$_tbl_start;
			}
		}
		$body .= "</table>
				</td>
			</tr>
			</table>".$add_text."
			</div>";
		return $body;
	}

	/**
	* Process through admin link or just return text if links disabled
	*/
	function _admin_link ($type, $text = "", $just_link = false) {
		if (!$this->ADD_ADMIN_LINKS || !isset($this->ADMIN_PATHS[$type])) {
			return $text;
		}
		if ($type == "link") {
			return "<a href='".$text."'>".$text."</a>";
		}
		$id = $text;
		if ($type == "show_db_table") {
			$id = str_replace(db()->DB_PREFIX, "", $id);
		}
		$replace = array(
			"{{ID}}"	=> urlencode(str_replace("\\", "/", $id)),
			"{{THEME}}"	=> conf('theme'),
		);
		$url = str_replace(array_keys($replace), array_values($replace), $this->ADMIN_PATHS[$type]);
		$link = WEB_PATH."admin/?".$url;
		if ($just_link) {
			return $link;
		}
		return "<a href='".$link."'>".$text."</a>";
	}

	/***/
/*
Call Stack:
	0.0023	 328536   1. {main}() /home/www/toggle3_dev/public_html/rewrite.php:0
	0.0071	 430564   2. require_once('/home/www/toggle3_dev/public_html/index.php') /home/www/toggle3_dev/public_html/rewrite.php:190
	0.0743	1071220   3. yf_main->__construct() /home/www/toggle3_dev/public_html/index.php:107
	0.1409	1681372   4. yf_tpl->init_graphics() /home/www/yf/classes/yf_main.class.php:207
	0.8170   23572448   5. yf_common->show_debug_info() /home/www/yf/classes/yf_tpl.class.php:433
	0.8226   24132436   6. yf_debug_info->go() /home/www/yf/classes/yf_common.class.php:309
	0.8227   24140900   7. yf_debug_info->_debug_db_queries() /home/www/yf/classes/common/yf_debug_info.class.php:186
	0.8228   24141464   8. yf_debug_info->_do_debug_db_connection_queries() /home/www/yf/classes/common/yf_debug_info.class.php:247
	0.8317   24193916   9. yf_debug_info->_pretty_trace() /home/www/yf/classes/common/yf_debug_info.class.php:327
*/
	function _pretty_trace($trace = array()) {
		if (!is_array($trace)) {
			$trace = array();
			foreach (debug_backtrace() as $k => $v) {
				if (!$k) {
					continue;
				}
				$v["object"] = isset($v["object"]) && is_object($v["object"]) ? get_class($v["object"]) : null;
				$trace[$k - 1] = $v;
			}
		}
		$body = "";
		$v = $trace;
#		if ()
#var_dump($trace);
#		foreach ((array)$trace as $k => $v) {
			$v["object"] = isset($v["object"]) && is_object($v["object"]) ? get_class($v["object"]) : null;
			$body .= print_r($v, 1)."";
#		}
		return $body;
	}
}
