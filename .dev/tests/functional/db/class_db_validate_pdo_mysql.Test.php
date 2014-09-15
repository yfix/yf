<?php

require_once __DIR__.'/class_db_validate_mysql.Test.php';

/**
 * @requires extension PDO
 * @requires extension pdo_mysql
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class class_db_validate_pdo_mysql_test extends class_db_validate_mysql_test {
	public static function setUpBeforeClass() {
		self::$_bak['DB_DRIVER'] = self::$DB_DRIVER;
		self::$DB_DRIVER = 'pdo_mysql';
		self::_connect();
		self::utils()->truncate_database(self::db_name());
		_class('validate')->_init();
		_class('validate')->db = self::$db;
	}
}
