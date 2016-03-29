<?php

require_once __DIR__.'/db_real_abstract.php';

/**
 * @requires extension mysqli
 */
class class_form_real_test extends db_real_abstract {
	public static function setUpBeforeClass() {
		self::$_bak['DB_DRIVER'] = self::$DB_DRIVER;
		self::$DB_DRIVER = 'mysqli';
		self::_connect();
		self::utils()->truncate_database(self::db_name());
		self::$_bak['ERROR_AUTO_REPAIR'] = self::db()->ERROR_AUTO_REPAIR;
		self::db()->ERROR_AUTO_REPAIR = true;
		$GLOBALS['db'] = self::db();
		$GLOBALS['CONF']['form2']['CONF_CSRF_PROTECTION'] = false;
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
		$this->assertTrue( (bool)self::utils()->alter_column('static_pages', 'text', ['nullable' => true]) );
		$this->assertTrue( (bool)self::utils()->column_info_item('static_pages', 'text', 'nullable') );

		$_SERVER['REQUEST_METHOD'] = 'POST';

		$_POST = [
			'name'		=> 'for_unit_tests',
			'active'	=> '1',
		];
		$this->assertTrue( main()->is_post() );

		form($_POST)
			->text('name')
			->text('text')
			->active_box()
			->validate(['name' => 'trim|required'])
			->insert_if_ok('static_pages', ['name','text'])
			->render(); // !! Important to call it to run validate() and insert_if_ok() processing

		$first = self::db()->from('static_pages')->get();

		$names = ['name', 'text', 'active'];
		foreach ($names as $name) {
			$this->assertSame($_POST[$name], $first[$name]);
		}

		$this->assertTrue( (bool)self::utils()->truncate_table('static_pages') );
		$this->assertEmpty( self::db()->from('static_pages')->get() );

		form($_POST)
			->text('name')
			->text('text')
			->active_box()
			->validate(['name' => 'trim|required'])
			->insert_if_ok('static_pages', ['name','text'], ['text' => null])
			->render(); // !! Important to call it to run validate() and insert_if_ok() processing

		$first = self::db()->from('static_pages')->get();

		$names = ['name', 'text', 'active'];
		foreach ($names as $name) {
			$this->assertSame($_POST[$name], $first[$name]);
		}

		$_SERVER['REQUEST_METHOD'] = null;
		$_POST = [];
	}
	public function test_validate_custom_error() {
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$params = ['do_not_remove_errors' => 1];

		$_POST = [
			'name'		=> 'for_unit_tests',
			'active'	=> '1',
		];
		$this->assertTrue( main()->is_post() );

		common()->USER_ERRORS = [];
		$this->assertEmpty( common()->USER_ERRORS );
		$custom_error = 'Such field as "%field" is empty...';
		form($_POST, $params)
			->text('text')
			->validate(['text' => 'trim|required'])
			->render(); // !! Important to call it to run validate() and insert_if_ok() processing
		$cur_error = common()->USER_ERRORS['text'];
		$this->assertNotEmpty( $cur_error );
		$this->assertNotEquals( $custom_error, $cur_error );

		common()->USER_ERRORS = [];
		$this->assertEmpty( common()->USER_ERRORS );
		form($_POST, $params)
			->text('text', ['validate_error' => $custom_error])
			->validate(['text' => 'trim|required'])
			->render(); // !! Important to call it to run validate() and insert_if_ok() processing
		$this->assertEquals( str_replace('%field', 'Text', $custom_error), common()->USER_ERRORS['text'] );

		common()->USER_ERRORS = [];
		$this->assertEmpty( common()->USER_ERRORS );
		$_POST['text'] = 'something';
		$custom_error = ['integer' => 'Custom error: "%field" should be of type integer'];
		form($_POST, $params)
			->text('text', ['validate_error' => $custom_error])
			->validate(['text' => 'trim|required|integer'])
			->render(); // !! Important to call it to run validate() and insert_if_ok() processing
		$this->assertEquals( str_replace('%field', 'Text', $custom_error['integer']), common()->USER_ERRORS['text'] );

		common()->USER_ERRORS = [];
		$this->assertEmpty( common()->USER_ERRORS );
		$_POST['text'] = '1234';
		form($_POST, $params)
			->text('text', ['validate_error' => $custom_error])
			->validate(['text' => 'trim|required|integer'])
			->render(); // !! Important to call it to run validate() and insert_if_ok() processing
		$this->assertEmpty( common()->USER_ERRORS );

		common()->USER_ERRORS = [];
		$_SERVER['REQUEST_METHOD'] = null;
		$_POST = [];
	}
}
