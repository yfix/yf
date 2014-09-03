<?php

require_once __DIR__.'/class_db_real_query_builder_mysql.Test.php';

/**
 * @requires extension pgsql
 */
class class_db_real_query_builder_pgsql_test extends class_db_real_query_builder_mysql_test {
	public static function setUpBeforeClass() {
		self::$_bak['DB_DRIVER'] = self::$DB_DRIVER;
		self::$DB_DRIVER = 'pgsql';
		self::_connect();
	}
	public static function tearDownAfterClass() {
		self::$DB_DRIVER = self::$_bak['DB_DRIVER'];
	}
	public function _need_skip_test($name) {
		return false;
	}
}
