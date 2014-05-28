<?php

/**
* Common methods for admin section stored here
*/
class yf_common_admin {

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		$self = 'common_admin';
		$func = null;
		if (isset( $this->_extend[$name] )) {
			$func = $this->_extend[$name];
		} elseif (isset( main()->_extend[$self][$name] )) {
			$func = main()->_extend[$self][$name];
		}
		if ($func) {
			return $func($args[0], $args[1], $args[2], $args[3], $this);
		}
		trigger_error(__CLASS__.': No method '.$name, E_USER_WARNING);
		return false;
	}


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
	*/
	function admin_wall_add($data = array()) {
# TODO: check this and enable
#		if (!is_array($data)) {
#			$data = func_get_args();
#		}
		return db()->insert_safe('admin_walls', array(
			'message'	=> isset($data['message']) ? $data['message'] : (isset($data[0]) ? $data[0] : ''),
			'object_id'	=> isset($data['object_id']) ? $data['object_id'] : (isset($data[1]) ? $data[1] : ''),
			'user_id'	=> isset($data['user_id']) ? $data['user_id'] : (isset($data[2]) ? $data[2] : main()->ADMIN_ID),
			'object'	=> isset($data['object']) ? $data['object'] : (isset($data[3]) ? $data[3] : $_GET['object']),
			'action'	=> isset($data['action']) ? $data['action'] : (isset($data[4]) ? $data[4] : $_GET['action']),
			'important'	=> isset($data['important']) ? $data['important'] : (isset($data[5]) ? $data[5] : 0),
			'old_data'	=> json_encode(isset($data['old_data']) ? $data['old_data'] : (isset($data[6]) ? $data[6] : '')),
			'new_data'	=> json_encode(isset($data['new_data']) ? $data['new_data'] : (isset($data[7]) ? $data[7] : '')),
			'add_date'	=> date('Y-m-d H:i:s'),
			'server_id'	=> (int)main()->SERVER_ID,
			'site_id'	=> (int)main()->SITE_ID,
		));
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
