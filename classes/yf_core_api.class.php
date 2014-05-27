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
	function get_class_instance($name, $section, $force_path) {
		$path = $this->section_paths[$section];
		if ($force_path) {
			$path = $force_path;
		}
		return _class($name, $path);
	}

	/**
	* This method will search for hooks alongside active modules
	*/
	function get_classes($section = 'all') {
		if (!in_array($section, array('all', 'user', 'admin', 'core'))) {
			$section = 'all';
		}
		$modules = array();
		if (in_array($section, array('all', 'core'))) {
			$modules['core'] = $this->get_modules_core();
		}
		if (in_array($section, array('all', 'user'))) {
			$modules['user'] = $this->get_modules_user();
		}
		if (in_array($section, array('all', 'admin'))) {
			$modules['admin'] = $this->get_modules_admin();
		}
		return $modules;
	}

	/**
	*/
	function get_modules_core() {
		return $this->_get_classes_by_params(array('folder' => $this->section_paths['core']));
	}

	/**
	*/
	function get_modules_user() {
		return $this->_get_classes_by_params(array('folder' => $this->section_paths['user']));
	}

	/**
	*/
	function get_modules_admin() {
		return $this->_get_classes_by_params(array('folder' => $this->section_paths['core']));
	}

	/**
	*/
	function _get_classes_by_params($extra = array(), &$paths = array()) {
		$prefix = isset($extra['prefix']) ? $extra['prefix'] : YF_PREFIX;
		$suffix = isset($extra['suffix']) ? $extra['suffix'] : YF_CLS_EXT;
		$folder = isset($extra['folder']) ? $extra['folder'] : $this->section_paths['core'];
		$globs = array(
			'project'			=> PROJECT_PATH. $folder.'*'.$suffix,
			'project_plugins'	=> PROJECT_PATH. 'plugins/*/'.$folder.'*'.$suffix,
			'framework'			=> YF_PATH. $folder.'*'.$suffix,
			'framework_plugins'	=> YF_PATH. 'plugins/*/'.$folder.'*'.$suffix,
// TODO: enable it, but test and cleanup before
#			'framework_p2'		=> YF_PATH. 'priority2/'.$folder.'*'.$suffix,
		);
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

	/**
	*/
	function get_submodules($section = 'all') {
		$data = array();
		foreach ($this->section_paths as $_section => $folder) {
			if ($section != 'all' && $section != $_section) {
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
	function get_submodules_methods($section = 'all') {
		$methods = array();
		foreach ((array)$this->get_submodules($section) as $_section => $modules) {
			foreach ((array)$modules as $module) {
				$obj = $this->get_class_instance($module, $_section, $this->section_paths[$_section].$module.'/');
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
	function get_functions() {
		$all = get_defined_functions();
		$funcs = array_combine($all['user'], $all['user']);
		is_array($funcs) && ksort($funcs);
		return $funcs;
	}

	/**
	*/
	function get_function_source($name) {
		$func = new ReflectionFunction($name);
		$info = array(
			'name'		=> $func->getName(),
			'file'		=> $func->getFileName(),
			'line_start'=> $func->getStartLine(),
			'line_end' 	=> $func->getEndline(),
#			'params'	=> $func->getParameters(),
			'comment'	=> $func->getDocComment(),
		);
		$info['source'] = $this->_get_file_slice($info['file'], $info['line_start'], $info['line_end']);
		return $info;
	}

	/***/
	function _get_file_slice($file, $line_start, $line_end) {
		$source = $this->_cache[__FUNCTION__][$file];
		if (is_null($source)) {
			$source = file($file);
			$this->_cache[__FUNCTION__][$file] = $source;
		}
		$offset = $line_end - $line_start;
#		if (!$offset) {
#			$line_start--;
#			$offset = 1;
#		}
		return implode(array_slice($source, $line_start - 1, $offset + 1));
	}

	/**
	*/
	function get_class_source() {
// TODO
	}
	function get_method_source() {
// TODO
	}
	function get_github_link() {
// TODO
	}
	function get_templates() {
// TODO
	}
	function get_template_source() {
// TODO
	}
	function get_langs() {
// TODO
	}
	function get_sites() {
// TODO
	}
	function get_site_info() {
// TODO
	}
	function get_servers() {
// TODO
	}
	function get_server_info() {
// TODO
	}
	function get_user_roles() {
// TODO
	}
	function get_user_groups() {
// TODO
	}
	function get_admin_roles() {
// TODO
	}
	function get_admin_groups() {
// TODO
	}
	function get_callbacks() {
// TODO
	}
	function get_events() {
// TODO
	}
	function get_libs() {
// TODO
	}
	function get_widgets() {
// TODO
	}
	function get_models() {
// TODO
	}
	function get_migrations() {
// TODO
	}
}
