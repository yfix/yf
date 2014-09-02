<?php

require_once __DIR__.'/class_db_real_pgsql.Test.php';

/**
 * @requires extension PDO
 * @requires extension pdo_pgsql
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class class_db_real_pdo_pgsql_test extends class_db_real_pgsql_test {
	public static function setUpBeforeClass() {
		self::$DB_DRIVER = 'pdo_pgsql';
		parent::setUpBeforeClass();
	}
}
