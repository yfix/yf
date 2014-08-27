<?php

require __DIR__.'/class_db_real.Test.php';

/**
 * @requires extension pgsql
 */
class class_db_pgsql_real_test extends class_db_real_test {
	public static function setUpBeforeClass() {
		self::$DB_DRIVER = 'pgsql';
		parent::setUpBeforeClass();
	}
}
