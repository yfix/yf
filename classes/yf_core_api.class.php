<?php

/**
* Core API
*/
class yf_core_api {

	/**
	* This method will search and call all found hook methods from active modules
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
	* This method will search for hooks alongside active modules
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
	* This method will search for hooks alongside active modules
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
		$methods = array();
		foreach ((array)$this->get_methods($section) as $module => $method) {
			if ($method[0] == '_') {
				$methods[$module][$method] = $method;
			}
		}
		return $methods;
	}

	/**
	*/
	function get_public_methods($section = 'all') {
		$methods = array();
		foreach ((array)$this->get_methods($section) as $module => $method) {
			if ($method[0] != '_') {
				$methods[$module][$method] = $method;
			}
		}
		return $methods;
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
	function get_class_instance($name, $section) {
		if ($section == 'core') {
			$path = 'classes/';
		} elseif ($section == 'user') {
			$path = 'modules/';
		} elseif ($section == 'admin') {
			$path = 'admin_modules/';
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
			$modules['core'] = $this->get_core_classes();
		}
		if (in_array($section, array('all', 'user'))) {
			$modules['user'] = $this->get_user_modules();
		}
#		if (in_array($section, array('all', 'admin'))) {
#			$modules['admin'] = $this->get_admin_modules();
#		}
		return $modules;
	}

	/**
	*/
	function get_core_classes(&$paths = array()) {
		$prefix = YF_PREFIX;
		$suffix = YF_CLS_EXT;
		$globs = array(
			'project'			=> PROJECT_PATH.'classes/*'.$suffix,
			'project_plugins'	=> PROJECT_PATH.'plugins/*/classes/*'.$suffix,
			'framework'			=> YF_PATH.'classes/*'.$suffix,
			'framework_plugins'	=> YF_PATH.'plugins/*/classes/*'.$suffix,
#			'framework_p2'		=> YF_PATH.'priority2/classes/*'.$suffix,
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
	function get_user_modules() {
#		return _class('user_modules', 'admin_modules/')->_get_modules(array('with_sub_modules' => 1));
	}

	/**
	*/
	function get_admin_modules() {
#		return _class('admin_modules', 'admin_modules/')->_get_modules(array('with_sub_modules' => 1));
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
	function get_hook_types() {
// TODO
	}
	function get_callbacks() {
// TODO
	}
	function get_callback_types() {
// TODO
	}
	function get_events() {
// TODO
	}
	function get_functions() {
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
	function get_blocks() {
// TODO
	}
	function get_categories() {
// TODO
	}
	function get_menus() {
// TODO
	}
}
