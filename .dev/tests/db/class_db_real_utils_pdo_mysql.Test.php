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
		self::_connect();
		// These actions needed to ensure database is empty
		self::$db->query('DROP DATABASE IF EXISTS '.self::$DB_NAME);
	}
	public static function tearDownAfterClass() {
		self::$DB_DRIVER = self::$_bak['DB_DRIVER'];
	}
	public function _need_skip_test($name) {
		if (substr($name, 0, 5) !== 'test_') {
			return false;
		}
		$short = substr($name, 5);
		return in_array($short, array(
/*
			'database_info',
			'alter_database',
			'check_table',
			'procedure_exists',
			'procedure_info',
			'drop_procedure',
			'create_procedure',
			'function_exists',
			'function_info',
			'drop_function',
			'create_function',
			'list_triggers',
			'trigger_exists',
			'trigger_info',
			'drop_trigger',
			'create_trigger',
			'list_events',
			'event_exists',
			'event_info',
			'drop_event',
			'create_event',
*/
		));
	}
}
