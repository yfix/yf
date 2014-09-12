<?php

require_once __DIR__.'/class_db_validate_mysql.Test.php';

/**
 * @requires extension mysqli
 */
class class_db_validate_mysqli_test extends class_db_validate_mysql_test {
	public static function setUpBeforeClass() {
		self::$_bak['DB_DRIVER'] = self::$DB_DRIVER;
		self::$DB_DRIVER = 'mysqli';
		self::_connect();
		self::utils()->truncate_database(self::db_name());
		_class('validate')->_init();
		_class('validate')->db = self::$db;
	}
}
