<?php

require_once __DIR__.'/class_db_real_installer_pgsql.Test.php';

/**
 * @requires extension PDO
 * @requires extension pdo_pgsql
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class class_db_real_installer_pdo_pgsql_test extends class_db_real_installer_pgsql_test {
	public static function setUpBeforeClass() {
		self::$_bak['DB_DRIVER'] = self::$DB_DRIVER;
		self::$DB_DRIVER = 'pdo_pgsql';
	}
	public static function tearDownAfterClass() {
		self::$DB_DRIVER = self::$_bak['DB_DRIVER'];
	}
}
