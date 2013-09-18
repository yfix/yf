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
	* Should be used to wrap around one logical debug block
	*/
	function _test() {
// TODO: remove me after refactoring
		$out .= '<ul class="nav nav-tabs">';
		$out .= '  <li><a class="brand" href="javascript:void(0)">DEBUG</a></li>';
		foreach (range(1,20) as $n) {
			$out .= '  <li'.($n == 1 ? ' class="active"' : '').'><a href="#debug_item'.$n.'" data-toggle="tab" class="">Debug '.$n.'</a></li>';
		}
		$out .= '</ul>';
		$out .= '<div class="tab-content">';
		foreach (range(1,20) as $n) {
			$out .= '  <div class="tab-pane fade in'.($n == 1 ? ' active' : '').'" id="debug_item'.$n.'">'.str_repeat('Debug content '.$n, 100).'</div>';
		}
		$out .= '</div>';
		return $out;
	}

	/**
	* Create simple table with debug info
	*/
	function go () {
		$methods = array();
		$class_name = get_class($this);
		foreach ((array)get_class_methods($class_name) as $method) {
			if (substr($method, 0, strlen("_debug_")) != "_debug_" || $method == $class_name || $method == __FUNCTION__) {
				continue;
			}
			$name = substr($method, strlen("_debug_"));
			$content = $this->$method();
			$debug_contents[$name] = $content;
		}
		$body .= '<div id="debug_console">';

		$body .= '<ul class="nav nav-tabs">';
		$body .= '  <li><a class="brand" href="javascript:void(0)">DEBUG</a></li>';
		$i = 0;
		foreach ((array)$debug_contents as $name => $content) {
			$body .= '  <li'.(++$i == 1 ? ' class="active"' : '').'><a href="#debug_item_'.$name.'" data-toggle="tab" class="">'.$name.'</a></li>';
		}
		$body .= '</ul>';

		$body .= '<div class="tab-content">';
		$i = 0;
		foreach ((array)$debug_contents as $name => $content) {
			$body .= '  <div class="tab-pane fade in'.(++$i == 1 ? ' active' : '').'" id="debug_item_'.$name.'">'.$content.'</div>';
		}
		$body .= '</div>';

		// DO NOT REMOVE!!! Needed to correct display template tags in debug output
		$body = str_replace(array("{", "}"), array("&#123;", "&#125;"), $body);

#		$body .= $this->_test();

		$body .= '</div>';
		return $body;
	}

	/**
	*/
	function _format_db_explain_result($explain_result = array()) {
		if (empty($explain_result)) {
			return false;
		}
		$body = '<table class="table table-bordered table-striped table-hover table-condensed">';
		// Header
		foreach ((array)$explain_result[0] as $k => $v) {
			$body .= '<td>'.$k.'</td>';
		}
		// Data
		foreach ((array)$explain_result as $_num => $_data) {
			$body .= '<tr>';
			foreach ((array)$_data as $k => $v) {
				$body .= '<td>'.(strlen($v) ? $v : '').'</td>';
			}
			$body .= '</tr>';
		}
		$body .= '</table>';
		return $body;
	}

	/**
	* Process through admin link or just return text if links disabled
	*/
	function _admin_link ($type, $text = '', $just_link = false) {
		if (!$this->ADD_ADMIN_LINKS || !isset($this->ADMIN_PATHS[$type])) {
			return $text;
		}
		if ($type == 'link') {
			return '<a href="'.$text.'">'.$text.'</a>';
		}
		$id = $text;
		if ($type == 'show_db_table') {
			$id = str_replace(db()->DB_PREFIX, '', $id);
		}
		$replace = array(
			'{{ID}}'	=> urlencode(str_replace("\\", '/', $id)),
			'{{THEME}}'	=> conf('theme'),
		);
		$url = str_replace(array_keys($replace), array_values($replace), $this->ADMIN_PATHS[$type]);
		$link = WEB_PATH. 'admin/?'. $url;
		if ($just_link) {
			return $link;
		}
		return '<a href="'.$link.'">'.$text.'</a>';
	}

	/**
	*/
	function _do_debug_db_connection_queries ($DB_CONNECTION, $connect_trace = array(), $connect_trace2 = "") {
		if (!$this->_SHOW_QUERY_LOG || !is_object($DB_CONNECTION) || !is_array($DB_CONNECTION->QUERY_LOG)) {
			return "";
		}
		$db_queries_list = $DB_CONNECTION->QUERY_LOG;
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
/*
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
		$body .= $connect_trace2 ? "<small>"._prepare_html($connect_trace2)."</small>" : "";
*/
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
			$text = preg_replace("#(".$DB_CONNECTION->DB_PREFIX."[a-z0-9_]+)#imse", "''.\$this->_admin_link('show_db_table', '\\1').''", $text);

			$exec_time = common()->_format_time_value($DB_CONNECTION->QUERY_EXEC_TIME[$id]);
			$admin_link = $this->_admin_link("sql_query", rawurlencode($orig_sql), true);
			if ($admin_link && $this->ADD_ADMIN_LINKS) {
				$exec_time = "<a href='".$admin_link."'>".$exec_time."</a>";
			}
			$data[] = array(
				'id'		=> ($id + 1),
				'exec_time'	=> $exec_time,
				'sql'		=> $text,
				'rows'		=> $DB_CONNECTION->QUERY_AFFECTED_ROWS[$orig_sql],
				'trace'		=> '<pre><small>'.$_cur_trace2.'</small></pre>',
				'explain'	=> $_cur_explain,
			);
		}

		if (!$DB_CONNECTION->_tried_to_connect) {
#			$body .= t("db not used")."";
		} else {
#			$body .= "<i>".t("total_exec_time").": ".common()->_format_time_value($total_queries_exec_time)."<span> sec";
#			$body .= "<i>".t("connect_time").": ".common()->_format_time_value($DB_CONNECTION->_connection_time)."<span> sec";
		}

		return table($data, array('table_class' => 'debug_item table-condensed'))
			->text('id')
			->text('exec_time')
			->text('sql', array('hidden_data' => array('%explain', '%trace')))
			->text('rows')
			->btn('explain', 'javascript:void(0)', array('hidden_toggle' => 'explain'))
			->btn('trace', 'javascript:void(0)', array('hidden_toggle' => 'trace'))
		;
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
/*
			if ($this->_SHOW_SHUTDOWN_QUERIES) {
				$body .= $this->_do_debug_db_shutdown_queries($v, $connect_trace, $connect_trace2);
			}
			if ($this->_SHOW_CACHED_QUERIES) {
				$body .= $this->_do_debug_db_cached_queries($v, $connect_trace, $connect_trace2);
			}
			if ($this->_SHOW_DB_SESSION_STATS) {
				$body .= $this->_do_debug_db_session_stats($v, $connect_trace, $connect_trace2);
			}
*/
		}
		return $body;
	}
}
