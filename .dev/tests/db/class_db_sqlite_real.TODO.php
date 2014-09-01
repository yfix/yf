<?php

require_once __DIR__.'/class_db_real.Test.php';

/**
 * @requires extension sqlite3
 */
class class_db_sqlite_real_test extends class_db_real_test {
	public static function setUpBeforeClass() {
		self::$DB_DRIVER = 'sqlite';
		parent::setUpBeforeClass();
	}
}
