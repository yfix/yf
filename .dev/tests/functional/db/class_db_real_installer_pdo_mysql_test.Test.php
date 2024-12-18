<?php

require_once __DIR__ . '/class_db_real_installer_mysql_test.Test.php';

/**
 */
class class_db_real_installer_pdo_mysql_test extends class_db_real_installer_mysql_test
{
    public static function setUpBeforeClass() : void
    {
        self::$_bak['DB_DRIVER'] = self::$DB_DRIVER;
        self::$DB_DRIVER = 'pdo_mysql';
        self::_connect();
        self::utils()->truncate_database(self::db_name());
    }
    public static function tearDownAfterClass() : void
    {
        self::utils()->truncate_database(self::db_name());
        self::$DB_DRIVER = self::$_bak['DB_DRIVER'];
    }
}
