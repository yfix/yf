<?php

require __DIR__.'/class_db_mysql_real.Test.php';

/**
 * @requires extension mysqli
 */
class class_db_mysqli_real_test extends class_db_mysql_real_test {
	public static function setUpBeforeClass() {
		self::$DB_DRIVER = 'mysqli';
		parent::setUpBeforeClass();
	}
}
