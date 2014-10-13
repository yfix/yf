<?php

require_once dirname(__DIR__).'/db_real_abstract.php';

/**
 * @requires extension mysql
 */
class class_model_real_test extends db_real_abstract {
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
	public function test_basic() {
		$model_base = _class('model');
		$this->assertTrue( is_object($model_base) );
		$this->assertTrue( is_a($model_base, 'yf_model') );
		$this->assertSame( $model_base, _class('yf_model') );

		$this->assertFalse( main()->_class_exists('film_model') );
		// unit_tests == name of the custom storage used here
		main()->_custom_class_storages = array(
			'film_model' => array('unit_tests' => array(__DIR__.'/fixtures/')),
		);
		$this->assertTrue( main()->_class_exists('film_model') );

		$film_model = _class('film_model');
		$this->assertTrue( is_object($film_model) );
		$this->assertTrue( is_a($film_model, 'film_model') );
		$this->assertTrue( is_a($film_model, 'yf_model') );

		$this->utils()->drop_table('film');
		$this->assertFalse( (bool)$this->utils()->table_exists('film') );
	}
}
