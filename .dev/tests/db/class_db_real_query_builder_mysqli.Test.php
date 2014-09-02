<?php

require_once __DIR__.'/class_db_real_query_builder_mysql.Test.php';

/**
 * @requires extension mysqli
 */
class class_db_real_query_builder_mysqli_test extends class_db_real_query_builder_mysql_test {
	public static function setUpBeforeClass() {
		self::$_bak['DB_DRIVER'] = self::$DB_DRIVER;
		self::$DB_DRIVER = 'mysqli';
		parent::setUpBeforeClass();
	}
	public static function tearDownAfterClass() {
		self::$DB_DRIVER = self::$_bak['DB_DRIVER'];
		parent::tearDownAfterClass();
	}
}
