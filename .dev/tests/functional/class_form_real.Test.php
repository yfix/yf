<?php

require_once __DIR__.'/db_real_abstract.php';

/**
 * @requires extension mysql
 */
class class_form_real_test extends db_real_abstract {
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
	public function test_insert_if_ok() {
		$this->assertFalse( (bool)self::utils()->table_exists('static_pages') );
		$this->assertEmpty( self::db()->from('static_pages')->get() );
		$this->assertTrue( (bool)self::utils()->table_exists('static_pages') );
		$this->assertEmpty( self::db()->from('static_pages')->get() );

		$this->assertFalse( (bool)self::utils()->column_info_item('static_pages', 'text', 'nullable') );
		$this->assertTrue( (bool)self::utils()->alter_column('static_pages', 'text', array('nullable' => true)) );
		$this->assertTrue( (bool)self::utils()->column_info_item('static_pages', 'text', 'nullable') );

		$_SERVER['REQUEST_METHOD'] = 'POST';

		$_POST = array(
			'name'		=> 'for_unit_tests',
			'active'	=> '1',
		);
		$this->assertTrue( main()->is_post() );

		form($_POST)
			->text('name')
			->text('text')
			->active_box()
			->validate(array('name' => 'trim|required'))
			->insert_if_ok('static_pages', array('name','text'))
			->render(); // !! Important to call it to run validate() and insert_if_ok() processing

		$first = self::db()->from('static_pages')->get();

		$names = array('name', 'text', 'active');
		foreach ($names as $name) {
			$this->assertSame($_POST[$name], $first[$name]);
		}

		$this->assertTrue( (bool)self::utils()->truncate_table('static_pages') );
		$this->assertEmpty( self::db()->from('static_pages')->get() );

		form($_POST)
			->text('name')
			->text('text')
			->active_box()
			->validate(array('name' => 'trim|required'))
			->insert_if_ok('static_pages', array('name','text'), array('text' => null))
			->render(); // !! Important to call it to run validate() and insert_if_ok() processing

		$first = self::db()->from('static_pages')->get();

		$names = array('name', 'text', 'active');
		foreach ($names as $name) {
			$this->assertSame($_POST[$name], $first[$name]);
		}

		$_SERVER['REQUEST_METHOD'] = null;
		$_POST = array();
	}
}
