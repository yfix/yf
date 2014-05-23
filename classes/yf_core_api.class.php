<?php

/**
* Core API
*/
class yf_core_api {
// TODO
	/**
	*/
	function get_classes($extra = array()) {
// TODO
	}

	/**
	*/
	function get_class_source() {
// TODO
	}

	/**
	*/
	function get_methods() {
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
	function get_hooks() {
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

	/**
	* This method will search and call all found hook methods from active modules
	*/
	function call_hooks($hook_name, &$params = array(), $section = 'all') {
		$data = array();
		foreach ((array)$this->find_hooks($hook_name) as $module => $methods) {
			foreach ((array)$methods as $method) {
				$data[$module.'__'.$method] = module($module)->$method($params);
			}
		}
		return $data;
	}

	/**
	* This method will search for hooks alongside active modules
	*/
	function find_hooks($hook_name, $section = 'all') {
		$hooks = array();
		foreach ((array)$this->find_all_hooks($section) as $module => $_hooks) {
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
	function find_all_hooks($section = 'all') {
		if (!in_array($section, array('all', 'user', 'admin'))) {
			$section = 'all';
		}
		$cache_name = __FUNCTION__.'__'.$section;
		$data = cache_get($cache_name);
		if ($data) {
			return $data;
		}
#		if (isset($this->cache[$cache_name])) {
#			return $this->cache[$cache_name];
#		}
		$hooks_prefix = '_hook_';
		$hooks_pl = strlen($hooks_prefix);

		$modules = $this->find_active_modules($section);
		$user_modules = $modules['user'];
		foreach ((array)$user_modules as $module) {
			foreach ((array)get_class_methods(module($module)) as $method) {
				if (substr($method, 0, $hooks_pl) != $hooks_prefix) {
					continue;
				}
				$hooks[$module][substr($method, $hooks_pl)] = $method;
			}
			if (is_array($hooks[$module])) {
				ksort($hooks[$module]);
			}
		}
		$admin_modules = $modules['admin'];
		foreach ((array)$admin_modules as $module) {
			foreach ((array)get_class_methods(module($module)) as $method) {
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
#		$this->cache[$cache_name] = $hooks;
		cache_set($cache_name, $hooks);
		return $hooks;
	}

	/**
	* This method will search for hooks alongside active modules
	*/
	function find_active_modules($section = 'all') {
		if (!in_array($section, array('all', 'user', 'admin'))) {
			$section = 'all';
		}
		$cache_name = __FUNCTION__.'__'.$section;
		$data = cache_get($cache_name);
		if ($data) {
			return $data;
		}
#		if (isset($this->cache[$cache_name])) {
#			return $this->cache[$cache_name];
#		}
		if (in_array($section, array('all', 'user'))) {
			$user_modules = module('user_modules')->_get_modules(array('with_sub_modules' => 1));
		}
		if (in_array($section, array('all', 'admin'))) {
			$admin_modules_prefix = 'admin:';
			foreach ((array)module('admin_modules')->_get_modules(array('with_sub_modules' => 1)) as $module_name) {
				$admin_modules[$admin_modules_prefix. $module_name] = $module_name;
			}
		}
		$modules = array();
		if (!empty($admin_modules)) {
			$modules['admin'] = $admin_modules;
		}
		if (!empty($user_modules)) {
			$modules['user'] = $user_modules;
		}
#		$this->cache[$cache_name] = $modules;
		cache_set($cache_name, $hooks);
		return $modules;
	}
}
