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
		$regex_php = '~[\s](asset|js|css)\([\s"\']+(?P<name>[^\(\)\{\}\$]+)[\s"\']+\)~ims';
		$raw_in_php = _class('dir')->grep($regex_php, APP_PATH, '*.php');
		$assets = array();
		foreach ((array)$raw_in_php as $path => $matches) {
			$lines = file($path);
			foreach ((array)$matches as $raw) {
				preg_match($regex_php, $raw, $m);
				$name = trim(trim($m['name']), '"\' \t');
				$assets[$name] = $name;
				$raw = trim(trim($raw), '"\' \t');
				foreach ((array)$lines as $n => $line) {
					if (strpos($line, $raw) !== false) {
						$by_line[$path][$raw][$n] = $n;
						$by_path[$name][$path][$n] = $n;
					}
				}
			}
		}
		$regex_tpl = '~\{(asset|js|css)\(\)\}\s+(?P<name>[^\{\}\(\)\$]+?)\s+\{\/\1\}~ims';
		$raw_in_tpl = _class('dir')->grep($regex_tpl, APP_PATH, '*.stpl');
		foreach ((array)$raw_in_tpl as $path => $matches) {
			$lines = file($path);
			foreach ((array)$matches as $raw) {
				preg_match($regex_tpl, $raw, $m);
				$name = trim(trim($m['name']), '"\' \t');
				$assets[$name] = $name;
				$raw = trim(trim($raw), '"\' \t');
				foreach ((array)$lines as $n => $line) {
					if (strpos($line, $raw) !== false) {
						$by_line[$path][$raw][$n] = $n;
						$by_path[$name][$path][$n] = $n;
					}
				}
			}
		}
#			foreach ((array)$by_line[$path] as $raw => $nums) {
#				foreach ($nums as $n) {
#					$sources[$path][$raw] = PHP_EOL. implode(array_slice($lines, $n - 3, 6));
#				}
#			}
		foreach ((array)$assets as $k => $v) {
			if (substr($k, 0, 2) === '//' || substr($k, 0, 7) === 'http://' || substr($k, 0, 8) === 'https://') {
				unset($assets[$k]);
			}
		}
		ksort($assets);
#		$out[] = 'ASSETS: <pre>'.print_r(_prepare_html($assets), 1).'</pre>';
#		$out[] = 'BY LINE: <pre>'.print_r(_prepare_html($by_line), 1).'</pre>';
#		$out[] = 'SOURCES: <pre>'.print_r(_prepare_html($sources), 1).'</pre>';
#		$out[] = 'PHP: <pre>'.print_r(_prepare_html($raw_in_php), 1).'</pre>';
#		$out[] = 'TPL: <pre>'.print_r(_prepare_html($raw_in_tpl), 1).'</pre>';

#		return implode(PHP_EOL, $out);
		return html()->simple_table($assets);
	}
}
