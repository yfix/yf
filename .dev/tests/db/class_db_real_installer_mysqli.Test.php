<?php

require_once __DIR__.'/class_db_real_installer_mysql.Test.php';

/**
 * @requires extension mysqli
 */
class class_db_real_installer_mysqli_test extends class_db_real_installer_mysql_test {
	public static function setUpBeforeClass() {
		self::$DB_DRIVER = 'mysqli';
		parent::setUpBeforeClass();
	}
}
