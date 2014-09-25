<?php

// TODO: implement migrations like in ROR, based on these methods

/**
*/
abstract class yf_db_migrator {

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
	}

	/**
	*/
	public function _init() {
	}

	/**
	* Compare and report real db structure with expected structure, stored inside sql_php, including fields, indexes, foreign keys, table options, etc
	*/
	public function compare() {
// TODO
	}

	/**
	*/
	public function compare_db() {
// TODO
	}

	/**
	*/
	public function compare_table() {
// TODO
	}

	/**
	*/
	public function compare_column() {
// TODO
	}

	/**
	*/
	public function compare_index() {
// TODO
	}

	/**
	*/
	public function compare_foreign_key() {
// TODO
	}

	/**
	* Generate migration file, based on compare() report
	*/
	public function generate_migration() {
// TODO
	}

	/**
	* Apply selected migration file to current database
	*/
	public function apply_migration() {
// TODO
	}

	/**
	* List of available migrations
	*/
	public function list_migrations() {
// TODO
	}
}
