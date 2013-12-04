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
	public $_file_prefix				= 'logs/not_translated_';
	/** @var string @conf_skip */
	public $_file_ext					= '.php';
	/** @var string @conf_skip */
	public $_auto_header				= "<? die('go away!');\n";
	/** @var string @conf_skip */
	public $_auto_footer				= "\n?>";
	/** @var bool */
	public $_SHOW_DB_QUERY_LOG			= 1;
	/** @var bool */
	public $_SHOW_DB_STATS				= 1;
	/** @var bool */
	public $_SHOW_DB_EXPLAIN_QUERY		= 1;
	/** @var bool */
	public $_SHOW_SPHINX				= 1;
	/** @var bool */
	public $_SHOW_SSH					= 1;
	/** @var bool */
	public $_SHOW_TPLS					= 1;
	/** @var bool */
	public $_SHOW_STPLS					= 1;
	/** @var bool */
	public $_SHOW_REWRITE_INFO			= 1;
	/** @var bool */
	public $_SHOW_CUSTOM_REPLACED		= 1;
	/** @var bool */
	public $_SHOW_OUTPUT_CACHE_INFO		= 1;
	/** @var bool */
	public $_SHOW_RESIZED_IMAGES_LOG	= 1;
	/** @var bool */
	public $_SHOW_INCLUDED_FILES		= 1;
	/** @var bool */
	public $_SHOW_LOADED_MODULES		= 1;
	/** @var bool */
	public $_INCLUDED_SKIP_CACHE		= 0;
	/** @var bool */
	public $_SHOW_MEMCACHED_INFO		= 1;
	/** @var bool */
	public $_SHOW_EACCELERATOR_INFO		= 1;
	/** @var bool */
	public $_SHOW_XCACHE_INFO			= 1;
	/** @var bool */
	public $_SHOW_APC_INFO				= 1;
	/** @var bool */
	public $_SHOW_MAIN_GET_DATA			= 1;
	/** @var bool */
	public $_SHOW_CORE_CACHE			= 1;
	/** @var bool */
	public $_SHOW_MAIN_EXECUTE			= 1;
	/** @var bool */
	public $_SHOW_SEND_MAIL				= 1;
	/** @var bool */
	public $_SHOW_GLOBALS				= 1;
	/** @var bool */
	public $_SHOW_NOT_TRANSLATED		= 1;
	/** @var bool */
	public $_SHOW_I18N_VARS				= 1;
	/** @var string */
	public $_NOT_TRANSLATED_FILE		= '';
	/** @var bool */
	public $_SHOW_GET_DATA				= 1;
	/** @var bool */
	public $_SHOW_POST_DATA				= 1;
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
	/** @var bool Store db queries to file */
	public $LOG_QUERIES_TO_FILE			= 0;
	/** @var bool Store slow db queries to file */
	public $LOG_SLOW_QUERIES_TO_FILE	= 0;
	/** @var bool Log queries file name */
	public $LOG_QUERIES_FILE_NAME		= 'db_queries.log';
	/** @var bool Log slow queries file name */
	public $LOG_SLOW_QUERIES_FILE_NAME	= 'slow_queries.log';
	/** @var float */
	public $SLOW_QUERIES_TIME_LIMIT		= 0.2;
	/** @var bool */
	public $SORT_TEMPLATES_BY_NAME		= 1;
	/** @var bool */
	public $ADD_ADMIN_LINKS				= true;
	/** @var bool */
	public $ADMIN_PATHS				= array(
		'edit_stpl'		=> 'object=template_editor&action=edit_stpl&location={LOCATION}&theme={{THEME}}&name={{ID}}',
		'edit_i18n'		=> 'object=locale_editor&action=edit_var&id={{ID}}',
		'edit_file'		=> 'object=file_manager&action=edit_item&id={{ID}}',
		'show_db_table'	=> 'object=db_parser&table={{ID}}',
		'sql_query'		=> 'object=db_manager&action=import&id={{ID}}',
		'link'			=> '{{ID}}',
	);

	/**
	* Catch missing method call
	*/
	function __call($name, $arguments) {
		trigger_error(__CLASS__.': No method '.$name, E_USER_WARNING);
		return false;
	}

	/**
	* Constructor
	*/
	function _init () {
		$this->_NOT_TRANSLATED_FILE = PROJECT_PATH. $this->_file_prefix. conf('language'). $this->_file_ext;
	}

	/**
	* Create simple table with debug info
	*/
	function go () {
/*
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
		// Do hide console if needed
		if (isset($_SESSION['hide_debug_console']) && $_SESSION['hide_debug_console']) {
			$body = "";
		}
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
*/
		$methods = array();
		$class_name = get_class($this);
		foreach ((array)get_class_methods($class_name) as $method) {
			if (substr($method, 0, strlen('_debug_')) != '_debug_' || $method == $class_name || $method == __FUNCTION__) {
				continue;
			}
			$name = substr($method, strlen('_debug_'));
			$content = $this->$method();
			$debug_contents[$name] = $content;
		}
		$body .= '<div id="debug_console">';

		$i = 0;
		$cookie_active_tab = substr($_COOKIE['debug_tabs_active'], strlen('debug_item_'));
		// This is needed to show default tab if saved tab not existing now for any reason
		if (!isset($debug_contents[$cookie_active_tab])) {
			$cookie_active_tab = '';
		}
		foreach ((array)$debug_contents as $name => $content) {
			if (empty($content)) {
				continue;
			}
			$is_first = (++$i == 1);
			$is_active = $cookie_active_tab ? ($cookie_active_tab == $name) : $is_first;
			$contents[$name] = '  <div class="tab-pane fade in'.($is_active ? ' active' : '').'" id="debug_item_'.$name.'">'.$content.'</div>';
			$links[$name] = '  <li'.($is_active ? ' class="active"' : '').'><a href="#debug_item_'.$name.'" data-toggle="tab" class="">'.$name.'</a></li>';
		}

		$body .= '<ul class="nav nav-tabs">';
		$body .= implode(PHP_EOL, $links);
		$body .= '</ul>';

		$body .= '<div class="tab-content">';
		$body .= implode(PHP_EOL, $contents);
		$body .= '</div>';

		// DO NOT REMOVE!!! Needed to correct display template tags in debug output
		$body = str_replace(array('{', '}'), array('&#123;', '&#125;'), $body);

		$body .= '</div>';
		return $body;
	}

	/**
	*/
	function _debug_DEBUG_YF () {
		if (!$this->_SHOW_SETTINGS) {
			return '';
		}
		$data['yf'] = array(
			'MAIN_TYPE'			=> MAIN_TYPE,
			'LANG'				=> conf('language'),
			'DEBUG_MODE'		=> DEBUG_MODE,
			'DEV_MODE'			=> (int)conf('DEV_MODE'),
			'REWRITE_MODE'		=> (int)tpl()->REWRITE_MODE,
			'USE_CACHE'			=> (int)conf('USE_CACHE'),
			'CACHE_DRIVER'		=> conf('USE_CACHE') ? cache()->DRIVER : '',
			'SITE_PATH'			=> SITE_PATH,
			'PROJECT_PATH'		=> PROJECT_PATH,
			'YF_PATH'			=> YF_PATH,
			'WEB_PATH'			=> WEB_PATH,
			'MEDIA_PATH'		=> MEDIA_PATH,
			'TPL_THEMES_PATH'	=> tpl()->_THEMES_PATH,
			'TPL_PATH'			=> tpl()->TPL_PATH,
			'TPL_SKIN'			=> conf('theme'),
			'TPL_INHERIT_SKIN'	=> (string)tpl()->INHERIT_SKIN,
			'TPL_INHERIT_SKIN2'	=> (string)tpl()->INHERIT_SKIN2,
			'MAIN_HOSTNAME'		=> main()->HOSTNAME,
			'SITE_ID'			=> (int)conf('SITE_ID'),
			'SERVER_ID'			=> (int)conf('SERVER_ID'),
			'SERVER_ROLE'		=> _prepare_html(conf('SERVER_ROLE')),
			'USER_ID'			=> (int)main()->USER_ID,
			'USER_GROUP'		=> (int)main()->USER_GROUP,
			'IS_SPIDER'			=> (int)conf('IS_SPIDER'),
			'NO_GRAPHICS'		=> (int)main()->NO_GRAPHICS,
			'OUTPUT_CACHING'	=> (int)main()->OUTPUT_CACHING,
			'NO_CACHE_HEADERS'	=> (int)main()->NO_CACHE_HEADERS,
		);
		foreach ((array)debug('_DEBUG_META') as $k => $v) {
			$data['yf']['META_'.strtoupper($k)] = $v;
		}
		$ini_all = ini_get_all();
		$ini = array(
			'memory_limit',
			'max_execution_time',
			'default_socket_timeout',
			'max_input_time',
			'memory_limit',
			'post_max_size',
			'upload_max_filesize',
			'file_uploads',
			'allow_url_fopen',
			'error_reporting',
			'display_errors',
		);
		foreach ($ini as $name) {
			$data['ini']['php_ini&nbsp;:&nbsp;'.$name] = $ini_all[$name]['local_value'];
		}
		if (tpl()->COMPRESS_OUTPUT && !main()->NO_GRAPHICS) {
			$data['ini'] += array(
				'compress: size original'	=> debug('compress_output_size_1').' bytes',
				'compress: size compressed'	=> debug('compress_output_size_2').' bytes',
				'compress: ratio'			=> (debug('compress_output_size_2') ? round(debug('compress_output_size_1') / debug('compress_output_size_2') * 100, 0) : 0).'%',
			);
		}
		if (conf('GZIP_ENABLED')) {
			$data['ini'] += array(
				'gzip: size original'		=> debug('page_size_original').' bytes',
				'gzip: size gzipped approx'	=> debug('page_size_gzipped').' bytes',
				'gzip: ratio approx'		=> round(debug('page_size_original') / debug('page_size_gzipped') * 100, 0).'%',
			);
		}
		$data['ini'] += array(
			'memory_usage'			=> function_exists('memory_get_usage') ? memory_get_usage() : 'n/a',
			'memory_peak_usage'		=> function_exists('memory_get_peak_usage') ? memory_get_peak_usage() : 'n/a',
			'sys_loadavg'			=> implode(' | ', sys_getloadavg()),
			'db_server_version'		=> db()->get_server_version(),
			'db_host_info'			=> db()->get_host_info(),
			'php_version'			=> phpversion(),
			'php_sapi_name'			=> php_sapi_name(),
			'php_current_user'		=> get_current_user(),
			'php_uname'				=> php_uname(),
			'php_include_path'		=> get_include_path(),
			'php_loaded_extensions'	=> implode(', ', get_loaded_extensions()),
			'php_ini_scanned_files'	=> nl2br(php_ini_scanned_files()),
		);
		foreach ((array)ini_get_all('session') as $k => $v) {
			$data['session'][$k] = $v['local_value'];
		}
		$a = $_POST + $_SESSION;
		$body .= form($a, array('action' => _force_get_url(array('object' => 'test', 'action' => 'change_debug')), 'class' => 'form-inline', 'style' => 'padding-left:20px;'))
			->row_start()
				->container('Locale edit')
				->active_box('locale_edit', array('selected' => $_SESSION['locale_vars_edit']))
				->save(array('class' => 'btn-mini'))
			->row_end()
		;
		foreach ($data as $name => $_data) {
			$body .= '<div class="span6">'.$this->_show_key_val_table($_data, array('no_total' => 1, 'no_sort' => 1)).'</div>';
		}
		return $body;
	}

	/**
	*/
	function _debug_db_queries () {
		if (!$this->_SHOW_DB_QUERY_LOG) {
			return false;
		}
		$body = '';
		$instances_trace = debug('db_instances_trace');
		foreach ((array)debug('db_instances') as $k => $v) {
			$connect_trace = array();
			if (isset($instances_trace[$k])) {
				$connect_trace = $instances_trace[$k];
			}
			$body .= $this->_do_debug_db_connection_queries($v, $connect_trace);
		}
		return $body;
	}

	/**
	*/
	function _do_debug_db_connection_queries ($db, $connect_trace = array()) {
		if (!$this->_SHOW_DB_QUERY_LOG) {
			return '';
		}
		if (!is_object($db) || !is_array($db->QUERY_LOG) || !$db->_tried_to_connect) {
			return false;
		}
		$items = array();
		$db_queries_list = $db->QUERY_LOG;
		if ($this->_SHOW_DB_EXPLAIN_QUERY && !empty($db_queries_list) && substr($db->DB_TYPE, 0, 5) == 'mysql') {
			foreach ((array)$db_queries_list as $id => $_query_text) {
				$_query_text = trim($_query_text);
				// Cut comment
				if (substr($_query_text, 0, 2) == '--') {
					$_query_text = substr($_query_text, strpos($_query_text, ''));
				}
				$_query_text = preg_replace('/[\s]{2,}/ims', ' ', str_replace("\t", ' ', trim($_query_text)));
				if (preg_match('/^[\(]*select/ims', $_query_text)) {
					$db_explain_results[$id] = $db->query_fetch_all('EXPLAIN '.$_query_text, -1);
				}
			}
		}
		$total_queries_exec_time = 0;

		$body .= '<b>'.t('QUERY_LOG').'  ('
			.($db->DB_SSL ? 'SSL ' : '')
			.$db->DB_TYPE
			.'://'.$db->DB_USER
			.'@'.$db->DB_HOST
			.($db->DB_PORT ? ':'.$db->DB_PORT : '')
			.'/'.$db->DB_NAME
			.($db->DB_CHARSET ? '?charset='.$db->DB_CHARSET : '')
			.($db->DB_SOCKET ? '?socket='.$db->DB_SOCKET : '')
			.')</b>';

		$trace_html = ' <a href="javascript:void(0)" class="btn btn-mini btn-toggle" data-hidden-toggle="debug-db-connect-trace">'.t('Trace').'</a>'
				.'<pre style="display:none;" id="debug-db-connect-trace"><small>'._prepare_html($connect_trace).'</small></pre>';

		$body .= $connect_trace ? $trace_html : '';

		$_this = $this;
		foreach ((array)$db_queries_list as $id => $text) {
			$text = trim($text);
			// Cut comment
			if (substr($text, 0, 2) == '--') {
				$text = substr($text, strpos($text, ''));
			}
			$total_queries_exec_time += $db->QUERY_EXEC_TIME[$id];
			$_cur_trace = $db->QUERY_BACKTRACE_LOG[$id];
			$_cur_explain = isset($db_explain_results[$id]) ? $this->_format_db_explain_result($db_explain_results[$id]) : '';
			$_sql_type = strtoupper(rtrim(substr(ltrim($text), 0, 7)));

			$orig_sql = $text;
			$text = htmlspecialchars($text);
			$replace = array(
				','	=> ', ', 
			);
			$text = str_replace(array_keys($replace), array_values($replace), $text);
			$text = preg_replace_callback('/([\s\t]+)('.preg_quote($db->DB_PREFIX, '/').'[a-z0-9_]+)/ims', function($m) use ($_this) {
				return $m[1]. $_this->_admin_link('show_db_table', $m[2]);
			}, $text);

			$exec_time = common()->_format_time_value($db->QUERY_EXEC_TIME[$id]);
			$admin_link = $this->_admin_link('sql_query', rawurlencode($orig_sql), true);
			if ($admin_link && $this->ADD_ADMIN_LINKS) {
				$exec_time = '<a href="'.$admin_link.'" class="btn btn-mini">'.$exec_time.'</a>';
			}
			$items[] = array(
				'id'		=> ($id + 1),
				'sql'		=> $text,
				'rows'		=> strval($db->QUERY_AFFECTED_ROWS[$orig_sql]),
				'exec_time'	=> strval($exec_time),
				'trace'		=> $_cur_trace,
				'explain'	=> $_cur_explain,
			);
		}
		$body .= ' | '.t('total_exec_time').': '.common()->_format_time_value($total_queries_exec_time).'<span> sec';
		$body .= ' | '.t('connect_time').': '.common()->_format_time_value($db->_connection_time).'<span> sec';
		$body .= $this->_show_auto_table($items, array('first_col_width' => '1%','hidden_map' => array('explain' => 'sql', 'trace' => 'sql')));
		return $body;
	}

	/**
	*/
	function _debug_db_stats () {
		if (!$this->_SHOW_DB_STATS) {
			return '';
		}
// TODO: add support for multiple instances and multiple drivers
		$data['stats'] = db()->get_2d('SHOW SESSION STATUS');
		$data['vars'] = db()->get_2d('SHOW VARIABLES');
#		$body .= 'PHP Extension used: '.$ext.'<br>'.PHP_EOL;
		foreach ($data as $name => $_data) {
			$body .= '<div class="span6">'.$name.'<br>'.$this->_show_key_val_table($_data, array('no_total' => 1, 'skip_empty_values' => 1)).'</div>';
		}
		return $body;
	}

	/**
	*/
	function _debug_memcached () {
		if (!$this->_SHOW_MEMCACHED_INFO) {
			return '';
		}
		if (strpos(strtolower(cache()->DRIVER), 'memcache') === false) {
			return '';
		}
		$mc_obj = cache_memcached_connect();
		if (!is_object($mc_obj)) {
			return '';
		}
		$data = array();
		$ext = '';
		if (method_exists($mc_obj, 'getExtendedStats')) {
			$ext = 'memcache (old)';
			$data = $mc_obj->getExtendedStats();
		} elseif (method_exists($mc_obj, 'getStats')) {
			$ext = 'memcached (new)';
			$data = $mc_obj->getStats();
		}
		if (!$data) {
			return 'n/a';
		}
		$body .= 'PHP Extension used: '.$ext.'<br>'.PHP_EOL;
		foreach ($data as $name => $_data) {
			$body .= '<div class="span6">'.$name.'<br>'.$this->_show_key_val_table($_data, array('no_total' => 1, 'skip_empty_values' => 1)).'</div>';
		}
		return $body;
	}

	/**
	*/
	function _debug_stpls () {
		if (!$this->_SHOW_STPLS) {
			return '';
		}
		$data = _class('tpl')->driver->CACHE;
		if ($this->SORT_TEMPLATES_BY_NAME && !empty($data)) {
			ksort($data);
		}
		$stpl_vars = debug('STPL_REPLACE_VARS');
		$items = array();
		foreach ((array)$data as $k => $v) {
			if (empty($v['calls'])) {
				continue;
			}
			$stpl_inline_edit = '';
			if (tpl()->ALLOW_INLINE_DEBUG) {
				$stpl_inline_edit = " stpl_name='".$k."' ";
			}
			$cur_size = strlen($v['string']);
			$total_size += $cur_size;
			$total_stpls_exec_time += (float)$v['exec_time'];

			$items[$counter] = array(
				'id'		=> ++$counter,
				'name'		=> /*$stpl_inline_edit. */$this->_admin_link('edit_stpl', $k, false, array('{LOCATION}' => $v['storage'])),
				'storage'	=> strval($v['storage']),
				'calls'		=> strval($v['calls']),
				'size'		=> strval($cur_size),
				'exec_time'	=> strval(common()->_format_time_value($v['exec_time'])),
				'trace'		=> _prepare_html(debug('STPL_TRACES::'.$k)),
			);
			if (isset($stpl_vars[$counter])) {
				$items[$counter]['vars'] = '<pre><small>'._prepare_html(var_export($stpl_vars[$counter], 1)).'</small></pre>';
			}
		}
		$body .= t('tpl_driver').': '.tpl()->DRIVER_NAME.' | '.t('compile_mode').': '.(int)tpl()->COMPILE_TEMPLATES.' | ';
		$body .= t('used_templates_size').': '.$total_size.' bytes';
		$body .= ' | '.t('total_exec_time').': '.common()->_format_time_value($total_stpls_exec_time).' seconds';
		$body .= $this->_show_auto_table($items, array('first_col_width' => '1%', 'hidden_map' => array('trace' => 'name', 'vars' => 'name')));
		return $body;
	}

	/**
	*/
	function _debug_rewrite () {
		if (!$this->_SHOW_REWRITE_INFO) {
			return '';
		}
		$data = debug('rewrite');
		if (empty($data)) {
			return '';
		}
		$items = array();
		foreach ((array)$data as $k => $v) {
			$items[] = array(
				'id'		=> $k + 1,
				'source'	=> strval($v['source']),
				'rewrited'	=> strval($this->_admin_link('link', $v['rewrited'])),
				'exec_time'	=> strval(common()->_format_time_value($v['exec_time'])),
				'trace'		=> $v['trace'],
			);
		}
		$body .= t('Rewrite processing time').': '.common()->_format_time_value(debug('rewrite_exec_time')).' <span>sec</span>';
		$body .= $this->_show_auto_table($items, array('first_col_width' => '1%', 'hidden_map' => array('trace' => 'source')));
		return $body;
	}

	/*
	*/
	function _debug_force_get_url () {
		if (!$this->_SHOW_REWRITE_INFO) {
			return '';
		}
		$items = debug('_force_get_url');
		foreach ((array)$items as $k => $v) {
			$items[$k]['time'] = strval(common()->_format_time_value($v['time']));
			$items[$k]['rewrited_link'] = strval($this->_admin_link('link', $v['rewrited_link']));
		}
		return $this->_show_auto_table($items, array('hidden_map' => array('trace' => 'params')));
	}

	/**
	*/
	function _debug_modules () {
		if (!$this->_SHOW_LOADED_MODULES) {
			return '';
		}
		$items = array();
		foreach ((array)debug('_MAIN_LOAD_CLASS_DEBUG') as $data) {
			$items[] = array(
				'id'			=> ++$counter,
				'module'		=> $data['class_name'],
				'loaded_class'	=> $data['loaded_class_name'],
				'path'			=> $this->_admin_link('edit_file', $data['loaded_path']),
				'size'			=> file_exists($data['loaded_path']) ? filesize($data['loaded_path']) : '',
				'storage'		=> $data['storage'],
				'time'			=> common()->_format_time_value($data['time']),
				'trace'			=> $data['trace'],
			);
		}
		return $this->_show_auto_table($items, array('hidden_map' => array('trace' => 'path')));
	}

	/**
	*/
	function _debug_execute () {
		if (!$this->_SHOW_MAIN_EXECUTE) {
			return '';
		}
		$items = debug('main_execute_block_time');
		return $this->_show_auto_table($items, array('first_col_width' => '1%', 'hidden_map' => array('trace' => 'params')));
	}

	/**
	*/
	function _debug_main_get_data () {
		if (!$this->_SHOW_MAIN_GET_DATA) {
			return '';
		}
		$items = debug('_main_get_data_debug');
		return $this->_show_auto_table($items, array('hidden_map' => array('trace' => 'params', 'data' => 'name')));
	}

	/**
	*/
	function _debug_cache_get () {
		if (!$this->_SHOW_CORE_CACHE) {
			return '';
		}
		$items = debug('_core_cache_debug::get');
		return $this->_show_auto_table($items, array('hidden_map' => array('trace' => 'params', 'data' => 'name')));
	}

	/**
	*/
	function _debug_cache_set () {
		if (!$this->_SHOW_CORE_CACHE) {
			return '';
		}
		$items = debug('_core_cache_debug::set');
		return $this->_show_auto_table($items, array('hidden_map' => array('trace' => 'name', 'data' => 'name')));
	}

	/**
	*/
	function _debug_cache_refresh () {
		if (!$this->_SHOW_CORE_CACHE) {
			return '';
		}
		$items = debug('_core_cache_debug::refresh');
		return $this->_show_auto_table($items, array('hidden_map' => array('trace' => 'name')));
	}

	/**
	*/
	function _debug__get () {
		if (!$this->_SHOW_GET_DATA) {
			return '';
		}
		return $this->_show_key_val_table($_GET);
	}

	/**
	*/
	function _debug__post () {
		if (!$this->_SHOW_POST_DATA) {
			return '';
		}
		return $this->_show_key_val_table($_POST);
	}

	/**
	*/
	function _debug__cookie () {
		if (!$this->_SHOW_COOKIE_DATA) {
			return '';
		}
		return $this->_show_key_val_table($_COOKIE);
	}

	/**
	*/
	function _debug__request () {
		if (!$this->_SHOW_REQUEST_DATA) {
			return '';
		}
		return $this->_show_key_val_table($_REQUEST);
	}

	/**
	*/
	function _debug__files () {
		if (!$this->_SHOW_FILES_DATA) {
			return '';
		}
		return $this->_show_key_val_table($_FILES);
	}

	/**
	*/
	function _debug__session () {
		if (!$this->_SHOW_SESSION_DATA) {
			return '';
		}
		return $this->_show_key_val_table($_SESSION);
	}

	/**
	*/
	function _debug__server () {
		if (!$this->_SHOW_SERVER_DATA) {
			return '';
		}
		return $this->_show_key_val_table($_SERVER);
	}

	/**
	*/
	function _debug__env () {
		if (!$this->_SHOW_ENV_DATA) {
			return '';
		}
		return $this->_show_key_val_table($_ENV);
	}

	/**
	*/
	function _debug_i18n () {
		if (!$this->_SHOW_I18N_VARS) {
			return '';
		}
		$lang = conf('language');
		$i18n_vars = (array)_class('i18n')->_I18N_VARS;
// TODO: show translations on other languages here too: print_r($i18n_vars)
		if ($i18n_vars[$lang]) {
			ksort($i18n_vars[$lang]);
		}
		$data = array();
		$data['vars'] = array();
		foreach ((array)$i18n_vars[$lang] as $k => $v) {
			$data['vars'][$this->_admin_link('edit_i18n', $k)] = $v;
		}
		$data['calls'] = array();
		$tr_time	= _class('i18n')->_tr_time;
		$tr_calls	= _class('i18n')->_tr_calls;
		foreach ((array)$tr_time[$lang] as $k => $v) {
			$data['calls'][$this->_admin_link('edit_i18n', $k)] = $tr_calls[$lang][$k].'|'.common()->_format_time_value($v);
		}
		$data['not_translated'] = (array)_class('i18n')->_NOT_TRANSLATED[$lang];

		$body .= t('translate time').': '.common()->_format_time_value(_class('i18n')->_tr_total_time).' sec<br>';
		foreach ($data as $name => $_data) {
			$body .= '<div class="span6">'.$name.'<br>'.$this->_show_key_val_table($_data, array('no_total' => 1)).'</div>';
		}
		return $body;
	}
	
	/**
	*/
	function _debug_sphinxsearch () {
		if (!$this->_SHOW_SPHINX) {
			return "";
		}
// TODO
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
			$body .= $this->_show_key_val_table($status);
		}
		return $body;
	}

	/**
	*/
	function _debug_ssh () {
		if (!$this->_SHOW_SSH) {
			return '';
		}
		// Need to enable only when ssh was used
		if (!isset(main()->modules['ssh'])) {
			return '';
		}
		return $this->_show_key_val_table(_class('ssh')->_debug);
	}

	/**
	*/
	function _debug_eaccelerator () {
		if (!$this->_SHOW_EACCELERATOR_INFO || !function_exists('eaccelerator_info')) {
			return '';
		}
// TODO: check me
		$eaccel_stats = eaccelerator_info();
		foreach ((array)ini_get_all('eaccelerator') as $_k => $_v) {
			$eaccel_stats[$_k] = $_v['local_value'];
		}
		return $this->_show_key_val_table($eaccel_stats);
	}

	/**
	*/
	function _debug_apc () {
		if (!$this->_SHOW_APC_INFO || !function_exists('apc_cache_info')) {
			return '';
		}
// TODO
#		$data = apc_cache_info();
#		foreach ((array)ini_get_all('apc') as $_k => $_v) {
#			$data[$_k] = $_v['local_value'];
#		}
#		return $this->_show_key_val_table($data);
	}

	/**
	*/
	function _debug_xcache () {
		if (!$this->_SHOW_XCACHE_INFO || !function_exists('xcache_get')) {
			return '';
		}
// TODO
#		foreach ((array)ini_get_all('xcache') as $_k => $_v) {
#			$data[$_k] = $_v['local_value'];
#		}
#		return $this->_show_key_val_table($data);
	}

	/**
	*/
	function _debug_resize_images () {
		if (!$this->_SHOW_RESIZED_IMAGES_LOG || empty($GLOBALS['_RESIZED_IMAGES_LOG'])) {
			return '';
		}
		return $this->_show_auto_table($GLOBALS['_RESIZED_IMAGES_LOG']);
	}

	/**
	*/
	function _debug_globals () {
		if (!$this->_SHOW_GLOBALS) {
			return '';
		}
		$data['constants'] = get_defined_constants(true);
		$data['constants'] = array_keys($data['constants']['user']); // Compatibility with PHP 5.3
		sort($data['constants']);
		$data['functions'] = get_defined_functions();
		$data['functions'] = $data['functions']['user']; // Compatibility with PHP 5.3
		sort($data['functions']);
		$data['classes'] = get_declared_classes();
		$data['globals'] = array_filter(array_keys($GLOBALS), function($v) { return $v[0] != '_';} );
		sort($data['globals']);
		foreach ($data as $name => $_data) {
			$body .= '<div class="span4">'.$name.'<br>'.$this->_show_key_val_table($_data, array('no_total' => 1)).'</div>';
		}
		return $body;
	}

	/**
	*/
	function _debug_included_files () {
		if (!$this->_SHOW_INCLUDED_FILES) {
			return '';
		}
		$items = array();
		foreach (get_included_files() as $file_name) {
			if ($this->_INCLUDED_SKIP_CACHE && false !== strpos($file_name, 'core_cache')) {
				continue;
			}
			$cur_size = file_exists($file_name) ? filesize($file_name) : '';
			$_fname = strtolower(str_replace(DIRECTORY_SEPARATOR, '/', $file_name));
			$items[] = array(
				'id'	=> ++$counter,
				'name'	=> $this->_admin_link('edit_file', $file_name),
				'size'	=> $cur_size,
			);
			$total_size += $cur_size;
		}
		$body .= 'total size: '.$total_size;
		return $body. $this->_show_auto_table($items);
	}

	/**
	*/
	function _show_key_val_table ($a, $params = array()) {
		if (!$a) {
			return false;
		}
		if (!isset($params['first_col_width'])) {
			$params['first_col_width'] = '1%';
		}
		if (is_array($a) && !$params['no_sort']) {
			ksort($a);
		}
		$items = array();
		foreach ((array)$a as $k => $v) {
			if ($params['skip_empty_values'] && !$v) {
				continue;
			}
			$items[] = array(
				'key'	=> $k,
				'value'	=> is_array($v) ? print_r($v, 1) : $v,
			);
		}
		if (!$items) {
			return false;
		}
		return $this->_show_auto_table($items, $params);
	}

	/**
	*/
	function _show_auto_table ($items = array(), $params = array()) {
		if (!is_array($items)) {
			$items = array();
		}
		$items = $this->_format_trace_in_items($items);
#		$items = _prepare_html($items);
		$total_time = 0.0;
		foreach ($items as &$item) {
			foreach ($item as $k => &$v) {
				if (is_array($v)) {
					$v = !empty($v) ? print_r($v, 1) : '';
				}
				if ($k == 'time') {
					$total_time += $v;
				}
			}
		}
		if (!$items) {
			return false;
		}
		$table = table((array)$items, array(
			'table_class' 		=> 'debug_item table-condensed', 
			'auto_no_buttons' 	=> 1,
			'pager_records_on_page' => 10000,
			'hidden_map'		=> $params['hidden_map'],
			'first_col_width'	=> $params['first_col_width'],
		))->auto();

		foreach ((array)$params['hidden_map'] as $name => $to) {
			$table->btn($name, 'javascript:void();', array('hidden_toggle' => $name));
		}
		if (!$params['no_total']) {
			$body .= ' | items: '.count($items). ($total_time ? ' | total time: '.common()->_format_time_value($total_time) : '');
		}
		return $body. $table;
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
	function _admin_link ($type, $text = '', $just_link = false, $replace = array()) {
		if (!$this->ADD_ADMIN_LINKS || !isset($this->ADMIN_PATHS[$type])) {
			return $text;
		}
		if ($type == 'link') {
			return '<a href="'.$text.'" class="btn btn-mini">'.$text.'</a>';
		}
		$id = $text;
		if ($type == 'show_db_table') {
			$id = str_replace(db()->DB_PREFIX, '', $id);
		}
		$replace += array(
			'{{ID}}'	=> urlencode(str_replace("\\", '/', $id)),
			'{{THEME}}'	=> conf('theme'),
		);
		$url = str_replace(array_keys($replace), array_values($replace), $this->ADMIN_PATHS[$type]);
		$link = WEB_PATH. 'admin/?'. $url;
		if ($just_link) {
			return $link;
		}
		return '<a href="'.$link.'" class="btn btn-mini">'.$text.'</a>';
	}

	/**
	*/
	function _format_trace ($trace) {
		return '<pre><small>'._prepare_html($trace).'</small></pre>';
	}

	/**
	*/
	function _format_trace_in_items ($items) {
		foreach ((array)$items as $k => $v) {
			if (isset($v['trace'])) {
				$items[$k]['trace'] = $this->_format_trace($v['trace']);
			}
		}
		return $items;
	}
}
