<?php

/**
* YF db driver abstract class
*/
abstract class yf_db_driver {

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
	}
	abstract protected function __construct(array $params);
	abstract protected function connect();
	abstract protected function close();
	abstract protected function query($query);
	abstract protected function begin();
	abstract protected function commit();
	abstract protected function rollback();
	abstract protected function error();
	abstract protected function fetch_array($query_id);
	abstract protected function fetch_assoc($query_id);
	abstract protected function fetch_row($query_id);
	abstract protected function fetch_object($query_id);
	abstract protected function free_result($query_id);
	abstract protected function affected_rows($query_id);
	abstract protected function insert_id($query_id);
	abstract protected function num_rows($query_id);
	abstract protected function real_escape_string($string);
	abstract protected function escape_key($data);
	abstract protected function escape_val($data);
	abstract protected function limit($count, $offset);
}
