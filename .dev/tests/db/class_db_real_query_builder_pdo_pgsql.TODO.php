<?php

require_once __DIR__.'/class_db_real_query_builder_pgsql.Test.php';

/**
 * @requires extension PDO
 * @requires extension pdo_pgsql
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class class_db_real_query_builder_pdo_pgsql_test extends class_db_real_query_builder_pgsql_test {
	public static function setUpBeforeClass() {
		self::$_bak['DB_DRIVER'] = self::$DB_DRIVER;
		self::$DB_DRIVER = 'pdo_pgsql';
		self::_connect();
	}
	public static function tearDownAfterClass() {
		self::$DB_DRIVER = self::$_bak['DB_DRIVER'];
	}
	public function _need_skip_test($name) {
		return false;
	}
}
