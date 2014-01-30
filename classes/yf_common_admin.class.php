<?php

/**
* Common methods for admin section stored here
*/
class yf_common_admin {

	/**
	*/
	function _init() {
		$this->USER_ID		= main()->USER_ID;
		$this->USER_GROUP	= main()->USER_GROUP;
		$this->ADMIN_ID		= main()->ADMIN_ID;
		$this->ADMIN_GROUP	= main()->ADMIN_GROUP;
		$this->CENTER_BLOCK_ID = _class('core_blocks')->_get_center_block_id();
		$this->ADMIN_URL_HOST	= parse_url(WEB_PATH, PHP_URL_HOST);
		$this->ADMIN_URL_PATH	= parse_url(WEB_PATH, PHP_URL_PATH);
	}

	/**
	*/
	function _admin_link_is_allowed($link = '') {
		// Currently this works only for admin section
		if (MAIN_TYPE == 'user') {
			return false;
		}
		// Guests can see nothing
		if (!strlen($link) || !main()->ADMIN_ID || MAIN_TYPE == 'user') {
			return false;
		}
		// Super-admin can see any links
		if (main()->ADMIN_GROUP === 1) {
			return true;
		}
		$link_parts = parse_url($link);
		// Outer links simply allowed
		if (isset($link_parts['scheme']) && $link_parts['host'] && $link_parts['path']) {
			if ($link_parts['host']. $link_parts['path'] != $this->ADMIN_URL_HOST. $this->ADMIN_URL_PATH) {
				return true;
			}
		}
		// Maybe this is also outer link and no need to block it (or maybe rewrited?)
		if (!isset($link_parts['query'])) {
			return true;
		}
		parse_str($link_parts['query'], $u);
		$u = (array)$u;
		if (isset($u['task']) && in_array($u['task'], array('login','logout'))) {
			return true;
		}
		return (int)_class('core_blocks')->_check_block_rights($this->CENTER_BLOCK_ID, $u['object'], $u['action']);
	}

	/**
	* This method will search and call all found hook methods from active modules
	*/
	function call_hooks($hook_name, $params = array(), $section = 'all') {
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
		if (isset($this->cache[$cache_name])) {
			return $this->cache[$cache_name];
		}
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
		$this->cache[$cache_name] = $hooks;
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
		if (isset($this->cache[$cache_name])) {
			return $this->cache[$cache_name];
		}
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
		$this->cache[$cache_name] = $modules;
		return $modules;
	}
}
