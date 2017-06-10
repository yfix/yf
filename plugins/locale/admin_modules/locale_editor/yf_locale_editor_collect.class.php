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
		$a['back_link'] = url('/@object/vars');
		$a['redirect_link'] = $a['back_link'];
		!$a['lang'] && $a['lang'] = 'en';
		!isset($a['keep_existing']) && $a['keep_existing'] = 1;
		// To ensure that currently active langs are in top of the list
		$langs = [];
		foreach ((array)$this->_parent->_cur_langs as $lang => $name) {
			$langs[$lang] = $name;
		}
		$langs[''] = '-------------';
		foreach ((array)$this->_parent->_langs as $lang => $name) {
			$langs[$lang] = $name;
		}
		return form($a + (array)$_POST)
			->validate([
				'lang' => 'required',
			])
			->on_validate_ok(array(&$this, '_on_validate_ok'))
			->select_box('lang', $langs)
#			->yes_no_box('keep_existing')
			->save_and_back('', ['desc' => 'Import'])
		;
	}

	/**
	*/
	function _on_validate_ok() {
		$p = &$_POST;
#		$lang = $p['lang'];
#		$keep_existing = $p['keep_existing'];
		$all_vars = $this->_parent->_get_all_vars();

#		if (!$to_tr) {
#			common()->message_error('Translate failed, no suitable variables found');
#			return false;
		}
d($to_tr);
# TODO: testme
		$vars_from_code = $this->_parse_sources();
		foreach ((array)$vars_from_code as $cur_var_name => $var_files_info) {
			$location_array = [];
			foreach ((array)$var_files_info as $file_name => $line_numbers) {
				$location_array[] = $file_name.':'.$line_numbers;
			}
			$location	= implode('; ', $location_array);
			$sql_array	= [
				'value'		=> _es($cur_var_name),
				'location'	=> $location,
			];
#			if (isset($this->_locale_vars[$cur_var_name])) {
#				db()->update_safe('locale_vars', $sql_array, 'id='.intval($this->_locale_vars[$cur_var_name]['id']));
#			} else {
#				db()->insert_safe('locale_vars', $sql_array);
#			}
		}
# TODO: show some report after completion: where and how many found
#		cache_del('locale_translate_'.$lang);
		return js_redirect('/@object/vars');
	}	

	/**
	* Collect vars from source files, no framework, just project and given module name (internal use only method)
	*/
	function collect_for_module () {
		no_graphics(true);

		$module_name = preg_replace('/[^a-z0-9\_]/i', '', _strtolower(trim($_GET['id'])));
		if (!$module_name) {
			return print 'Error, no module name';
		}

		$vars = $this->_parse_sources([
			'only_project'	=> 1,
			'only_module'	=> $module_name,
		]);

		echo '<pre>';
		foreach ((array)$vars as $var => $paths) {
			echo $var.PHP_EOL;
		}
		echo '</pre>';
	}

	/**
	* Parse source code for translate variables
	*/
	function _parse_sources ($params = []) {
# TODO: test and optimize
# TODO: collect angular variables like this: {{'var'|translate}}
		$_include_php_pattern	= ['#\/(admin_modules|classes|functions|modules)#', '#\.php$#'];
		$_include_stpl_pattern	= ['#\/(templates)#', '#\.stpl$#'];
		$_exclude_pattern		= ['#\/(commands|docs|libs|scripts|sql|storage|tests)#', ''];
		$_translate_php_pattern	= "/[\(\{\.\,\s\t=]+?(t)[\s\t]*?\([\s\t]*?('[^'\$]+?'|\"[^\"\$]+?\")/ims";
		$_translate_stpl_pattern= "/\{(t)\([\"']*([\s\w\-\.\,\:\;\%\&\#\/\<\>]*)[\"']*[,]*[^\)\}]*\)\}/is";

		$vars_array = [];

		$php_path_pattern	= '';
		$stpl_path_pattern	= '';
		if ($params['only_module']) {
			$_include_php_pattern	= ['#/(modules)#', '#'.preg_quote($params['only_module'], '#').'\.class\.php$#'];
			$_include_stpl_pattern	= ['#/templates#', '#\.stpl$#'];
			$stpl_path_pattern = '#templates/[^/]+/'.$params['only_module'].'/#';
		}
		if (!$params['only_project']) {
			if (!$params['only_stpls']) {
				$yf_framework_php_files	= _class('dir')->scan_dir(YF_PATH, true, $_include_php_pattern, $_exclude_pattern);
			}
			if (!$params['only_php']) {
				$yf_framework_stpl_files = _class('dir')->scan_dir(YF_PATH, true, $_include_stpl_pattern, $_exclude_pattern);
			}
		}
		if (!$params['only_framework']) {
			if (!$params['only_stpls']) {
				$cur_project_php_files = _class('dir')->scan_dir(INCLUDE_PATH, true, $_include_php_pattern, $_exclude_pattern);
			}
			if (!$params['only_php']) {
				$cur_project_stpl_files = _class('dir')->scan_dir(INCLUDE_PATH, true, $_include_stpl_pattern, $_exclude_pattern);
			}
		}
		foreach ((array)$yf_framework_php_files as $file_name) {
			$short_file_name = str_replace([REAL_PATH, INCLUDE_PATH, YF_PATH], '', $file_name);
			foreach ((array)$this->_collect_in_file($file_name, $_translate_php_pattern) as $cur_var_name => $code_lines) {
				$vars_array[$cur_var_name][$short_file_name] = $code_lines;
			}
		}
		foreach ((array)$cur_project_php_files as $file_name) {
			$short_file_name = str_replace([REAL_PATH, INCLUDE_PATH, YF_PATH], '', $file_name);
			foreach ((array)$this->_collect_in_file($file_name, $_translate_php_pattern) as $cur_var_name => $code_lines) {
				$vars_array[$cur_var_name][$short_file_name] = $code_lines;
			}
		}
		foreach ((array)$yf_framework_stpl_files as $file_name) {
			$short_file_name = str_replace([REAL_PATH, INCLUDE_PATH, YF_PATH], '', $file_name);
			foreach ((array)$this->_collect_in_file($file_name, $_translate_stpl_pattern) as $cur_var_name => $code_lines) {
				$vars_array[$cur_var_name][$short_file_name] = $code_lines;
			}
		}
		foreach ((array)$cur_project_stpl_files as $file_name) {
			$short_file_name = str_replace([REAL_PATH, INCLUDE_PATH, YF_PATH], '', $file_name);
			if ($stpl_path_pattern && !preg_match($stpl_path_pattern, $short_file_name)) {
				continue;
			}
			foreach ((array)$this->_collect_in_file($file_name, $_translate_stpl_pattern) as $cur_var_name => $code_lines) {
				$vars_array[$cur_var_name][$short_file_name] = $code_lines;
			}
		}
		ksort($vars_array);
		return $vars_array;
	}

	/**
	* Get vars from the given file name
	*/
	function _collect_in_file($file_name = '', $pattern = '') {
		$vars_array = [];
		if (empty($file_name)) {
			return $vars_array;
		}
		$file_source_array = file($file_name);
		$match	= preg_match_all($pattern, implode(PHP_EOL, $file_source_array), $matches);
		if (empty($matches[0])) {
			return $vars_array;
		}
		foreach ((array)$matches[2] as $match_number => $cur_var_name) {
			$code_lines		= [];
			$cur_var_name	= trim($cur_var_name, "\"'");
			foreach ((array)$file_source_array as $line_number => $line_text) {
				if (false === strpos($line_text, $matches[0][$match_number])) {
					continue;
				}
				$code_lines[] = $line_number;
			}
			if (empty($code_lines) || empty($cur_var_name)) {
				continue;
			}
			$vars_array[$cur_var_name] = implode(',',$code_lines);
		}
		return $vars_array;
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
