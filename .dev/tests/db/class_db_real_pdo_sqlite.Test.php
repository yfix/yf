<?php

require_once __DIR__.'/class_db_real_sqlite.Test.php';

/**
 * @requires extension PDO
 * @requires extension pdo_sqlite
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class class_db_real_pdo_sqlite_test extends class_db_real_sqlite_test {
	public static function setUpBeforeClass() {
		self::$_bak['DB_DRIVER'] = self::$DB_DRIVER;
		self::$DB_DRIVER = 'pdo_sqlite';
		self::_connect(array('name' => STORAGE_PATH. DB_NAME.'.db'));
	}
	public static function tearDownAfterClass() {
		$db_file = STORAGE_PATH. DB_NAME.'.db';
		if (file_exists($db_file)) {
			unlink($db_file);
		}
		self::$DB_DRIVER = self::$_bak['DB_DRIVER'];
	}
	public static function _need_skip_test($name) {
		return false;
	}
}
