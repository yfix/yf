<?php

require_once __DIR__.'/class_db_real_pgsql.Test.php';

/**
 * @requires extension PDO
 * @requires extension pdo_pgsql
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class class_db_real_pdo_pgsql_test extends class_db_real_pgsql_test {
	public static function setUpBeforeClass() {
		self::$_bak['DB_DRIVER'] = self::$DB_DRIVER;
		self::$DB_DRIVER = 'pdo_pgsql';
		self::_connect();
		// These actions needed to ensure database is empty
		self::$db->query('DROP DATABASE IF EXISTS '.self::$DB_NAME);
		self::$db->query('CREATE DATABASE IF NOT EXISTS '.self::$DB_NAME);
	}
	public static function tearDownAfterClass() {
		self::$DB_DRIVER = self::$_bak['DB_DRIVER'];
	}
}
