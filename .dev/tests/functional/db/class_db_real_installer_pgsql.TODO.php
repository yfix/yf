<?php

require_once __DIR__ . '/db_real_abstract.php';

/**
 * @requires extension pgsql
 */
class class_db_real_installer_pgsql_test extends db_real_abstract
{
    public static function setUpBeforeClass() : void
    {
        self::$_bak['DB_DRIVER'] = self::$DB_DRIVER;
        self::$DB_DRIVER = 'pgsql';
        self::_connect();
        self::utils()->truncate_database(self::db_name());
    }
    public static function tearDownAfterClass() : void
    {
        self::utils()->truncate_database(self::db_name());
        self::$DB_DRIVER = self::$_bak['DB_DRIVER'];
    }
    // TODO
}
