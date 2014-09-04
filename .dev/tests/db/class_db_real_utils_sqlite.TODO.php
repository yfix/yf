<?php

require_once __DIR__.'/class_db_real_utils_mysql.Test.php';

/**
 * @requires extension sqlite3
 */
class class_db_real_utils_sqlite_test extends class_db_real_utils_mysql_test {
	public static function setUpBeforeClass() {
		self::$_bak['DB_DRIVER'] = self::$DB_DRIVER;
		self::$DB_DRIVER = 'sqlite';
		self::_connect(array('name' => STORAGE_PATH. DB_NAME.'.db'));
	}
	public static function tearDownAfterClass() {
		$db_file = STORAGE_PATH. DB_NAME.'.db';
		if (file_exists($db_file)) {
			unlink($db_file);
		}
		self::$DB_DRIVER = self::$_bak['DB_DRIVER'];
	}
	public function _need_skip_test($name) {
		if (substr($name, 0, 5) !== 'test_') {
			return false;
		}
		$short = substr($name, 5);
		return in_array($short, array(
			'list_collations',
			'list_charsets',
			'list_databases',
			'database_info',
			'database_exists',
			'create_database',
			'drop_database',
			'alter_database',
			'rename_database',
		));
	}
	public function table_name($name) {
		return $name;
	}
}
