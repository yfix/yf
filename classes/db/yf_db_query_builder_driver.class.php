<?php

/**
* Query builder (Active Record)
*/
abstract class yf_db_query_builder_driver {
	abstract function sql();
	abstract function render();
	abstract function exec($as_sql = true);
	abstract function get($use_cache = true);
	abstract function get_all($use_cache = true);
	abstract function get_2d($use_cache = true);
	abstract function get_deep_array($levels = 1, $use_cache = true);
	abstract function select();
	abstract function from();
	abstract function join($table, $on, $join_type = 'JOIN');
	abstract function left_join($table, $on);
	abstract function right_join($table, $on);
	abstract function inner_join($table, $on);
	abstract function where();
	abstract function whereid($id);
	abstract function group_by();
	abstract function having();
	abstract function order_by();
	abstract function limit($count = 10, $offset = null);
}
