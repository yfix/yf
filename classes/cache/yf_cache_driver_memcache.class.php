<?php

load('cache_driver', 'framework', 'classes/cache/');
class yf_cache_driver_memcache extends yf_cache_driver {

	/** @var array internal @conf_skip */
	public $DEFAULT	= array(
		'port'		=> 11211,
		'host'		=> '127.0.0.1', // !!! DO NOT USE 'localhost' on Ubuntu 10.04 (and maybe others) due to memcached bug
		'persistent'=> false,
	);
	/** @var object internal @conf_skip */
	public $_connection = null;
	/** @var array list of implemented featues @conf_skip */
	public $implemented = array(
		'get', 'set', 'del', 'clean_all', 'list_keys'
	);
	/** @var mixed @conf_skip */
	public $_memcache_new_extension = null;
// TODO: create setting which extension to use (memcache|memcached)

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		// Support for driver-specific methods
		if (is_object($this->_connected) && method_exists($this->_connected, $name)) {
			return call_user_func_array(array($this->_connected, $name), $args);
		}
		trigger_error(__CLASS__.': No method '.$name, E_USER_WARNING);
		return false;
	}

	/**
	*/
	function _init ($params = array()) {
		$conf_mc_host = conf('MEMCACHED_HOST');
		if ($conf_mc_host) {
			$this->DEFAULT['host'] = $conf_mc_host;
		}
		$conf_mc_port = conf('MEMCACHED_PORT');
		if ($conf_mc_host) {
			$this->DEFAULT['port'] = $conf_mc_port;
		}
		if (class_exists('Memcached')) {
			$this->_connection = new Memcached();
		} elseif (class_exists('Memcache')) {
			$this->_connection = new Memcache();
		}
		if (is_object($this->_connection)) {
			$mc_params = array($this->DEFAULT);
			if (isset($params['memcache']) && !empty($params['memcache'])) {
				$mc_params = is_array($params['memcache']) ? $params['memcache'] : array($params['memcache']);
			}
			$failed = true;
			foreach ((array)$mc_params as $server) {
				if (!is_array($server) || !isset($server['host'])) {
					continue;
				}
				$server['port'] = isset($server['port']) ? (int)$server['port'] : 11211;
				$server['persistent'] = isset($server['persistent']) ? (bool) $server['persistent'] : true;
				if ($this->_connection->addServer($server['host'], $server['port'], $server['persistent'])) {
					$failed = false;
				}
			}
		}
		if (is_object($this->_connection)) {
			$this->_memcache_new_extension = method_exists($this->_connection, 'getMulti');
			if ($this->_memcache_new_extension) {
				$this->implemented = array_merge($this->implemented, array('multi_get', 'multi_set', 'multi_del'));
			}
		}
	}

	/**
	*/
	function is_ready() {
		return isset($this->_connection) ? true : false;
	}

	/**
	*/
	function get($name, $ttl = 0, $params = array()) {
		if (!$this->is_ready()) {
			return null;
		}
		$result = $this->_connection->get($name);
		if (is_string($result)) {
			$try_unpack = unserialize($result);
			if ($try_unpack || substr($result, 0, 2) == 'a:') {
				$result = $try_unpack;
			}
		}
		return $result;
	}

	/**
	*/
	function set($name, $data, $ttl = 0) {
		if (!$this->is_ready()) {
			return null;
		}
		$result = null;
		// Solved set() trouble with many servers. http://www.php.net/manual/ru/function.memcache-set.php#84032
// TODO: test if really solved (not really checked before)
		if ($this->_memcache_new_extension) {
			if (!$this->_connection->replace($name, $data, $ttl)) {
				$result = $this->_connection->set($name, $data, $ttl);
			}
		} else {
			$flags = null; // MEMCACHE_COMPRESSED
			if (!$this->_connection->replace($name, $data, $flags, $ttl)) {
				$result = $this->_connection->set($name, $data, $flags, $ttl);
			}
		}
		return $result;
	}

	/**
	*/
	function del($name) {
		if (!$this->is_ready()) {
			return null;
		}
		return $this->_connection->delete($name, 0);
	}

	/**
	*/
	function clean_all() {
		if (!$this->is_ready()) {
			return null;
		}
		return $this->_connection->flush();
	}

	/**
	*/
	function list_keys($filter = '') {
		if (!$this->is_ready()) {
			return null;
		}
		return $this->_connection->getAllKeys();
	}

	/**
	*/
	function multi_get(array $names, $ttl = 0, $params = array()) {
		if (!$this->is_ready()) {
			return null;
		}
		if (!$this->_memcache_new_extension) {
			foreach ((array)$names as $name) {
				$result[$name] = $this->get($name);
			}
			return $result;
		}
// TODO: maybe use this one?: http://ua1.php.net/manual/en/memcached.getmultibykey.php
		return $this->_connection->getMulti($names);
	}

	/**
	*/
	function multi_set(array $data, $ttl = 0) {
		if (!$this->is_ready()) {
			return null;
		}
		if (!$this->_memcache_new_extension) {
			foreach ((array)$data as $name => $_data) {
				$result[$name] = $this->set($name, $_data);
			}
			return $result;
		}
// TODO: maybe use this one?: http://ua1.php.net/manual/en/memcached.setmultibykey.php
		return $this->_connection->setMulti($data, $ttl);
	}

	/**
	*/
	function multi_del(array $names) {
		if (!$this->is_ready()) {
			return null;
		}
		if (!$this->_memcache_new_extension) {
			foreach ((array)$names as $name) {
				$result[$name] = $this->del($name);
			}
			return $result;
		}
// TODO: maybe use this one?: http://ua1.php.net/manual/en/memcached.deletemultibykey.php
		return $this->_connection->deleteMulti($names);
	}
}
