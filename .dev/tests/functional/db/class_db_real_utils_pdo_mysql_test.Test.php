<?php

require_once __DIR__ . '/class_db_real_utils_mysql_test.Test.php';

/**
 */
class class_db_real_utils_pdo_mysql_test extends class_db_real_utils_mysql_test
{
    public static function setUpBeforeClass() : void
    {
        self::$_bak['DB_DRIVER'] = self::$DB_DRIVER;
        self::$DB_DRIVER = 'pdo_mysql';
        self::_connect();
        // These actions needed to ensure database is empty
        if ( ! $_ENV['TRAVIS']) {
            self::$db->query('DROP DATABASE IF EXISTS ' . self::$DB_NAME);
        }
    }
    public static function tearDownAfterClass() : void
    {
        self::$DB_DRIVER = self::$_bak['DB_DRIVER'];
    }
    //	public static function _need_skip_test($name) {
//		if (substr($name, 0, 5) !== 'test_') {
//			return false;
//		}
//		$short = substr($name, 5);
//		return in_array($short, array(
//		));
//	}
}
