<?php

/**
* YF Cache driver abstract
*/
abstract class yf_cache_driver {
	abstract protected function is_ready();
	abstract protected function get($name, $ttl = 0, $params = array());
	abstract protected function set($name, $data, $ttl = 0);
	abstract protected function del($name);
	abstract protected function flush();
	abstract protected function stats();
}
