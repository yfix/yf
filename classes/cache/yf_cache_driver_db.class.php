<?php

load('cache_driver', 'framework', 'classes/cache/');
class yf_cache_driver_db extends yf_cache_driver {

	public $table = 'cache';

// TODO: create table "cache" with Engine=MEMORY

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
		$data = db()->select('value')->from($table)->where('key', '=', $name)->get_2d();
		if (!$data) {
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
		return db()->replace($table, db()->es(array(
			'key'	=> $name,
			'value'	=> json_encode($data),
		)));
	}

	/**
	*/
	function del($name) {
		if (!$this->is_ready()) {
			return null;
		}
		return db()->query('DELETE FROM '.db($table).' WHERE `key`="'.db()->es($name).'"');
	}

	/**
	*/
	function flush() {
		if (!$this->is_ready()) {
			return null;
		}
		return db()->query('TRUNCATE '.db($table));
	}

	/**
	*/
	function list_keys($filter = '') {
		if (!$this->is_ready()) {
			return null;
		}
		$data = db()->from($table)->get_2d();
		if (!$data) {
			return false;
		}
		foreach ($data as &$v) {
			$v = json_decode($v, true);
		}
		return $data;
	}
}
