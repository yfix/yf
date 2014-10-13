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
/*
		$body[] = '<p>The database currently contains <b>'. model('film')->count(). '</b> movies.</p>';
		$body[] = '<p>The title of the first movie in the database is: <b>'. model('film')->first()->title. '</b></p>';

		$film = model('film')->first(array('title' => 'RANDOM GO'));
		$body[] = '<p><b>'. $film->title. '</b>\'s description is: '.PHP_EOL. '<b>'. $film->description. '</b></p>';

		$body[] = '<p>Films with a title that starts with a <b>T</b> and a rental rate of <b>2.99</b>:';
		$films = model('film')->find(array('title' => 'T*', 'rental_rate' => '2.99'));
		$body[] = '</p><ul>';
		foreach ($films as $film) {
			$body[] = '<li>'.$film->title.'</li>';
		}
		$body[] = '</ul>';

		$body[] = '<p>There are <b>'. model('film')->count(array('rental_rate' => '4.99')). '</b> movie(s) with a rental rate of <b>4.99</b></p>';
*/
/*
		return model('admin')->table(array(
				'filter_params' => array(
					'login'	=> 'like',
					'email'	=> 'like',
				),
			))
*/
/*
		return model('admin')->form($id, $a, array('autocomplete' => 'off'))
			->login()
			->email()
			->password()
*/
/*
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
*/
	}
}
