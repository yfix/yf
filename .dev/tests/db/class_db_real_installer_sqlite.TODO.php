<?php

require_once __DIR__.'/db_real_abstract.php';

/**
 * @requires extension sqlite3
 */
class class_db_real_installer_sqlite_test extends db_real_abstract {
	public static function setUpBeforeClass() {
		self::$_bak['DB_DRIVER'] = self::$DB_DRIVER;
		self::$DB_DRIVER = 'sqlite';
	}
	public static function tearDownAfterClass() {
		self::$DB_DRIVER = self::$_bak['DB_DRIVER'];
	}
	// TODO
}