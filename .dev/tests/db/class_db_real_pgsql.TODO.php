<?php

require_once __DIR__.'/db_real_abstract.php';

/**
 * @requires extension pgsql
 */
class class_db_real_pgsql_test extends db_real_abstract {
	public static function setUpBeforeClass() {
		self::$_bak['DB_DRIVER'] = self::$DB_DRIVER;
		self::$DB_DRIVER = 'pgsql';
		self::_connect();
		// These actions needed to ensure database is empty
		self::$db->query('DROP DATABASE IF EXISTS '.self::$DB_NAME);
		self::$db->query('CREATE DATABASE IF NOT EXISTS '.self::$DB_NAME);
	}
	public static function tearDownAfterClass() {
		self::$DB_DRIVER = self::$_bak['DB_DRIVER'];
	}
}
