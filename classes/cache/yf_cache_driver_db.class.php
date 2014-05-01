<?php

load('cache_driver', 'framework', 'classes/cache/');
class yf_cache_driver_db extends yf_cache_driver {

	public $table = 'cache';

	/**
	*/
	function is_ready() {
		return is_object(db()) && db()->_connected;
	}

	/**
	*/
	function get($name, $ttl = 0, $params = array()) {
		if (!$this->is_ready()) {
			return null;
		}
		$ttl = intval($ttl ?: $this->_parent->TTL);
		$data = db()->select('value')->from($this->table)->where('key', '=', $name)->get_2d();
		if (!$data || $data['time'] < (time() - $ttl)) {
			return false;
		}
		foreach ($data as &$v) {
			$v = json_decode($v, true);
		}
		return $data;
	}

	/**
	*/
	function set($name, $data, $ttl = 0) {
		if (!$this->is_ready()) {
			return null;
		}
		return db()->replace($this->table, db()->es(array(
			'key'	=> $name,
			'value'	=> json_encode($data),
			'time'	=> time(),
		)));
	}

	/**
	*/
	function del($name) {
		if (!$this->is_ready()) {
			return null;
		}
		return db()->query('DELETE FROM '.db($this->table).' WHERE `key`="'.db()->es($name).'"');
	}

	/**
	*/
	function flush() {
		if (!$this->is_ready()) {
			return null;
		}
		return db()->query('TRUNCATE '.db($this->table));
	}

	/**
	*/
	function list_keys() {
		if (!$this->is_ready()) {
			return null;
		}
		$data = db()->from($this->table)->get_2d();
		if (!$data) {
			return false;
		}
		foreach ($data as &$v) {
			$v = json_decode($v, true);
		}
		return $data;
	}
}
