<?php

require_once __DIR__.'/db_real_abstract.php';

/**
 * @requires extension sqlite3
 */
class class_db_real_sqlite_test extends db_real_abstract {
	public static function setUpBeforeClass() {
		self::$DB_DRIVER = 'sqlite';
		parent::setUpBeforeClass();
	}
}
