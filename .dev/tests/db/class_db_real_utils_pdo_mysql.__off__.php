<?php

require_once __DIR__.'/class_db_real_utils_mysql.Test.php';

/**
 * @requires extension PDO
 * @requires extension pdo_mysql
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class class_db_real_utils_pdo_mysql_test extends class_db_real_utils_mysql_test {
	public static function setUpBeforeClass() {
		self::$_bak['DB_DRIVER'] = self::$DB_DRIVER;
		self::$DB_DRIVER = 'pdo_mysql';
		parent::setUpBeforeClass();
	}
	public static function tearDownAfterClass() {
		self::$DB_DRIVER = self::$_bak['DB_DRIVER'];
		parent::tearDownAfterClass();
	}
}
