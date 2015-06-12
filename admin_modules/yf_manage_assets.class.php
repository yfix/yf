<?php

// TODO: requirejs integration, auto-generate its config with switcher on/off
// TODO: cache fill from console, with ability to put into cron task
// TODO: support for multiple media servers
// TODO: support for .min, using some of console minifier (yahoo, google, jsmin ...)
// TODO: move to web accessible folder only after completion to ensure atomicity
// TODO: upload to S3, FTP

class yf_manage_assets {

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
	}

	/**
	*/
	function _module_action_handler($method) {
		$prepend = $this->_menu();
		if (!$method || substr($method, 0, 1) === '_' || !method_exists($this, $method)) {
			$method = 'show';
		}
		return $prepend. $this->$method(). $append;
	}

	/**
	*/
	function _menu() {
		return html()->module_menu($this, array(
			array('/@object/search_used/', 'Search used', 'fa fa-search'),
			array('/@object/cache_info/', 'Cache info', 'fa fa-info'),
			array('/@object/cache_purge/', 'Cache purge', 'fa fa-recycle'),
			array('/@object/cache_fill/', 'Cache fill', 'fa fa-refresh'),
			array('/@object/combine/', 'Combine', 'fa fa-rocket'),
			array('/@object/upload/', 'Upload', 'fa fa-upload'),
			array('/@object/settings/', 'Settings', 'fa fa-wrench'),
		)). '<br /><br />'.PHP_EOL;
	}

	/**
	*/
	function show() {
		return redirect('/@object/search_used');
	}

	/**
	*/
	function search_used() {
		$exclude_paths = array(
			'*/.git/*',
			'*/.dev/*',
			'*/test/*',
			'*/tests/*',
			'*/cache/*',
			'*/test*.class.php',
			'*/'.YF_PREFIX.'test*.class.php',
		);
		$regex_php = '~[\s](asset|js|css)\([\s"\']+(?P<name>[^\(\)\{\}\$]+)[\s"\']+\)~ims';
		$raw_in_php = _class('dir')->grep($regex_php, APP_PATH, '*.php', array('exclude_paths' => $exclude_paths));
		$names = array();
		foreach ((array)$raw_in_php as $path => $matches) {
			$lines = file($path);
			foreach ((array)$matches as $raw) {
				preg_match($regex_php, $raw, $m);
				$name = trim(trim(trim($m['name']), '"\''));
				$names[$name] = $name;
				$raw = trim(trim(trim($raw), '"\''));
				foreach ((array)$lines as $n => $line) {
					if (strpos($line, $raw) !== false) {
						$by_line[$path][$raw][$n] = $n;
						$by_path[$name][$path][$n] = $n;
					}
				}
			}
		}
		$regex_tpl = '~\{(asset|js|css)\(\)\}\s+(?P<name>[^\{\}\(\)\$]+?)\s+\{\/\1\}~ims';
		$raw_in_tpl = _class('dir')->grep($regex_tpl, APP_PATH, '*.stpl', array('exclude_paths' => $exclude_paths));
		foreach ((array)$raw_in_tpl as $path => $matches) {
			$lines = file($path);
			foreach ((array)$matches as $raw) {
				preg_match($regex_tpl, $raw, $m);
				$name = trim(trim(trim($m['name']), '"\''));
				$names[$name] = $name;
				$raw = trim(trim(trim($raw), '"\''));
				foreach ((array)$lines as $n => $line) {
					if (strpos($line, $raw) !== false) {
						$by_line[$path][$raw][$n] = $n;
						$by_path[$name][$path][$n] = $n;
					}
				}
			}
		}
		$assets = _class('assets');
		$names_by_type = array(
			'user'	=> array(),
			'admin'	=> array(),
		);
		foreach ((array)$names as $k => $v) {
			if (substr($k, 0, 2) === '//' || substr($k, 0, 7) === 'http://' || substr($k, 0, 8) === 'https://') {
				unset($names[$k]);
				continue;
			}
			$details = $assets->get_asset_details($k);
			if (empty($details) || (isset($details['config']) && $details['config']['no_cache'])) {
				unset($names[$k]);
				continue;
			}
			if (isset($details['config']['main_type'])) {
				$main_type = $details['config']['main_type'];
				$names_by_type[$main_type][$k] = $v;
			} else {
				$names_by_type['user'][$k] = $v;
				$names_by_type['admin'][$k] = $v;
			}
		}
		ksort($names);
		ksort($names_by_type['user']);
		ksort($names_by_type['admin']);
		$table = array();
		foreach ((array)$names as $name) {
			$table[$name] = '<small>'.implode('<br>', array_keys($by_path[$name])).'</small>';
		}
		$export = '<'.'?php'.PHP_EOL.'return array('.PHP_EOL
			.'\'user\' => '.preg_replace('~\s{2}[0-9]+\s+=>\s+~i', '  ', var_export(array_keys($names_by_type['user']), 1)). ','. PHP_EOL
			.'\'admin\' => '.preg_replace('~\s{2}[0-9]+\s+=>\s+~i', '  ', var_export(array_keys($names_by_type['admin']), 1)). ','. PHP_EOL
			.');';
		return '<pre style="color:white;background:black;line-height:1em;font-weight:bold;"><small>'._prepare_html($export).'</small></pre>'
			.'<h3>Used assets</h3>'.html()->simple_table($table);
	}

	/**
	*/
	function cache_info() {
		$assets = clone _class('assets');
		$assets->USE_CACHE = false;
		$dir = _class('dir');
		$enabled_langs = main()->get_data('languages');
		$main_types = array('user', 'admin');
		foreach ((array)$main_types as $main_type) {
			$assets->_override['main_type'] = $main_type;
			foreach ((array)$enabled_langs as $lang) {
				$assets->_override['language'] = $lang;
				$cache_dir = $assets->_cache_dir($out_type = '');
				$tmp = array();
				foreach ((array)$dir->rglob($cache_dir) as $path) {
					if (is_dir($path) || substr($path, -5, 5) === '.info') {
						continue;
					}
					$tmp[] = $path;
				}
				$contents[] = implode(PHP_EOL, $tmp);
			}
		}
		return 'Cache info: <pre style="line-height:1em;"><small>'.implode(PHP_EOL, $contents).'</small>';
	}

	/**
	*/
	function cache_purge() {
		$assets = clone _class('assets');
		$assets->USE_CACHE = false;
		$dir = _class('dir');
		$enabled_langs = main()->get_data('languages');
		$main_types = array('user', 'admin');
		foreach ((array)$main_types as $main_type) {
			$assets->_override['main_type'] = $main_type;
			foreach ((array)$enabled_langs as $lang) {
				$assets->_override['language'] = $lang;
				$cache_dir = $assets->_cache_dir($out_type = '');
				$tmp = array();
				foreach ((array)$dir->rglob($cache_dir) as $path) {
					if (is_dir($path) || substr($path, -5, 5) === '.info') {
						continue;
					}
					$tmp[] = $path;
				}
				$contents[] = implode(PHP_EOL, $tmp);
				$dir->delete($cache_dir, $and_start_dir = true);
			}
		}
		return 'Deleted: <pre style="line-height:1em;"><small>'.implode(PHP_EOL, $contents).'</small>';
	}

	/**
	*/
	function cache_fill() {
		$this->cache_purge();
// TODO: use temp dir while caching
// TODO: verify that all files are available
		$assets = clone _class('assets');
		$assets->ADD_IS_DIRECT_OUT = true;
		$assets->USE_CACHE = true;
		$assets->COMBINE = false;
		$assets->FORCE_LOCAL_STORAGE = false;
		($cache_dir_tpl = $GLOBALS['PROJECT_CONF']['assets']['CACHE_DIR_TPL']) && $assets->CACHE_DIR_TPL = $cache_dir_tpl;
		$combined_names = $assets->load_combined_config($force = true);
#		if (is_callable($combined_names)) { $combined_names = $combined_names(); }

		$cur_lang = conf('language');

		$dir = _class('dir');
		$enabled_langs = main()->get_data('languages');
		$main_types = array('user', 'admin');
		foreach ((array)$main_types as $main_type) {
			$assets->_override['main_type'] = $main_type;
			foreach ((array)$enabled_langs as $lang) {
				conf('language', $lang);
				$assets->_override['language'] = $lang;
				foreach ((array)$assets->supported_out_types as $out_type) {
					foreach ((array)$combined_names[$main_type] as $name) {
						// echo $main_type.' | '.$lang.' | '.$out_type.' | '.$name.'<br>';
						$direct_out = $assets->add_asset($name);
					}
				}
			}
		}
		conf($cur_lang);
		return $this->cache_info();
	}

	/**
	* Force combine assets according to config
	*/
	function combine() {
		$assets = clone _class('assets');
		$assets->clean_all();
		$assets->ADD_IS_DIRECT_OUT = false;
		$assets->USE_CACHE = true;
		$assets->COMBINE = true;
		$combined_names = $assets->load_combined_config($force = true);
		$assets->FORCE_LOCAL_STORAGE = false;
		($cache_dir_tpl = $GLOBALS['PROJECT_CONF']['assets']['CACHE_DIR_TPL']) && $assets->CACHE_DIR_TPL = $cache_dir_tpl;
#		if (is_callable($combined_names)) { $combined_names = $combined_names(); }

		$cur_lang = conf('language');

		$dir = _class('dir');
		$enabled_langs = main()->get_data('languages');
		$main_types = array('user', 'admin');
		foreach ((array)$main_types as $main_type) {
			$assets->_override['main_type'] = $main_type;
			foreach ((array)$enabled_langs as $lang) {
				conf('language', $lang);
				$assets->_override['language'] = $lang;
				foreach ((array)$assets->supported_out_types as $out_type) {
					$assets->clean_all();
					$combined_path = $assets->_get_combined_path($out_type);
					if (file_exists($combined_path)) {
						unlink($combined_path);
						unlink($combined_path.'.info');
					}
					foreach ((array)$combined_names[$main_type] as $name) {
						$assets->add_asset($name);
					}
					$out = $assets->show($out_type);
					$combined_dir = dirname($assets->_get_combined_path($_out_type = ''));
					$tmp = array();
					foreach ((array)$dir->rglob($combined_dir) as $path) {
						if (is_dir($path) || substr($path, -5, 5) === '.info') {
							continue;
						}
						$tmp[] = $path;
					}
					$contents[] = implode(PHP_EOL, $tmp);
				}
			}
		}
		conf($cur_lang);
		return 'Combined info: <pre style="line-height:1em;"><small>'.implode(PHP_EOL, $contents).'</small>';
	}

	/**
	*/
	function upload() {
// TODO: upload cache and combined into outer storage (CDN, FTP, S3, ...)
	}

	/**
	*/
	function settings() {
// TODO: pretty show current important assets settings and optionally allow to change them
	}
}
