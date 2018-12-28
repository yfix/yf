<?php

require_once dirname(__DIR__) . '/yf_unit_tests_setup.php';
require_once dirname(__DIR__) . '/db_setup.php';

abstract class db_offline_abstract extends yf\tests\wrapper
{
    public static $db = null;
    public static $_er = [];
    public static $_bak = [];
    public static function _need_skip_test($name)
    {
        return false;
    }
    public static function setUpBeforeClass()
    {
        self::$db = _class('db');
        self::$_er = error_reporting();
        error_reporting(0);
    }
    public static function tearDownAfterClass()
    {
        error_reporting(self::$_er);
    }
    protected static function db()
    {
        return self::$db;
    }
    protected static function utils()
    {
        return self::$db->utils();
    }
    protected static function qb()
    {
        return self::$db->query_builder();
    }
}
