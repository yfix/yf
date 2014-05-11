<?php

/**
* Show debug info
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_debug {

	public $_SHOW_DB_QUERY_LOG			= 1;
	public $_SHOW_DB_STATS				= 1;
	public $_SHOW_DB_EXPLAIN_QUERY		= 1;
	public $_SHOW_SPHINX				= 1;
	public $_SHOW_SSH					= 1;
	public $_SHOW_STPLS					= 1;
	public $_SHOW_REWRITE_INFO			= 1;
	public $_SHOW_OUTPUT_CACHE_INFO		= 1;
	public $_SHOW_RESIZED_IMAGES_LOG	= 1;
	public $_SHOW_INCLUDED_FILES		= 1;
	public $_SHOW_LOADED_MODULES		= 1;
	public $_SHOW_MEMCACHED_INFO		= 1;
	public $_SHOW_EACCELERATOR_INFO		= 1;
	public $_SHOW_XCACHE_INFO			= 1;
	public $_SHOW_APC_INFO				= 1;
	public $_SHOW_MAIN_GET_DATA			= 1;
	public $_SHOW_CORE_CACHE			= 1;
	public $_SHOW_MAIN_EXECUTE			= 1;
	public $_SHOW_GLOBALS				= 1;
	public $_SHOW_NOT_TRANSLATED		= 1;
	public $_SHOW_I18N_VARS				= 1;
	public $_SHOW_GET_DATA				= 1;
	public $_SHOW_POST_DATA				= 1;
	public $_SHOW_COOKIE_DATA			= 1;
	public $_SHOW_REQUEST_DATA			= 0;
	public $_SHOW_SESSION_DATA			= 1;
	public $_SHOW_FILES_DATA			= 1;
	public $_SHOW_SERVER_DATA			= 1;
	public $_SHOW_ENV_DATA				= 0;
	public $_SHOW_SETTINGS				= 1;
	public $_SHOW_CURL_REQUESTS			= 1;
	public $_SHOW_FORM2					= 1;
	public $_SHOW_TABLE2				= 1;
	public $_SHOW_DD_TABLE				= 1;
	public $SORT_TEMPLATES_BY_NAME		= 1;
	public $ADD_ADMIN_LINKS				= true;
	public $ADMIN_PATHS				= array(
		'edit_stpl'		=> 'object=template_editor&action=edit_stpl&location={LOCATION}&theme={{THEME}}&name={{ID}}',
		'edit_i18n'		=> 'object=locale_editor&action=edit_var&id={{ID}}',
		'edit_file'		=> 'object=file_manager&action=edit_item&id={{ID}}',
		'show_db_table'	=> 'object=db_manager&action=table_show&id={{ID}}',
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
		$this->_NOT_TRANSLATED_FILE = PROJECT_PATH. 'logs/not_translated_'. conf('language'). '.php';
	}

	/**
	* Create simple table with debug info
	*/
	function go () {
		$ts = microtime(true);
		// Do hide console if needed
		if ($_SESSION['hide_debug_console'] || $_GET['hide_debug_console']) {
			return '';
		}
		$body .= '<div id="debug_console">';
		$body .= common()->_show_execution_time();

		$debug_timings = array();
		$methods = array();
		$class_name = get_class($this);
		foreach ((array)get_class_methods($class_name) as $method) {
			if (substr($method, 0, strlen('_debug_')) != '_debug_' || $method == $class_name || $method == __FUNCTION__) {
				continue;
			}
			$name = substr($method, strlen('_debug_'));
			$ts2 = microtime(true);
			$content = $this->$method($method_params);
			if ($method_params) {
// TODO: support for callback params (title, desc, style, class, etc..)
				$debug_params[$method] = $method_params;
			}
			$debug_timings[$method] = round(microtime(true) - $ts2, 4).' secs';
			$debug_contents[$name] = $content;
		}

		$i = 0;
		$cookie_active_tab = substr($_COOKIE['debug_tabs_active'], strlen('debug_item_'));
		// This is needed to show default tab if saved tab not existing now for any reason
		if (!isset($debug_contents[$cookie_active_tab])) {
			$cookie_active_tab = '';
		}
		$links = array();
		$contents = array();
		foreach ((array)$debug_contents as $name => $content) {
			if (empty($content)) {
				continue;
			}
			$is_first = (++$i == 1);
			$is_active = $cookie_active_tab ? ($cookie_active_tab == $name) : $is_first;
			$contents[$name] = '  <div class="tab-pane fade in'.($is_active ? ' active' : '').'" id="debug_item_'.$name.'">'.$content.'</div>';
			$links[$name] = '  <li'.($is_active ? ' class="active"' : '').'><a href="#debug_item_'.$name.'" data-toggle="tab" class="">'.$name.'</a></li>';
		}

		$debug_time = round(microtime(true) - $ts, 5);
		$body .= 'debug console rendering: '
				.' <a href="javascript:void(0)" class="btn btn-default btn-mini btn-xs btn-toggle" data-hidden-toggle="debug-timings">'.$debug_time.' secs</a>'
				.'<pre style="display:none;" id="debug-timings"><small>'._prepare_html(var_export($debug_timings, 1)).'</small></pre>';

		$body .= '<ul class="nav nav-tabs">';
		$body .= implode(PHP_EOL, $links);
		$body .= '</ul>';

		$body .= '<div class="tab-content">';
		$body .= implode(PHP_EOL, $contents);
		$body .= '</div>';

// TODO: convert into _class('html')->tabs()

		// DO NOT REMOVE!!! Needed to correct display template tags in debug output
		$body = str_replace(array('{', '}'), array('&#123;', '&#125;'), $body);

		$body .= '</div>';
		return $body;
	}

	/**
	*/
	function _get_request_headers() {
		// function_exists('apache_request_headers') ? apache_request_headers() : '', // From PHP5.4+ it works also with fastcgi, not only apache
		$arh = array();
		$rx_http = '/\AHTTP_/';
		foreach((array)$_SERVER as $key => $val) {
			if ( preg_match($rx_http, $key) ) {
				$arh_key = preg_replace($rx_http, '', $key);
				$rx_matches = array();
				$rx_matches = explode('_', $arh_key);
				if ( count($rx_matches) > 0 and strlen($arh_key) > 2 ) {
					foreach($rx_matches as $ak_key => $ak_val) {
						$rx_matches[$ak_key] = ucfirst($ak_val);
					}
					$arh_key = implode('-', $rx_matches);
				}
				$arh[$arh_key] = $val;
			}
		}
		return $arh;
	}

	/**
	*/
	function _debug_DEBUG_YF (&$params = array()) {
		if (!$this->_SHOW_SETTINGS) {
			return '';
		}
		$data['yf'] = array(
			'MAIN_TYPE'			=> MAIN_TYPE,
			'LANG'				=> conf('language'),
			'DEBUG_MODE'		=> DEBUG_MODE,
			'DEV_MODE'			=> (int)conf('DEV_MODE'),
			'REWRITE_MODE'		=> (int)tpl()->REWRITE_MODE,
			'CACHE_USE'			=> (int)((main()->USE_SYSTEM_CACHE || conf('USE_CACHE')) && !cache()->NO_CACHE),
			'CACHE_NO_CACHE'	=> (int)cache()->NO_CACHE,
			'CACHE_DRIVER'		=> cache()->DRIVER,
			'CACHE_NS'			=> cache()->CACHE_NS,
			'CACHE_TTL'			=> (int)cache()->FILES_TTL,
			'SITE_PATH'			=> SITE_PATH,
			'PROJECT_PATH'		=> PROJECT_PATH,
			'YF_PATH'			=> YF_PATH,
			'WEB_PATH'			=> WEB_PATH,
			'MEDIA_PATH'		=> MEDIA_PATH,
			'ADMIN_WEB_PATH'	=> ADMIN_WEB_PATH,
			'ADMIN_SITE_PATH'	=> ADMIN_SITE_PATH,
			'CSS_FRAMEWORK'		=> conf('css_framework') ?: 'bs2',
			'BOOTSTRAP_THEME'	=> $_COOKIE['yf_theme'] ?: conf('DEF_BOOTSTRAP_THEME'),
			'TPL_DRIVER'		=> tpl()->DRIVER_NAME,
			'TPL_COMPILE'		=> (int)tpl()->COMPILE_TEMPLATES,
			'TPL_THEMES_PATH'	=> tpl()->_THEMES_PATH,
			'TPL_PATH'			=> tpl()->TPL_PATH,
			'TPL_SKIN'			=> conf('theme'),
			'TPL_INHERIT_SKIN'	=> (string)tpl()->INHERIT_SKIN,
			'TPL_INHERIT_SKIN2'	=> (string)tpl()->INHERIT_SKIN2,
			'MAIN_HOSTNAME'		=> main()->HOSTNAME,
			'SITE_ID'			=> (int)conf('SITE_ID'),
			'SERVER_ID'			=> (int)conf('SERVER_ID'),
			'SERVER_ROLE'		=> _prepare_html(conf('SERVER_ROLE')),
			'SERVER_SELF_IPS'	=> implode(', ', (array)main()->_server_self_ips),
			'USER_ID'			=> (int)main()->USER_ID,
			'USER_GROUP'		=> (int)main()->USER_GROUP,
			'USER_ROLE'			=> main()->USER_ROLE,
			'IS_POST'			=> (int)main()->is_post(),
			'IS_AJAX'			=> (int)main()->is_ajax(),
			'IS_SPIDER'			=> (int)conf('IS_SPIDER'),
			'NO_GRAPHICS'		=> (int)main()->NO_GRAPHICS,
			'OUTPUT_CACHING'	=> (int)main()->OUTPUT_CACHING,
			'NO_CACHE_HEADERS'	=> (int)main()->NO_CACHE_HEADERS,
			'HTTP_IN_HEADERS'	=> $this->_get_request_headers(),
			'HTTP_OUT_HEADERS'	=> headers_list(),
		);
		foreach ((array)$this->_get_debug_data('_DEBUG_META') as $k => $v) {
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
			$c_info = $this->_get_debug_data('compress_output');

			$data['ini'] += array(
				'compress: size original'	=> $c_info['size_original'].' bytes',
				'compress: size compressed'	=> $c_info['size_compressed'].' bytes',
				'compress: ratio'			=> ($c_info['size_compressed'] ? round($c_info['size_original'] / $c_info['size_compressed'] * 100, 0) : 0).'%',
			);
		}
		if (conf('GZIP_ENABLED')) {
			$g_info = $this->_get_debug_data('gzip_page');

			$data['ini'] += array(
				'gzip: size original'		=> $g_info['size_original'].' bytes',
				'gzip: size gzipped approx'	=> $g_info['size_gzipped'].' bytes',
				'gzip: ratio approx'		=> round($g_info['size_original'] / $g_info['size_gzipped'] * 100, 0).'%',
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
			'php_ini_scanned_files'	=> php_ini_scanned_files(),
		);
		foreach ((array)ini_get_all('session') as $k => $v) {
			$data['session'][$k] = $v['local_value'];
		}
		$a = $_POST + $_SESSION;
		$body .= form($a, array('action' => _force_get_url(array('object' => 'test', 'action' => 'change_debug')), 'class' => 'form-inline', 'style' => 'padding-left:20px;'))
			->row_start()
				->container('Locale edit')
				->active_box('locale_edit', array('selected' => $_SESSION['locale_vars_edit']))
				->save(array('class' => 'btn btn-default btn-mini btn-xs'))
			->row_end()
		;
		foreach ($data as $name => $_data) {
			$body .= '<div class="span6 col-lg-6">'.$this->_show_key_val_table(_prepare_html($_data), array('no_total' => 1, 'no_sort' => 1, 'no_escape' => 1)).'</div>';
		}
		return $body;
	}

	/**
	*/
	function _debug_db (&$params = array()) {
		if (!$this->_SHOW_DB_QUERY_LOG) {
			return false;
		}
		$items = array();
		$instances_trace = $this->_get_debug_data('db_instances_trace');
		foreach ((array)$this->_get_debug_data('db_instances') as $k => $db) {
			$connect_trace = array();
			if (isset($instances_trace[$k])) {
				$connect_trace = $instances_trace[$k];
			}
			$name = $db->DB_TYPE.' | '.$db->DB_USER.' | '.$db->DB_HOST. ($db->DB_PORT ? ':'.$db->DB_PORT : '').' | '.$db->DB_NAME;
			$items[$name] = $this->_do_debug_db_connection_queries($db, $connect_trace);
		}
		$items['db_shutdown_queries'] = $this->_show_db_shutdown_queries();
		$items['db_stats'] = $this->_show_db_stats();
		return _class('html')->tabs($items, array('hide_empty' => 1));
	}

	/**
	*/
	function _do_debug_db_connection_queries ($db, $connect_trace = array()) {
		if (!$this->_SHOW_DB_QUERY_LOG) {
			return '';
		}
		if (!is_object($db) || !is_array($db->_LOG) || !$db->_tried_to_connect) {
			return false;
		}
		$items = array();
		$db_queries_list = $db->_LOG;
		if ($this->_SHOW_DB_EXPLAIN_QUERY && !empty($db_queries_list) && substr($db->DB_TYPE, 0, 5) == 'mysql') {
			foreach ((array)$db_queries_list as $id => $log) {
				if ($log['error']) {
					continue;
				}
				$sql = trim($log['sql']);
				// Cut comment
				if (substr($sql, 0, 2) == '--') {
					$sql = substr($sql, strpos($sql, "\n"));
				}
				$sql = preg_replace('/[\s]{2,}/ims', ' ', str_replace("\t", ' ', trim($sql)));
				if (preg_match('/^[\(]*select/ims', $sql)) {
					$db_explain_results[$id] = $db->get_all('EXPLAIN '.$sql, -1);
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

		$trace_html = ' <a href="javascript:void(0)" class="btn btn-default btn-mini btn-xs btn-toggle" data-hidden-toggle="debug-db-connect-trace">'.t('Trace').'</a>'
				.'<pre style="display:none;" id="debug-db-connect-trace"><small>'._prepare_html($connect_trace).'</small></pre>';

		$body .= $connect_trace ? $trace_html : '';

		$_this = $this;
		foreach ((array)$db_queries_list as $id => $log) {
			$sql = trim($log['sql']);
			// Cut comment
			if (substr($sql, 0, 2) == '--') {
				$sql = substr($sql, strpos($sql, "\n"));
				$sql = trim($sql);
				if (!strlen($sql)) {
					continue;
				}
			}
			$total_queries_exec_time += $log['time'];
			$_cur_trace = $log['trace'];
			$_cur_explain = isset($db_explain_results[$id]) ? $this->_format_db_explain_result($db_explain_results[$id]) : '';
			$_sql_type = strtoupper(rtrim(substr(ltrim($sql), 0, 7)));

			$admin_link = $this->_admin_link('sql_query', urlencode($sql), true);
			$sql = htmlspecialchars($sql);
			$replace = array(
				','	=> ', ', 
			);
			$sql = str_replace(array_keys($replace), array_values($replace), $sql);
			$sql = preg_replace_callback('/([\s\t]+`?)('.preg_quote($db->DB_PREFIX, '/').'[a-z0-9_]+)(`?)/ims', function($m) use ($_this) {
				return $m[1]. $_this->_admin_link('show_db_table', $m[2]). $m[3];
			}, $sql);

			$exec_time = round($log['time'], 4);
			if ($admin_link && $this->ADD_ADMIN_LINKS) {
				$exec_time = '<a href="'.$admin_link.'" class="btn btn-default btn-mini btn-xs">'.$exec_time.'</a>';
			}
			$num = $id + 1;
			$items[] = array(
				'id'		=> $num,
				'sql'		=> $sql,
				'rows'		=> strval($log['rows']),
				'error'		=> $log['error'] ? '<pre>'._prepare_html(var_export($log['error'], 1)).'</pre>' : '',
				'exec_time'	=> strval($exec_time),
				'time'		=> round($log['time'], 4),
				'trace'		=> $_cur_trace,
				'explain'	=> $_cur_explain,
			);
		}
		$items = $this->_time_count_changes($items);
		foreach ((array)$items as $k => $v) {
			unset($items[$k]['time']);
		}
		$body .= ' | '.t('total_exec_time').': '.round($total_queries_exec_time, 4).'<span> sec';
		$body .= ' | '.t('connect_time').': '.round($db->_connection_time, 4).'<span> sec';
		$body .= $this->_show_auto_table($items, array(
			'first_col_width' => '1%',
			'hidden_map' => array('explain' => 'sql', 'trace' => 'sql', 'error' => 'sql'),
			'tr' => function($row, $id) { return $row['error'] ? ' class="error"' : '';}
		));
		return $body;
	}

	/**
	*/
	function _show_db_shutdown_queries () {
		if (!$this->_SHOW_DB_QUERY_LOG) {
			return '';
		}
		return $this->_show_key_val_table(db()->_SHUTDOWN_QUERIES);
	}

	/**
	*/
	function _show_db_stats () {
		if (!$this->_SHOW_DB_STATS) {
			return '';
		}
// TODO: add support for multiple instances and multiple drivers
// TODO: use subtabs here for different db instances
		$data['stats'] = db()->get_2d('SHOW SESSION STATUS');
		$data['vars'] = db()->get_2d('SHOW VARIABLES');
		foreach ($data as $name => $_data) {
			$body .= '<div class="span10 col-lg-10">'.$name.'<br>'.$this->_show_key_val_table($_data, array('no_total' => 1, 'skip_empty_values' => 1)).'</div>';
		}
		return $body;
	}

	/**
	*/
	function _debug_memcached (&$params = array()) {
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
			$body .= '<div class="span6 col-lg-6">'.$name.'<br>'.$this->_show_key_val_table($_data, array('no_total' => 1, 'skip_empty_values' => 1)).'</div>';
		}
		return $body;
	}

	/**
	*/
	function _debug_stpls (&$params = array()) {
		if (!$this->_SHOW_STPLS) {
			return '';
		}
		$data = _class('tpl')->driver->CACHE;
		if ($this->SORT_TEMPLATES_BY_NAME && !empty($data)) {
			ksort($data);
		}
		$stpl_vars = $this->_get_debug_data('STPL_REPLACE_VARS');
		$stpl_traces = $this->_get_debug_data('STPL_TRACES');

		$items = array();
		foreach ((array)$data as $k => $v) {
			if (empty($v['calls'])) {
				continue;
			}
			$stpl_inline_edit = '';
			if (tpl()->ALLOW_INLINE_DEBUG) {
				$stpl_inline_edit = ' stpl_name=\''.$k.'\' ';
			}
			$cur_size = strlen($v['string']);
			$total_size += $cur_size;
			$total_stpls_exec_time += (float)$v['exec_time'];

			$items[$counter] = array(
				'id'		=> ++$counter,
// TODO: add link to inline stpl edit
				'name'		=> /*$stpl_inline_edit. */$this->_admin_link('edit_stpl', $k, false, array('{LOCATION}' => $v['storage'])),
				'storage'	=> strval($v['storage']),
				'calls'		=> strval($v['calls']),
				'size'		=> strval($cur_size),
				'time'		=> round($v['exec_time'], 4),
				'trace'		=> _prepare_html($stpl_traces[$k]),
			);
			if (isset($stpl_vars[$counter])) {
				$items[$counter]['vars'] = '<pre><small>'._prepare_html(var_export($stpl_vars[$counter], 1)).'</small></pre>';
			}
		}
		$items = $this->_time_count_changes($items);

		$body .= t('tpl_driver').': '.tpl()->DRIVER_NAME.' | '.t('compile_mode').': '.(int)tpl()->COMPILE_TEMPLATES.' | ';
		$body .= t('used_templates_size').': '.$total_size.' bytes';
		$body .= ' | '.t('total_exec_time').': '.round($total_stpls_exec_time, 4).' seconds';
		$body .= $this->_show_auto_table($items, array('first_col_width' => '1%', 'hidden_map' => array('trace' => 'name', 'vars' => 'name')));
		return $body;
	}

	/**
	*/
	function _debug_rewrite (&$params = array()) {
		if (!$this->_SHOW_REWRITE_INFO) {
			return '';
		}
		$data = $this->_get_debug_data('rewrite');
		if (empty($data)) {
			return '';
		}
		$items = array();
		foreach ((array)$data as $k => $v) {
			$items[] = array(
				'id'		=> $k + 1,
				'source'	=> strval($v['source']),
				'rewrited'	=> strval($this->_admin_link('link', $v['rewrited'])),
				'time'		=> round($v['exec_time'], 4),
				'trace'		=> $v['trace'],
			);
		}
		$items = $this->_time_count_changes($items);
		$body .= t('Rewrite processing time').': '.round($this->_get_debug_data('rewrite_exec_time'), 4).' <span>sec</span>';
		$body .= $this->_show_auto_table($items, array('first_col_width' => '1%', 'hidden_map' => array('trace' => 'source')));
		return $body;
	}

	/*
	*/
	function _debug_force_get_url (&$params = array()) {
		if (!$this->_SHOW_REWRITE_INFO) {
			return '';
		}
		$items = $this->_get_debug_data('_force_get_url');
		foreach ((array)$items as $k => $v) {
			$items[$k]['time'] = round($v['time'], 4);
			$items[$k]['rewrited_link'] = strval($this->_admin_link('link', $v['rewrited_link']));
		}
		$items = $this->_time_count_changes($items);
		return $this->_show_auto_table($items, array('hidden_map' => array('trace' => 'params')));
	}

	/**
	*/
	function _debug_modules (&$params = array()) {
		if (!$this->_SHOW_LOADED_MODULES) {
			return '';
		}
		$items = array();
		foreach ((array)$this->_get_debug_data('main_load_class') as $data) {
			$items[] = array(
				'id'			=> ++$counter,
				'module'		=> $data['class_name'],
				'loaded_class'	=> $data['loaded_class_name'],
				'path'			=> $this->_admin_link('edit_file', $data['loaded_path']),
				'size'			=> file_exists($data['loaded_path']) ? filesize($data['loaded_path']) : '',
				'storage'		=> $data['storage'],
				'time'			=> round($data['time'], 4),
				'trace'			=> $data['trace'],
			);
		}
		$items = $this->_time_count_changes($items);
		return $this->_show_auto_table($items, array('hidden_map' => array('trace' => 'path')));
	}

	/**
	*/
	function _debug_execute (&$params = array()) {
		if (!$this->_SHOW_MAIN_EXECUTE) {
			return '';
		}
		$items = $this->_get_debug_data('main_execute_block_time');
		$items = $this->_time_count_changes($items);
		return $this->_show_auto_table($items, array('first_col_width' => '1%', 'hidden_map' => array('trace' => 'params')));
	}

	/**
	*/
	function _debug_main_get_data (&$params = array()) {
		if (!$this->_SHOW_MAIN_GET_DATA) {
			return '';
		}
		$items = (array)$this->_get_debug_data('main_get_data');
		foreach ($items as &$v) {
			$data = var_export($v['data'], 1);
			$size = strlen($data);
			$v['data'] = '<pre><small>'._prepare_html(substr($data, 0, 1000)).'</small></pre>';
			$v['data_size'] = $size;
		}
		$items = $this->_time_count_changes($items);
		return $this->_show_auto_table($items, array('hidden_map' => array('trace' => 'params', 'data' => 'name')));
	}

	/**
	*/
	function _debug_cache_get (&$params = array()) {
		if (!$this->_SHOW_CORE_CACHE) {
			return '';
		}
// TODO + add admin link to purge cache
// TODO + add link to inspect current cache contents if driver supports this
		$items = (array)$this->_get_debug_data('cache_get');
		foreach ($items as &$v) {
			$data = var_export($v['data'], 1);
			$size = strlen($data);
			$v['data'] = '<pre><small>'._prepare_html(substr($data, 0, 1000)).'</small></pre>';
			$v['data_size'] = $size;
		}
		$items = $this->_time_count_changes($items);
		return $this->_show_auto_table($items, array('hidden_map' => array('trace' => 'params', 'data' => 'name')));
	}

	/**
	*/
	function _debug_cache_set (&$params = array()) {
		if (!$this->_SHOW_CORE_CACHE) {
			return '';
		}
		$items = (array)$this->_get_debug_data('cache_set');
		foreach ($items as &$v) {
			$data = var_export($v['data'], 1);
			$size = strlen($data);
			$v['data'] = '<pre><small>'._prepare_html(substr($data, 0, 1000)).'</small></pre>';
			$v['data_size'] = $size;
		}
		$items = $this->_time_count_changes($items);
		return $this->_show_auto_table($items, array('hidden_map' => array('trace' => 'name', 'data' => 'name')));
	}

	/**
	*/
	function _debug_cache_del (&$params = array()) {
		if (!$this->_SHOW_CORE_CACHE) {
			return '';
		}
		$items = $this->_get_debug_data('cache_del');
		$items = $this->_time_count_changes($items);
		return $this->_show_auto_table($items, array('hidden_map' => array('trace' => 'name')));
	}

	/**
	*/
	function _debug__get (&$params = array()) {
		if (!$this->_SHOW_GET_DATA) {
			return '';
		}
		return $this->_show_key_val_table($_GET);
	}

	/**
	*/
	function _debug__post (&$params = array()) {
		if (!$this->_SHOW_POST_DATA) {
			return '';
		}
		return $this->_show_key_val_table($_POST);
	}

	/**
	*/
	function _debug__cookie (&$params = array()) {
		if (!$this->_SHOW_COOKIE_DATA) {
			return '';
		}
// TODO: add link to delete cookie (inside browser)
		return $this->_show_key_val_table($_COOKIE);
	}

	/**
	*/
	function _debug__request (&$params = array()) {
		if (!$this->_SHOW_REQUEST_DATA) {
			return '';
		}
		return $this->_show_key_val_table($_REQUEST);
	}

	/**
	*/
	function _debug__files (&$params = array()) {
		if (!$this->_SHOW_FILES_DATA) {
			return '';
		}
		return $this->_show_key_val_table($_FILES);
	}

	/**
	*/
	function _debug__session (&$params = array()) {
		if (!$this->_SHOW_SESSION_DATA) {
			return '';
		}
		$items = $_SESSION;
		foreach ((array)$items as $k => $v) {
			$items[$k] = array(
				'key' => $k,
				'value' => '<pre>'._prepare_html(var_export($v, 1)).'</pre>',
			);
		}
		return $this->_show_auto_table($items, array('first_col_width' => '1%'));
	}

	/**
	*/
	function _debug__server (&$params = array()) {
		if (!$this->_SHOW_SERVER_DATA) {
			return '';
		}
		return $this->_show_key_val_table($_SERVER);
	}

	/**
	*/
	function _debug__env (&$params = array()) {
		if (!$this->_SHOW_ENV_DATA) {
			return '';
		}
		return $this->_show_key_val_table($_ENV);
	}

	/**
	*/
	function _debug_i18n (&$params = array()) {
		if (!$this->_SHOW_I18N_VARS) {
			return '';
		}
// TODO: unify into one table, when translated/called/not translated will be as status
		$lang = conf('language');
		$i18n_vars = (array)_class('i18n')->_I18N_VARS;
// TODO: show translations on other languages here too: print_r($i18n_vars)
// TODO: previous todo seems means multi-language translation debug support
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
			$data['calls'][$this->_admin_link('edit_i18n', $k)] = $tr_calls[$lang][$k].'|'.round($v, 4);
		}
		$data['not_translated'] = (array)_class('i18n')->_NOT_TRANSLATED[$lang];

		$body .= t('translate time').': '.round(_class('i18n')->_tr_total_time, 4).' sec<br>';
		foreach ($data as $name => $_data) {
			$body .= '<div class="span6 col-lg-6">'.$name.'<br>'.$this->_show_key_val_table(_prepare_html($_data), array('no_total' => 1, 'no_escape' => 1)).'</div>';
		}
		return $body;
	}
	
	/**
	*/
	function _debug_sphinxsearch (&$params = array()) {
		if (!$this->_SHOW_SPHINX) {
			return "";
		}
		$sphinx_debug = $this->_get_debug_data('sphinxsearch');
		if (!$sphinx_debug) {
			return '';
		}
		$body .= 'host: '. _class('sphinxsearch')->_get_host();
		$body .= ', version: '._class('sphinxsearch')->_get_server_version();

		$sphinx_connect_debug = array();
		$items = &$sphinx_debug;
		foreach ((array)$items as $id => $item) {
			if ($item['query'] == 'sphinx connect') {
				$sphinx_connect_debug = $item;
				unset($items[$id]);
				continue;
			}
			$item['time'] = round($item['time'], 4);
			$item['results'] = '<pre>'._prepare_html(var_export($item['results'], 1)).'</pre>';
			$item['meta'] = '<pre>'._prepare_html(var_export($item['meta'], 1)).'</pre>';
			$item['describe'] = '<pre>'._prepare_html(var_export($item['describe'], 1)).'</pre>';
			$items[$id] = array('id' => $id) + $item;
		}
		$items = $this->_time_count_changes($items);

#		$body .= '<i>'.t('Total time').': '.round($total_time, 4).' secs';
		$body .= $this->_show_auto_table($items, array('first_col_width' => '1%', 'hidden_map' => array('trace' => 'query', 'meta' => 'count', 'describe' => 'count', 'results' => 'count')));
		$body .= $sphinx_connect_debug ? '<pre>'._prepare_html(var_export($sphinx_connect_debug, 1)).'</pre>' : '';
		$body .= $this->_show_key_val_table(_class('sphinxsearch')->_get_server_status());
		return $body;
	}

	/**
	*/
	function _debug_ssh (&$params = array()) {
		if (!$this->_SHOW_SSH) {
			return '';
		}
// TODO: add link to webshell of that server
		// Need to enable only when ssh was used
		if (!isset(main()->modules['ssh'])) {
			return '';
		}
		return $this->_show_key_val_table(_class('ssh')->_debug);
	}

	/**
	*/
	function _debug_eaccelerator (&$params = array()) {
		if (!$this->_SHOW_EACCELERATOR_INFO || !function_exists('eaccelerator_info')) {
			return '';
		}
		$eaccel_stats = eaccelerator_info();
		foreach ((array)ini_get_all('eaccelerator') as $_k => $_v) {
			$eaccel_stats[$_k] = $_v['local_value'];
		}
		return $this->_show_key_val_table($eaccel_stats);
	}

	/**
	*/
	function _debug_apc (&$params = array()) {
		if (!$this->_SHOW_APC_INFO || !function_exists('apc_cache_info')) {
			return '';
		}
		$data = apc_cache_info();
		foreach ((array)ini_get_all('apc') as $_k => $_v) {
			$data[$_k] = $_v['local_value'];
		}
		return $this->_show_key_val_table($data);
	}

	/**
	*/
	function _debug_xcache (&$params = array()) {
		if (!$this->_SHOW_XCACHE_INFO || !function_exists('xcache_get')) {
			return '';
		}
		foreach ((array)ini_get_all('xcache') as $_k => $_v) {
			$data[$_k] = $_v['local_value'];
		}
		return $this->_show_key_val_table($data);
	}

	/**
	*/
	function _debug_resize_images (&$params = array()) {
		if (!$this->_SHOW_RESIZED_IMAGES_LOG || empty($GLOBALS['_RESIZED_IMAGES_LOG'])) {
			return '';
		}
		return $this->_show_auto_table($GLOBALS['_RESIZED_IMAGES_LOG']);
	}

	/**
	*/
	function _debug_globals (&$params = array()) {
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
			$body .= '<div class="span4 col-lg-4">'.$name.'<br>'.$this->_show_key_val_table($_data, array('no_total' => 1)).'</div>';
		}
		return $body;
	}

	/**
	*/
	function _debug_included_files (&$params = array()) {
		if (!$this->_SHOW_INCLUDED_FILES) {
			return '';
		}
		$items = (array)$this->_get_debug_data('included_files');
		foreach ($items as $k => &$v) {
			if (!$v['exists']) {
				unset($items[$k]);
				continue;
			}
			$v['path'] = $this->_admin_link('edit_file', $v['path']);
			$v = array('id' => ++$i) + $v;
			$total_size += $v['size'];
		}
		$items = $this->_time_count_changes($items);
		$body .= 'total size: '.$total_size;
		return $body. $this->_show_auto_table($items, array('hidden_map' => array('trace' => 'path')));
	}

	/**
	*/
	function _debug_curl_requests (&$params = array()) {
		if (!$this->_SHOW_CURL_REQUESTS) {
			return '';
		}
		$items = $this->_get_debug_data('curl_get_remote_page');
		foreach ((array)$items as $k => $v) {
			$items[$k] = array(
				'id' => $k + 1,
				'info' => '<pre>'._prepare_html(var_export($v['info'], 1)).'</pre>',
				'trace'	=> $v['trace'],
			);
		}
		$items = $this->_time_count_changes($items);
		return $this->_show_auto_table($items, array('hidden_map' => array('trace' => 'id')));
	}

	/**
	*/
	function _debug_form2 (&$params = array()) {
		if (!$this->_SHOW_FORM2) {
			return '';
		}
		$items = $this->_get_debug_data('form2');
		foreach ((array)$items as $k => $v) {
			$v['params'] = '<pre>'._prepare_html(var_export($v['params'], 1)).'</pre>';
			$v['fields'] = '<pre>'._prepare_html(var_export($v['fields'], 1)).'</pre>';
			$items[$k] = array('id' => ++$i) + $v;
		}
		$items = $this->_time_count_changes($items);
		return $this->_show_auto_table($items, array('hidden_map' => array('trace' => 'params', 'fields' => 'params')));
	}

	/**
	*/
	function _debug_table2 (&$params = array()) {
		if (!$this->_SHOW_TABLE2) {
			return '';
		}
		$items = $this->_get_debug_data('table2');
		foreach ((array)$items as $k => $v) {
			$v['params'] = '<pre>'._prepare_html(var_export($v['params'], 1)).'</pre>';
			$v['fields'] = '<pre>'._prepare_html(var_export($v['fields'], 1)).'</pre>';
			$v['buttons'] = '<pre>'._prepare_html(var_export($v['buttons'], 1)).'</pre>';
			if ($v['header_links']) {
				$v['header_links'] = '<pre>'._prepare_html(var_export($v['header_links'], 1)).'</pre>';
			}
			if ($v['footer_links']) {
				$v['footer_links'] = '<pre>'._prepare_html(var_export($v['footer_links'], 1)).'</pre>';
			}
			$items[$k] = array('id' => ++$i) + $v;
		}
		$items = $this->_time_count_changes($items);
		return $this->_show_auto_table($items, array('hidden_map' => array('trace' => 'header_links', 'fields' => 'header_links', 'buttons' => 'header_links')));
	}

	/**
	*/
	function _debug_dd_table (&$params = array()) {
		if (!$this->_SHOW_DD_TABLE) {
			return '';
		}
		$items = $this->_get_debug_data('dd_table');
		foreach ((array)$items as $k => $v) {
			$v['fields'] = '<pre>'._prepare_html(var_export($v['fields'], 1)).'</pre>';
			$v['extra'] = '<pre>'._prepare_html(var_export($v['extra'], 1)).'</pre>';
			if ($v['field_types']) {
				$v['field_types'] = '<pre>'._prepare_html(var_export($v['field_types'], 1)).'</pre>';
			}
			$items[$k] = array('id' => ++$i) + $v;
		}
		$items = $this->_time_count_changes($items);
		return $this->_show_auto_table($items, array('hidden_map' => array('trace' => 'fields')));
	}

	/**
	*/
	function _debug_profiling (&$params = array()) {
		$all_timings = main()->_timing;
		if (!$all_timings) {
			return false;
		}
		$ts = main()->_time_start;
		$_last_item = end($all_timings);
		$time_all = $_last_item[0] - $ts;
		$items = array();
		foreach ((array)$all_timings as $i => $v) {
			$time_offset = $v[0] - $ts;
			$time_change = '';
			$time_change_p = '';
			if (isset($all_timings[$i + 1])) {
				$time_change = $all_timings[$i + 1][0] - $v[0];
			}
			$time_warning = false;
			if ($time_change > 0.001) {
				$time_change_p = round(100 - (($time_all - $time_change) / $time_all * 100), 1);
				if ($time_change_p >= 5) {
					$time_warning = true;
				}
			}
			$items[] = array(
				'i'				=> $i,
				'time_offset'	=> round($time_offset, 4),
				'time_change'	=> $time_change && $time_change > 0.0001 ? round($time_change, 4) : '',
				'time_change_p'	=> $time_change_p ? '<span class="'.($time_warning ? 'label label-warning' : '').'">'.$time_change_p.'%</span>' : '',
				'class'			=> $v[1],
				'method'		=> $v[2],
				'trace'			=> $v[3],
				'args'			=> $v[4] ? _prepare_html(var_export($v[4], 1)) : '',
			);
		}
		return $this->_show_auto_table($items, array('hidden_map' => array('trace' => 'args')));
	}

	/**
	*/
	function _debug_hooks (&$params = array()) {
		$items = array();
		$hook_name = '_hook_debug';
		foreach (main()->modules as $module_name => $module_obj) {
			if (!method_exists($module_obj, $hook_name)) {
				continue;
			}
			$items[$module_name] = $module_obj->$hook_name($this);
		}
		return $this->_show_key_val_table($items);
	}

	/**
	*/
	function _debug_css (&$params = array()) {
		$items = $this->_get_debug_data('core_css');
		foreach ((array)$items as $k => $v) {
			$v['preview'] = '<pre>'._prepare_html(substr($v['content'], 0, 100)).'</pre>';
			$v['content'] = '<pre>'._prepare_html(var_export($v['content'], 1)).'</pre>';
			$v['params'] = $v['params'] ? '<pre>'._prepare_html(var_export($v['params'], 1)).'</pre>' : '';
			unset($v['is_added']);
			$items[$k] = array('id' => ++$i) + $v;
		}
		return $this->_show_auto_table($items, array('hidden_map' => array('trace' => 'md5', 'content' => 'preview')));
	}

	/**
	*/
	function _debug_js (&$params = array()) {
		$items = $this->_get_debug_data('core_js');
		foreach ((array)$items as $k => $v) {
			$v['preview'] = '<pre>'._prepare_html(substr($v['content'], 0, 100)).'</pre>';
			$v['content'] = '<pre>'._prepare_html(var_export($v['content'], 1)).'</pre>';
			$v['params'] = $v['params'] ? '<pre>'._prepare_html(var_export($v['params'], 1)).'</pre>' : '';
			unset($v['is_added']);
			$items[$k] = array('id' => ++$i) + $v;
		}
		return $this->_show_auto_table($items, array('hidden_map' => array('trace' => 'md5', 'content' => 'preview')));
	}

	/**
	*/
	function _debug_other (&$params = array()) {
		$items = array();
		foreach (debug() as $k => $v) {
			if (isset($this->_used_debug_datas[$k])) {
				continue;
			}
			$items[$k] = $v;
		}
		return $this->_show_key_val_table($items);
	}

	/**
	*/
	function _show_key_val_table ($a, $params = array(), $name = '') {
		if (!$a) {
			return false;
		}
		if (!isset($params['first_col_width'])) {
			$params['first_col_width'] = '1%';
		}
		if (is_array($a) && !$params['no_sort']) {
			ksort($a);
		}
		// Escape by default
		if (!$params['no_escape']) {
			$params['escape'] = 1;
		}
		$items = array();
		foreach ((array)$a as $k => $v) {
			if ($params['skip_empty_values'] && !$v) {
				continue;
			}
			$v = is_array($v) ? var_export($v, 1) : $v;
			$items[] = array(
				'key'	=> $params['escape'] ? _prepare_html($k) : $k,
				'value'	=> $params['escape'] && strlen($v) ? '<pre>'._prepare_html($v).'</pre>' : $v,
			);
		}
		if (!$items) {
			return false;
		}
		if ($params['escape']) {
			$params['no_escape'] = 1; // Means we already escaped here
		}
		return $this->_show_auto_table($items, $params);
	}

	/**
	*/
	function _show_auto_table ($items = array(), $params = array(), $name = '') {
		if (!is_array($items)) {
			$items = array();
		}
		$items = $this->_format_trace_in_items($items);
#		$items = _prepare_html($items);
		$total_time = 0.0;
		foreach ($items as &$item) {
			foreach ($item as $k => &$v) {
				if (is_array($v)) {
// TODO: add auto-escape here, but need to test before
					$v = !empty($v) ? var_export($v, 1) : '';
					if (!$params['no_escape']) {
						$v = _prepare_html($v);
					}
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
// Temporary disabled, as somehow borwser renders table with width=100% if first col width provided, why???
#			'first_col_width'	=> $params['first_col_width'],
			'tr'				=> $params['tr'],
			'td'				=> $params['td'],
		))->auto();

		foreach ((array)$params['hidden_map'] as $name => $to) {
			$table->btn($name, 'javascript:void();', array('hidden_toggle' => $name, 'display_func' => function($row, $info, $params) use($name) { return (bool)strlen($row[$name]); }));
		}
		if (!$params['no_total']) {
			$body .= ' | items: '.count($items). ($total_time ? ' | total time: '.round($total_time, 4) : '');
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
			return '<a href="'.$text.'" class="btn btn-default btn-mini btn-xs">'.$text.'</a>';
		}
		$id = $text;
		$replace += array(
			'{{ID}}'	=> urlencode(str_replace("\\", '/', $id)),
			'{{THEME}}'	=> conf('theme'),
		);
		$url = str_replace(array_keys($replace), array_values($replace), $this->ADMIN_PATHS[$type]);
		$link = ADMIN_WEB_PATH. '?'. $url;
		if ($just_link) {
			return $link;
		}
		return '<a href="'.$link.'" class="btn btn-default btn-mini btn-xs">'.$text.'</a>';
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

	/**
	*/
	function _get_debug_data ($name) {
		$this->_used_debug_datas[$name]++;
		$data = debug($name);
		debug($name, false);
		return $data;
	}

	/**
	*/
	function _time_count_changes ($items = array(), $field = 'time') {
		$time_all = 0;
		$time_max = 0;
		foreach ((array)$items as $i => $v) {
			$time = $v[$field];
			$time_all += $time;
			if ($time > $time_max) {
				$time_max = $time;
			}
		}
		if (!$time_all) {
			return $items;
		}
		$warn_limit = $time_max / $time_all * 100 / 2;
		if ($warn_limit < 20) {
			$warn_limit = 20;
		}
		foreach ((array)$items as $i => $v) {
			$time = $v[$field];
			$timep = round($time / $time_all * 100, 1);
			$items[$i]['timep'] = $timep ? '<span class="'.($timep > $warn_limit ? 'label label-warning' : '').'">'.$timep.'%</span>' : '';
		}
		return $items;
	}

	/**
	*/
	function _i18n_vars_todo () {
// TODO: JS full rewrite needed, as was done for i18n inline editor
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
		return $body;
	}
}
