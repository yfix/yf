<?php

require_once __DIR__.'/db_real_abstract.php';

/**
 * @requires extension mysql
 */
class class_main_real_test extends db_real_abstract {
	public static function setUpBeforeClass() {
		self::$_bak['DB_DRIVER'] = self::$DB_DRIVER;
		self::$DB_DRIVER = 'mysql5';
		self::_connect();
		self::utils()->truncate_database(self::db_name());
		self::$_bak['ERROR_AUTO_REPAIR'] = self::db()->ERROR_AUTO_REPAIR;
		self::db()->ERROR_AUTO_REPAIR = true;
		$GLOBALS['db'] = self::db();
	}
	public static function tearDownAfterClass() {
		self::utils()->truncate_database(self::db_name());
		self::$DB_DRIVER = self::$_bak['DB_DRIVER'];
		self::db()->ERROR_AUTO_REPAIR = self::$_bak['ERROR_AUTO_REPAIR'];
	}
	public static function db_name() {
		return self::$DB_NAME;
	}
	public static function table_name($name) {
		return $name;
	}
	public function test_get_data() {
		$this->assertFalse( (bool)self::utils()->table_exists('static_pages') );
		$this->assertEmpty( self::db()->from('static_pages')->get_all() );
		$this->assertTrue( (bool)self::utils()->table_exists('static_pages') );
		$data = array(
			'name'		=> 'for_unit_tests',
			'active'	=> 1,
		);
		$this->assertTrue( self::db()->insert('static_pages', $data) );
		$first = self::db()->from('static_pages')->get();
		foreach ($data as $k => $v) {
			$this->assertEquals($v, $first[$k]);
		}
		$expected = array($data['name'] => $data['name']);
		$this->assertEquals($expected, main()->get_data('static_pages_names'));
	}
	public function test_plugins_restrictions() {
		main()->_plugins_white_list = array();
		main()->_plugins_black_list = array();
		$loaded_plugins1 = main()->_preload_plugins_list($force = true);
		$first = key($loaded_plugins1);
		main()->_plugins_black_list = array($first);
		$loaded_plugins2 = main()->_preload_plugins_list($force = true);
		$this->assertNotEquals($loaded_plugins1, $loaded_plugins2);
		main()->_plugins_white_list = array($first);
		$loaded_plugins3 = main()->_preload_plugins_list($force = true);
		$this->assertEquals($loaded_plugins1, $loaded_plugins3);
		main()->_plugins_white_list = array();
		main()->_plugins_black_list = array();
	}
	public function test_extend_class_storage() {
		$model_base = _class('model');
		$this->assertTrue( is_object($model_base) );
		$this->assertTrue( is_a($model_base, 'yf_model') );
		$this->assertSame( $model_base, _class('yf_model') );

		$this->assertFalse( main()->_class_exists('film_model') );

		// unit_tests == name of the custom storage used here
		main()->_custom_class_storages = array(
			'film_model' => array('unit_tests' => array(__DIR__.'/model/fixtures/')),
		);

		$this->assertTrue( main()->_class_exists('film_model') );

		$film_model = _class('film_model');
		$this->assertTrue( is_object($film_model) );
		$this->assertTrue( is_a($film_model, 'film_model') );
		$this->assertTrue( is_a($film_model, 'yf_model') );
	}
	public function test_extend_methods() {
#		main()->not_existing_method();
	}
}
