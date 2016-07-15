<?php

require_once __DIR__.'/db_real_abstract.php';

/**
 */
class class_db_real_installer_sqlite_test extends db_real_abstract {
	public static function setUpBeforeClass() {
		self::$_bak['DB_DRIVER'] = self::$DB_DRIVER;
		self::$DB_DRIVER = 'sqlite';
		self::_connect(['name' => STORAGE_PATH. DB_NAME.'.db']);
	}
	public static function tearDownAfterClass() {
		$db_file = STORAGE_PATH. DB_NAME.'.db';
		if (file_exists($db_file)) {
			unlink($db_file);
		}
		self::$DB_DRIVER = self::$_bak['DB_DRIVER'];
	}
	public static function _need_skip_test($name) {
		$sqlite_version = self::db()->get_server_version();
		return extension_loaded('sqlite') && version_compare($sqlite_version, '3.7.11', '>');
	}
	// TODO
}