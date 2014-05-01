<?php

load('cache_driver', 'framework', 'classes/cache/');
class yf_cache_driver_memcache extends yf_cache_driver {

	/** @var array internal @conf_skip */
	public $MEMCACHE_DEF_PARAMS	= array(
		'port'		=> 11211,
		'host'		=> '127.0.0.1', // !!! DO NOT USE 'localhost' on Ubuntu 10.04 (and maybe others) due to memcached bug
		'persistent'=> false,
	);
	/** @var object internal @conf_skip */
	public $_memcache				= null;
	/** @var array list of implemented featues */
	public $implemented = array(
		'get', 'set', 'del', 'multi_get', 'multi_set', 'multi_del'//, 'clean', 'list_all', ...
	);

	/**
	* Framework constructor
	*/
	function _init ($params = array()) {
#		$this->MEMCACHE_DEF_PARAMS = &_class('cache')->MEMCACHE_DEF_PARAMS;

		$conf_mc_host = conf('MEMCACHED_HOST');
		if ($conf_mc_host) {
			$this->MEMCACHE_DEF_PARAMS['host'] = $conf_mc_host;
		}
		$conf_mc_port = conf('MEMCACHED_PORT');
		if ($conf_mc_host) {
			$this->MEMCACHE_DEF_PARAMS['port'] = $conf_mc_port;
		}
#		if ($this->DRIVER == 'memcache') {
			$this->_memcache = null;
			$mc_obj = null;
			if (class_exists('Memcached')) {
				$mc_obj = new Memcached();
			} elseif (class_exists('Memcache')) {
				$mc_obj = new Memcache();
			}
			if (is_object($mc_obj)) {
				$mc_params = (isset($params['memcache']) && !empty($params['memcache'])) 
					? (is_array($params['memcache']) ? $params['memcache'] : array($params['memcache'])) 
					: array($this->MEMCACHE_DEF_PARAMS);
				$failed = true;
				foreach ((array)$mc_params as $server) {
					if (!is_array($server) || !isset($server['host'])) {
						continue;
					}
					$server['port'] = isset($server['port']) ? (int)$server['port'] : 11211;
					$server['persistent'] = isset($server['persistent']) ? (bool) $server['persistent'] : true;
					if ($mc_obj->addServer($server['host'], $server['port'], $server['persistent'])) {
						$failed = false;
					}
				}
			}
			if (is_object($mc_obj)) {
				$this->_memcache = $mc_obj;
			} else {
				$this->_memcache = null;
				$this->DRIVER = 'file';
			}
#		}
#		if ($this->DRIVER == 'memcache') {
			$this->_memcache_new_extension = method_exists($this->_memcache, 'getMulti');
#		}
	}
	function _is_ready() {
		return isset($this->_memcache) ? true : false;
	}
	function get($name, $ttl = 0, $params = array()) {
		if ($this->_is_ready()) {
			return null;
		}
		$result = $this->_memcache->get($name);
		if (is_string($result)) {
			$try_unpack = unserialize($result);
			if ($try_unpack || substr($result, 0, 2) == 'a:') {
				$result = $try_unpack;
			}
		}
		return $result;
	}
	function set($name, $data, $ttl = 0) {
		if ($this->_is_ready()) {
			return null;
		}
// TODO
			if (isset($this->_memcache)) {
				// Solved set() trouble with many servers.
				// http://www.php.net/manual/ru/function.memcache-set.php#84032
				if ($this->_memcache_new_extension) {
					if (!$this->_memcache->replace($key_name_ns, $data_to_put, $TTL)) {
						$result = $this->_memcache->set($key_name_ns, $data_to_put, $TTL);
					}
				} else {
					if (!$this->_memcache->replace($key_name_ns, $data_to_put, /*MEMCACHE_COMPRESSED*/ null, $TTL)) {
						$result = $this->_memcache->set($key_name_ns, $data_to_put, /*MEMCACHE_COMPRESSED*/null, $TTL);
					}
				}
			} else {
				$this->DRIVER = 'file';
			}
	}
	function del($name) {
		if ($this->_is_ready()) {
			return null;
		}
// TODO
	}
	function multi_get(array $names, $ttl = 0, $params = array()) {
		if ($this->_is_ready()) {
			return null;
		}
// TODO: optimize me for memcache, using native getMultiByKey() method
// TODO
	}
	function multi_set(array $data, $ttl = 0) {
		if ($this->_is_ready()) {
			return null;
		}
// TODO: optimize me for memcache, using native setMultiByKey() method
// TODO
	}
	function multi_del(array $names) {
		if ($this->_is_ready()) {
			return null;
		}
// TODO: optimize me for memcache
// TODO
	}
	function clean($name) {
		if ($this->_is_ready()) {
			return null;
		}
// TODO
		$result = $this->_memcache->delete($key_name_ns, 0);
	}
	function clean_all() {
		if ($this->_is_ready()) {
			return null;
		}
// TODO
	}
}
