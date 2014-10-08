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
}
