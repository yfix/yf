<?php

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
			array('/@object/cache_purge/', 'Cache purge', 'fa fa-recycle'),
			array('/@object/cache_fill/', 'Cache fill', 'fa fa-refresh'),
			array('/@object/combine/', 'Combine', 'fa fa-rocket'),
			array('/@object/upload/', 'Upload', 'fa fa-upload'),
			array('/@object/settings/', 'Settings', 'fa fa-wrench'),
		));
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
		$assets = array();
		foreach ((array)$raw_in_php as $path => $matches) {
			$lines = file($path);
			foreach ((array)$matches as $raw) {
				preg_match($regex_php, $raw, $m);
				$name = trim(trim(trim($m['name']), '"\''));
				$assets[$name] = $name;
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
				$assets[$name] = $name;
				$raw = trim(trim(trim($raw), '"\''));
				foreach ((array)$lines as $n => $line) {
					if (strpos($line, $raw) !== false) {
						$by_line[$path][$raw][$n] = $n;
						$by_path[$name][$path][$n] = $n;
					}
				}
			}
		}
		foreach ((array)$assets as $k => $v) {
			if (substr($k, 0, 2) === '//' || substr($k, 0, 7) === 'http://' || substr($k, 0, 8) === 'https://') {
				unset($assets[$k]);
			}
		}
		ksort($assets);
		$table = array();
		foreach ((array)$assets as $name) {
			$table[$name] = '<small>'.implode('<br>', array_keys($by_path[$name])).'</small>';
		}
		return '<pre style="color:white;background:black;line-height:1em;font-weight:bold;"><small>'._prepare_html(var_export(array_keys($assets), 1)).'</small></pre>'
			.'<h3>Used assets</h3>'.html()->simple_table($table);
	}

	/**
	*/
	function show() {
		return redirect('/@object/search_used');
	}

	/**
	*/
	function cache_purge() {
// TODO
	}

	/**
	*/
	function cache_fill() {
// TODO
	}

	/**
	*/
	function combine() {
// TODO
	}

	/**
	*/
	function upload() {
// TODO
	}

	/**
	*/
	function settings() {
// TODO
	}

}
