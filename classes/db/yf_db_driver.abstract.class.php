<?php

/**
* YF db driver abstract class
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
abstract class yf_db_driver {
	abstract protected function __construct($server, $user, $password, $database, $persistency = false, $use_ssl = false, $port = '', $socket = '', $charset = '', $allow_auto_create_db = false);
	abstract protected function affected_rows();
	abstract protected function begin();
	abstract protected function connect();
	abstract protected function close();
	abstract protected function commit();
	abstract protected function enclose_field_name($data);
	abstract protected function enclose_field_value($data);
	abstract protected function error();
	abstract protected function fetch_array($query_id = 0);
	abstract protected function fetch_assoc($query_id = 0);
	abstract protected function fetch_row($query_id = 0);
	abstract protected function free_result($query_id = 0);
	abstract protected function get_host_info();
	abstract protected function get_server_version();
	abstract protected function insert_id();
	abstract protected function limit($count, $offset);
	abstract protected function meta_columns($table, $KEYS_NUMERIC = false, $FULL_INFO = false);
	abstract protected function meta_tables($DB_PREFIX = '');
	abstract protected function multi_query($queries = array());
	abstract protected function num_rows($query_id = 0);
	abstract protected function ping();
	abstract protected function query($query = '');
	abstract protected function real_escape_string($string);
	abstract protected function rollback();
	abstract protected function unbuffered_query($query = '');
}
