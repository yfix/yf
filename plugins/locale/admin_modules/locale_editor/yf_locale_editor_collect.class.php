<?php

/**
*/
class yf_locale_editor_collect {

	/**
	*/
	function _init () {
		$this->_parent = module('locale_editor');
	}

	/**
	* Collect variables from app and framework source files
	*/
	function collect() {
		$defaults = [
			'back_link' => url('/@object/vars'),
			'redirect_link' => url('/@object/vars'),
			'include_app' => 1,
			'include_framework' => 1,
			'find_php' => 1,
			'find_stpl' => 1,
			'find_angular' => 1,
			'include_admin' => 1,
			'min_length' => 3,
		];
		foreach ((array)$defaults as $k => $v) {
			!isset($a[$k]) && $a[$k] = $v;
		}
		$display_func = function() { return !is_post(); };
		return $this->_parent->_header_links(). '<br>'. 
			form($a + (array)$_POST)
			->validate(['min_length' => 'required'])
			->on_validate_ok(function($data,$e,$vr,$form) { return $this->_on_validate_ok($form); })
			->yes_no_box('include_app', ['display_func' => $display_func])
			->yes_no_box('include_framework', ['display_func' => $display_func])
			->yes_no_box('find_php', ['display_func' => $display_func])
			->yes_no_box('find_stpl', ['display_func' => $display_func])
			->yes_no_box('find_angular', ['display_func' => $display_func])
			->yes_no_box('include_admin', ['display_func' => $display_func])
			->number('min_length', ['class_add' => 'input-small', 'display_func' => $display_func])
			->save_and_back('', ['desc' => 'Collect', 'display_func' => $display_func])
		;
	}

	/**
	*/
	function _on_validate_ok($form) {
		$p = $_POST;

		$found_vars = $this->_parse_sources();

		$sql = [];
		foreach ((array)$found_vars as $var => $files) {
			$locations = [];
			foreach ((array)$files as $file => $lines) {
				$locations[] = $file.':'.$lines;
			}
			$var = trim($var);
			if (_strlen($var) < $p['min_length']) {
				continue;
			}
			$sql[$var] = [
				'value'		=> $var,
				'location'	=> implode('; ', $locations),
			];
			$stats['replaced']++;
		}
		if ($sql) {
			db()->replace_safe('locale_vars', $sql);
		}
		$stats['replaced'] && common()->message_success((int)$stats['replaced'].' existing variable(s) successfully replaced');
		!$stats	&& common()->message_info('Collect done, nothing changed');

		$form->container(a(['href' => '/@object/@action', 'title' => 'Back', 'icon' => 'fa fa-arrow-left', 'class' => 'btn btn-primary btn-small', 'target' => '']), ['wide' => true]);
		$form->container($this->_parent->_pre_text(_var_export(_prepare_html($found_vars), 1)), ['wide' => true]);
	}

	/**
	* Parse source code for translate variables
	*/
	function _parse_sources ($params = []) {
		$params = $params ?: $_POST;

		$scan = function($top, $type) {
			$dirs_map = [
				'framework' => YF_PATH,
				'app' => APP_PATH,
			];
			$globs = [
				'php' => '{,plugins/*/}{classes,modules}/{*,*/*,*/*/*}.php',
				'stpl' => '{,plugins/*/,www/}{templates}/*/{*,*/*,*/*/*}.stpl',
				'ng' => '{,plugins/*/,www/}{templates}/*/{*,*/*,*/*/*}.stpl',
			];
			$files = glob($dirs_map[$top].''.$globs[$type], GLOB_BRACE);
			foreach ((array)$files as $k => $file) {
				if (false !== strpos($file, '/test/')) {
					unset($files[$k]);
				} elseif (false !== strpos($file, '/templates/admin/')) {
					unset($files[$k]);
				}
			}
			return $files;
		};
		$files = [];
		if ($params['include_framework']) {
			$params['find_php'] && $files['framework']['php'] = $scan('framework', 'php');
			$params['find_stpl'] && $files['framework']['stpl'] = $scan('framework', 'stpl');
			$params['find_angular'] && $files['framework']['ng'] = $scan('framework', 'ng');
		}
		if ($params['include_app']) {
			$params['find_php'] && $files['app']['php'] = $scan('app', 'php');
			$params['find_stpl'] && $files['app']['stpl'] = $scan('app', 'stpl');
			$params['find_angular'] && $files['app']['ng'] = $scan('app', 'ng');
		}
		$collect_in_file = function($file, $type) {
			if (!$file) {
				return [];
			}
			$pspaces = '\s'."\t";
			$pquotes = '"\'';
			$patterns_translate	= [
# TODO: try tokenizer for php
				'php'	=> '~[\(\{\.,='.$pspaces.']+?'.'t'.'['.$pspaces.']*?\(['.$pspaces.']*?(?<var>\'[^\'$]+?\'|"[^"$]+?")~ims',
				'stpl'	=> '~\{t\(['.$pquotes.']*(?<var>['.$pspaces.'\w\-\.,:;%&#/><]*)['.$pquotes.']*[,]*[^\)\}]*\)\}~is',
				'ng'	=> '~\{\{['.$pspaces.$pquotes.']*(?<var>[^\|\}]+?)['.$pspaces.$pquotes.']*\|['.$pspaces.']*'.'translate'.'['.$pspaces.']*\}\}~is',
			];
			// Quick tests
			$raw = file_get_contents($file);
			if ($type == 'stpl' && false === strpos($raw, '{t(')) {
				return [];
			} elseif ($type == 'ng' && false === strpos($raw, '{{')) {
				return [];
			}
			$vars = [];
			$farray = file($file);
			$matched	= preg_match_all($patterns_translate[$type], implode(PHP_EOL, $farray), $m);
			if (!$matched || !isset($m[0])) {
				return [];
			}
			foreach ((array)$m['var'] as $mnum => $var) {
				$lines = [];
				$var = trim(trim(trim($var), $pquotes));
				foreach ((array)$farray as $line_num => $line_text) {
					if (false === strpos($line_text, $m[0][$mnum])) {
						continue;
					}
					$lines[] = $line_num;
				}
				if (empty($lines) || empty($var)) {
					continue;
				}
				$vars[$var] = implode(',', $lines);
			}
			return $vars;
		};
		$vars = [];
		foreach ((array)$files as $top => $types) {
			foreach ((array)$types as $type => $paths) {
				foreach ((array)$paths as $path) {
					if (!$path) {
						continue;
					}
					$short_path = str_replace([APP_PATH, YF_PATH], '', $path);
					foreach ((array)$collect_in_file($path, $type) as $var => $lines) {
						$vars[$var][$short_path] = $lines;
					}
				}
			}
		}
		$vars && ksort($vars);
		return $vars;
	}

	/**
	* Return array of all used locations in vars
	*/
	function _collect_all_vars_locations() {
		$used_locations = [];
		foreach ((array)from('locale_vars')->where_raw('location != ""')->get_2d('location,location AS l2') as $location) {
			foreach ((array)explode(';', $location) as $cur_location) {
				$cur_location = trim(substr($cur_location, 0, strpos($cur_location, ':')));
				if (empty($cur_location)) {
					continue;
				}
				$used_locations[$cur_location]++;
			}
		}
		if (!empty($used_locations)) {
			ksort($used_locations);
		}
		return $used_locations;
	}
}
