<?php

require_once __DIR__.'/db_real_abstract.php';

/**
 * @requires extension pgsql
 */
class class_db_real_pgsql_test extends db_real_abstract {
	public static function setUpBeforeClass() {
		self::$DB_DRIVER = 'pgsql';
		parent::setUpBeforeClass();
	}
}
