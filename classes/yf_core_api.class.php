<?php

/**
* Core API
*/
class yf_core_api {

	var $section_paths = array(
		'core'	=> 'classes/',
		'user'	=> 'modules/',
		'admin'	=> 'admin_modules/',
	);
	/** @security Project code needed to be defended from easy traversing */
	var $SOURCE_ONLY_FRAMEWORK = false;

	/**
	* This method will search and call all found hook methods from active modules
	* @example: call_hooks('settings', $params)
	*/
	function call_hooks($hook_name, &$params = array(), $section = 'all') {
		$data = array();
		foreach ((array)$this->get_hooks($hook_name) as $module => $methods) {
			foreach ((array)$methods as $method) {
				$obj = $this->get_class_instance($module, $section);
				$data[$module.'__'.$method] = $obj->$method($params);
			}
		}
		return $data;
	}

	/**
	*/
	function get_hooks($hook_name, $section = 'all') {
		$hooks = array();
		foreach ((array)$this->get_all_hooks($section) as $module => $_hooks) {
			foreach ((array)$_hooks as $name => $method_name) {
				if ($name == $hook_name) {
					$hooks[$module][$name] = $method_name;
				}
			}
		}
		return $hooks;
	}

	/**
	*/
	function get_available_hooks($section = 'all') {
		$avail_hooks = array();
		foreach ((array)$this->get_all_hooks($section) as $module => $_hooks) {
			foreach ((array)$_hooks as $name => $method_name) {
				$avail_hooks[$name][$module] = $method_name;
			}
		}
		return $avail_hooks;
	}

	/**
	*/
	function get_all_hooks($section = 'all') {
		$hooks = array();
		$hooks_prefix = '_hook_';
		$hooks_pl = strlen($hooks_prefix);
		foreach ((array)$this->get_private_methods($section) as $module => $methods) {
			foreach ((array)$methods as $method) {
				if (substr($method, 0, $hooks_pl) != $hooks_prefix) {
					continue;
				}
				$hooks[$module][substr($method, $hooks_pl)] = $method;
			}
			if (is_array($hooks[$module])) {
				ksort($hooks[$module]);
			}
		}
		if (is_array($hooks)) {
			ksort($hooks);
		}
		return $hooks;
	}

	/**
	*/
	function get_private_methods($section = 'all') {
		$data = array();
		foreach ((array)$this->get_methods($section) as $module => $methods) {
			foreach ((array)$methods as $method) {
				if ($method[0] == '_') {
					$data[$module][$method] = $method;
				}
			}
		}
		return $data;
	}

	/**
	*/
	function get_public_methods($section = 'all') {
		$data = array();
		foreach ((array)$this->get_methods($section) as $module => $method) {
			foreach ((array)$methods as $method) {
				if ($method[0] != '_') {
					$data[$module][$method] = $method;
				}
			}
		}
		return $data;
	}

	/**
	*/
	function get_methods($section = 'all') {
		$methods = array();
		foreach ((array)$this->get_classes($section) as $_section => $modules) {
			foreach ((array)$modules as $module) {
				$obj = $this->get_class_instance($module, $_section);
				foreach ((array)get_class_methods($obj) as $method) {
					$methods[$module][$method] = $method;
				}
			}
		}
		foreach ((array)$methods as $module => $_methods) {
			ksort($methods[$module]);
		}
		return $methods;
	}

	/**
	*/
	function get_classes($section = 'all') {
		if (!in_array($section, array('all', 'user', 'admin', 'core'))) {
			$section = 'all';
		}
		$modules = array();
		if (in_array($section, array('all', 'core'))) {
			$modules['core'] = $this->_get_classes_by_params(array('folder' => $this->section_paths['core']));
		}
		if (in_array($section, array('all', 'user'))) {
			$modules['user'] = $this->_get_classes_by_params(array('folder' => $this->section_paths['user']));
		}
		if (in_array($section, array('all', 'admin'))) {
			$modules['admin'] = $this->_get_classes_by_params(array('folder' => $this->section_paths['core']));
		}
		return $modules;
	}

	/**
	*/
	function get_submodules($section = 'all') {
		$data = array();
		foreach ($this->section_paths as $_section => $folder) {
			if ($section != 'all' && $section != $_section) {
				continue;
			}
			// Currently I do not want to analyze submodules from core
			if ($_section == 'core') {
				continue;
			}
			$_data = array();
			$paths = array();
			$this->_get_classes_by_params(array('folder' => $folder.'*/'), $paths);
			foreach ((array)$paths as $name => $_paths) {
				if (!is_array($_paths)) {
					continue;
				}
				$path = current($_paths);
				$subdir = basename(dirname($path));
				$_data[$subdir][$name] = $name;
			}
			if (is_array($_data)) {
				ksort($_data);
			}
			$data[$_section] = $_data;
		}
		return $data;
	}

	/**
	*/
	function get_functions() {
		$all = get_defined_functions();
		$funcs = array_combine($all['user'], $all['user']);
		is_array($funcs) && ksort($funcs);
		return $funcs;
	}

	/**
	*/
	function get_function_source($name) {
		$r = new ReflectionFunction($name);
		$info = array(
			'name'		=> $r->getName(),
			'file'		=> $r->getFileName(),
			'line_start'=> $r->getStartLine(),
			'line_end' 	=> $r->getEndline(),
			'params'	=> $r->getParameters(),
			'comment'	=> $r->getDocComment(),
		);
		$info['source'] = $this->_get_file_slice($info['file'], $info['line_start'], $info['line_end']);
		return $info;
	}

	/**
	*/
	function get_method_source($module, $method, $section = 'all') {
		$obj = $this->get_class_instance($module, $section);
		return $this->_get_method_source($obj, $method);
	}

	/**
	* Examples: get_gihub_link('my_array_merge'), get_gihub_link('core_css.show_css')
	*/
	function get_github_link($input, $section = 'all') {
		$is_module	= false;
		$is_func	= false;
		if (is_array($input)) {
			if ($input['is_module']) {
				list($module, $method) = explode('.', $input['is_module']);
				if (!$module || !$method) {
					return '';
				}
				$is_module = true;
			} elseif ($input['is_func'] && $input['name'] && function_exists($input['name'])) {
				$is_func = $input['name'];
			}
		} elseif (false !== strpos($input, '.')) {
			list($module, $method) = explode('.', $input);
			if (!$module || !$method) {
				return '';
			}
			$is_module = true;
		} elseif (is_string($input) && function_exists($input)) {
			$is_func = $input;
		}
		if ($is_module) {
			$info = $this->get_method_source($module, $method, $section);
		} elseif ($is_func) {
			$info = $this->get_function_source($is_func);
		}
		$gh_url = $info ? 'https://github.com/yfix/yf/tree/master/'.substr($info['file'], strlen(YF_PATH)).'#L'.$info['line_start'] : '';
		return $gh_url ? '<a target="_blank" class="btn btn-primary btn-small btn-sm" href="'.$gh_url.'">Github <i class="icon icon-github"></i></a>': '';
	}

	/**
	*/
	function get_module_tests($module) {
		$tests_dir = YF_PATH.'.dev/tests/';
		$path = $tests_dir.'class_'.$module.'.Test.php';
		if (file_exists($path)) {
			return file_get_contents($path);
		}
		return false;
	}

	/**
	*/
	function get_function_tests($name) {
		$tests_dir = YF_PATH.'.dev/tests/';
		$path = $tests_dir.'func_'.$name.'.Test.php';
		if (file_exists($path)) {
			return file_get_contents($path);
		} else {
			$path = $tests_dir.'func_'.ltrim($name, '_').'.Test.php';
			if (file_exists($path)) {
				return file_get_contents($path);
			}
		}
		return false;
	}

	/**
	*/
	function get_module_docs($name) {
		$docs_dir = YF_PATH.'.dev/docs/en/';
		$f = $this->docs_dir. $name. '.stpl';
		if (file_exists($f)) {
			return '<section class="page-contents">'.tpl()->parse_string(file_get_contents($f), $replace, 'doc_'.$name).'</section>';
		}
		return false;
	}

	/**
	*/
	function get_method_docs($name, $method) {
		$docs_dir = YF_PATH.'.dev/docs/en/';
		$f = $this->docs_dir. $name. '/'.$method.'.stpl';
		if (file_exists($f)) {
			return '<section class="page-contents">'.tpl()->parse_string(file_get_contents($f), $replace, 'doc_'.$name.'.'.$method).'</section>';
		}
		return false;
	}

	/**
	*/
	function get_function_docs($name) {
		$docs_dir = YF_PATH.'.dev/docs/en/';
		$f = $this->docs_dir. $name. '.stpl';
		if (file_exists($f)) {
			return '<section class="page-contents">'.tpl()->parse_string(file_get_contents($f), $replace, 'doc_'.$name).'</section>';
		}
		return false;
	}

	/**
	*/
	function get_callbacks() {
// TODO
	}

	/**
	*/
	function get_events() {
// TODO
	}

	/**
	*/
	function get_langs() {
// TODO
	}

	/**
	*/
	function get_sites() {
// TODO
	}

	/**
	*/
	function get_site_info() {
// TODO
	}

	/**
	*/
	function get_servers() {
// TODO
	}

	/**
	*/
	function get_server_info() {
// TODO
	}

	/**
	*/
	function get_user_roles() {
// TODO
	}

	/**
	*/
	function get_user_groups() {
// TODO
	}

	/**
	*/
	function get_admin_roles() {
// TODO
	}

	/**
	*/
	function get_admin_groups() {
// TODO
	}

	/**
	*/
	function get_templates() {
// TODO
	}

	/**
	*/
	function get_template_source() {
// TODO
	}

	/**
	*/
	function get_libs() {
// TODO
	}

	/**
	*/
	function get_models() {
// TODO
	}

	/**
	*/
	function get_migrations() {
// TODO
	}

	/**
	*/
	function get_widgets() {
// TODO
/*
		$prefix = 'widget_';
		$prefix_len = strlen($prefix);
		$data = array();
		foreach ((array)$this->get_all_hooks($section) as $module => $_hooks) {
			foreach ((array)$_hooks as $name => $method_name) {
				if (substr($name, 0, $prefix_len) != $prefix) {
					continue;
				}
				$data[$name][$module] = $method_name;
			}
		}
		return $data;
*/
	}

	/**
	*/
	function get_submodules_methods($section = 'all') {
		$methods = array();
		foreach ((array)$this->get_submodules($section) as $_section => $modules) {
			foreach ((array)$modules as $module => $submodules) {
				foreach ((array)$submodules as $submodule) {
// TODO: need to solve several troubles with instantinating submodules at once
#					$obj = $this->get_class_instance($submodule, $_section, $this->section_paths[$_section].$module.'/');
					foreach ((array)get_class_methods($obj) as $method) {
						$methods[$submodule][$method] = $method;
					}
				}
			}
		}
		foreach ((array)$methods as $module => $_methods) {
			ksort($methods[$module]);
		}
		return $methods;
	}

	/**
	*/
	function get_class_instance($name, $section, $force_path) {
		$path = $this->section_paths[$section];
		if ($force_path) {
			$path = $force_path;
		}
		return _class($name, $path);
	}

	/**
	*/
	function add_syntax_highlighter() {
		require_js('//cdnjs.cloudflare.com/ajax/libs/highlight.js/8.0/highlight.min.js');
		require_js('//cdnjs.cloudflare.com/ajax/libs/highlight.js/8.0/languages/php.min.js');
		require_js('<script>hljs.initHighlightingOnLoad();</script>');
		require_css('//cdnjs.cloudflare.com/ajax/libs/highlight.js/8.0/styles/railscasts.min.css');
		require_css('section.page-contents pre, pre.prettyprint {
			background-color: transparent;
			border: 0;
			font-family: inherit;
			font-size: inherit;
			font-weight: bold;
		}');
	}

	/**
	*/
	function _get_classes_by_params($extra = array(), &$paths = array()) {
		$prefix = isset($extra['prefix']) ? $extra['prefix'] : YF_PREFIX;
		$suffix = isset($extra['suffix']) ? $extra['suffix'] : YF_CLS_EXT;
		$folder = isset($extra['folder']) ? $extra['folder'] : $this->section_paths['core'];

		$globs = array();
		if (!$this->SOURCE_ONLY_FRAMEWORK) {
			$globs['project']			= PROJECT_PATH. $folder.'*'.$suffix;
			$globs['project_plugins']	= PROJECT_PATH. 'plugins/*/'.$folder.'*'.$suffix;
		}
		$globs['framework']			= YF_PATH. $folder.'*'.$suffix;
		$globs['framework_plugins']	= YF_PATH. 'plugins/*/'.$folder.'*'.$suffix;
// TODO: enable it, but test and cleanup before
#		$globs['framework_p2']		= YF_PATH. 'priority2/'.$folder.'*'.$suffix;

		$prefix_len = strlen($prefix);
		$suffix_len = strlen($suffix);
		$classes = array();
		foreach ($globs as $glob) {
			foreach (glob($glob) as $path) {
				$name = substr(basename($path), 0, -$suffix_len);
				if (substr($name, 0, $prefix_len) == $prefix) {
					$name = substr($name, $prefix_len);
				}
				$classes[$name] = $name;
				$paths[$name][$path] = $path;
			}
		}
		if (is_array($classes)) {
			ksort($classes);
		}
		return $classes;
	}

	/***/
	function _get_file_slice($file, $line_start, $line_end) {
		$source = $this->_cache[__FUNCTION__][$file];
		if (is_null($source)) {
			$source = file($file);
			$this->_cache[__FUNCTION__][$file] = $source;
		}
		$offset = $line_end - $line_start;
		return implode(array_slice($source, $line_start - 1, $offset + 1));
	}

	/***/
	function _get_method_source($cls, $method) {
		if (is_object($cls)) {
			$cls = get_class($cls);
		}
		$methods = $this->_cache[__FUNCTION__][$cls];
		if (is_null($methods)) {
			$methods = $this->_get_methods_source($cls);
			$this->_cache[__FUNCTION__][$cls] = $methods;
		}
		return $methods[$method];
	}

	/***/
	function _get_methods_source($cls) {
		if (is_object($cls)) {
			$cls = get_class($cls);
		}
		$data = array();
		$class = new ReflectionClass($cls);
		foreach ($class->getMethods() as $v) {
			$name = $v->name;
			if ($name == 'show' || substr($name, 0, 1) == '_') {
				continue;
			}
			$r = new ReflectionMethod($cls, $name);
			$info = array(
				'name'		=> $name,
				'file'		=> $r->getFileName(),
				'line_start'=> $r->getStartLine(),
				'line_end'	=> $r->getEndLine(),
				'params'	=> $r->getParameters(),
				'comment'	=> $r->getDocComment(),
			);
			$info['source'] = $this->_get_file_slice($info['file'], $info['line_start'], $info['line_end']);
			$data[$name] = $info;
		}
		return $data;
	}
}
