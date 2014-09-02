<?php

require_once __DIR__.'/class_db_real_query_builder_mysql.Test.php';

/**
 * @requires extension sqlite3
 */
class class_db_real_query_builder_sqlite_test extends class_db_real_query_builder_mysql_test {
	public static function setUpBeforeClass() {
		self::$DB_DRIVER = 'sqlite';
		parent::setUpBeforeClass();
	}
}
