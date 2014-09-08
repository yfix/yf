<?php

/**
* Query builder (Active Record) for SQLite3
*/
load('db_query_builder_driver', 'framework', 'classes/db/');
class yf_db_query_builder_sqlite extends yf_db_query_builder_driver {

	/**
	* RIGHT JOIN and FULL OUTER JOIN not supported
	*/
	function right_join($table, $on) {
		return $this->join($table, $on, 'left');
	}

	// TODO: RLIKE and NOT RLIKE not supported
}
