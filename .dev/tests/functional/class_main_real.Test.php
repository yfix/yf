<?php

require_once __DIR__ . '/db_real_abstract.php';

/**
 * @requires extension mysqli
 */
class class_main_real_test extends db_real_abstract
{
    public static function db_name()
    {
        return self::$DB_NAME;
    }
    public static function table_name($name)
    {
        return $name;
    }
    public static function setUpBeforeClass()
    {
        self::$_bak['DB_DRIVER'] = self::$DB_DRIVER;
        self::$DB_DRIVER = 'mysqli';
        self::_connect();
        self::utils()->truncate_database(self::db_name());
        self::$_bak['ERROR_AUTO_REPAIR'] = self::db()->ERROR_AUTO_REPAIR;
        self::db()->ERROR_AUTO_REPAIR = true;
        $GLOBALS['db'] = self::db();
    }
    public static function tearDownAfterClass()
    {
        self::utils()->truncate_database(self::db_name());
        self::$DB_DRIVER = self::$_bak['DB_DRIVER'];
        self::db()->ERROR_AUTO_REPAIR = self::$_bak['ERROR_AUTO_REPAIR'];
    }
    public function test_get_data()
    {
        $this->assertFalse((bool) self::utils()->table_exists('static_pages'));
        $this->assertEmpty(self::db()->from('static_pages')->get_all());
        $this->assertTrue((bool) self::utils()->table_exists('static_pages'));
        $data = [
            'name' => 'for_unit_tests',
            'active' => 1,
        ];
        $this->assertTrue(self::db()->insert('static_pages', $data));
        $first = self::db()->from('static_pages')->get();
        foreach ($data as $k => $v) {
            $this->assertEquals($v, $first[$k]);
        }
        $expected = [$data['name'] => $data['name']];
        $this->assertEquals($expected, main()->get_data('static_pages_names'));
    }
    public function test_plugins_restrictions()
    {
        main()->_plugins_white_list = [];
        main()->_plugins_black_list = [];
        $loaded_plugins1 = main()->_preload_plugins_list($force = true);
        $first = key($loaded_plugins1);
        main()->_plugins_black_list = [$first];
        $loaded_plugins2 = main()->_preload_plugins_list($force = true);
        $this->assertNotEquals($loaded_plugins1, $loaded_plugins2);
        main()->_plugins_white_list = [$first];
        $loaded_plugins3 = main()->_preload_plugins_list($force = true);
        $this->assertEquals($loaded_plugins1, $loaded_plugins3);
        main()->_plugins_white_list = [];
        main()->_plugins_black_list = [];
    }
    public function test_extend_class_storage()
    {
        $model_base = _class('model');
        $this->assertInternalType('object', $model_base);
        $this->assertTrue(is_a($model_base, 'yf_model'));
        $this->assertSame($model_base, _class('yf_model'));

        $model_exists = main()->_class_exists('film_model');
        if ( ! $model_exists) {
            $this->assertFalse(main()->_class_exists('film_model'));
            // unit_tests == name of the custom storage used here
            main()->_custom_class_storages = [
                'film_model' => ['unit_tests' => [__DIR__ . '/model/fixtures/']],
            ];
            $this->assertTrue(main()->_class_exists('film_model'));
        }

        $film_model = _class('film_model');
        $this->assertInternalType('object', $film_model);
        $this->assertTrue(is_a($film_model, 'film_model'));
        $this->assertTrue(is_a($film_model, 'yf_model'));
    }
    public function test_extend_methods()
    {
        //		main()->not_existing_method();
    }
}
