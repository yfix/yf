<?php

class yf_manage_assets {

	/**
	*/
	function show() {
		return $this->search_used();
#		return a('/@object/purge_cache', 'Purge cache');
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
		return '<h3>Used assets</h3>'.html()->simple_table($table);
	}
}
