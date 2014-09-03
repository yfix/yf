<?php

require_once __DIR__.'/class_db_real_installer_mysql.Test.php';

/**
 * @requires extension mysqli
 */
class class_db_real_installer_mysqli_test extends class_db_real_installer_mysql_test {
	public static function setUpBeforeClass() {
		self::$_bak['DB_DRIVER'] = self::$DB_DRIVER;
		self::$DB_DRIVER = 'mysqli';
		self::_connect();
		// These actions needed to ensure database is empty
		self::$db->query('DROP DATABASE IF EXISTS '.self::$DB_NAME);
		self::$db->query('CREATE DATABASE IF NOT EXISTS '.self::$DB_NAME);
	}
	public static function tearDownAfterClass() {
		self::$DB_DRIVER = self::$_bak['DB_DRIVER'];
	}
}
