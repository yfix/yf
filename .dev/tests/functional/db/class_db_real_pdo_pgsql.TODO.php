<?php

require_once __DIR__ . '/class_db_real_pgsql_test.Test.php';

/**
 * @requires extension PDO
 * @requires extension pdo_pgsql
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class class_db_real_pdo_pgsql_test extends class_db_real_pgsql_test
{
    public static function setUpBeforeClass() : void
    {
        self::$_bak['DB_DRIVER'] = self::$DB_DRIVER;
        self::$DB_DRIVER = 'pdo_pgsql';
        self::_connect();
        self::utils()->truncate_database(self::db_name());
    }
    public static function tearDownAfterClass() : void
    {
        self::utils()->truncate_database(self::db_name());
        self::$DB_DRIVER = self::$_bak['DB_DRIVER'];
    }
}
