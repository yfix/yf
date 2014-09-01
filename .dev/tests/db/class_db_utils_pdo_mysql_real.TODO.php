<?php

require_once __DIR__.'/class_db_utils_mysql_real.Test.php';

/**
 * @requires extension PDO
 * @requires extension pdo_mysql
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class class_db_utils_pdo_mysql_real_test extends class_db_utils_mysql_real_test {
	public static function setUpBeforeClass() {
		self::$DB_DRIVER = 'pdo_mysql';
		parent::setUpBeforeClass();
	}
}
