<?php

require_once dirname(__DIR__).'/yf_unit_tests_setup.php';
require_once dirname(__DIR__).'/db_setup.php';

/**
 * @requires extension mysql
 */
class class_db_mysql_real_test extends PHPUnit_Framework_TestCase {
	public static $db = null;
	public static $server_version = '';
	public static $DB_NAME = '';
	public static $DB_DRIVER = 'mysql5';
	public static $CI_SERVER = '';
	public static function setUpBeforeClass() {
		self::$DB_NAME = DB_NAME;
		$db_class = load_db_class();
		self::$db = new $db_class(self::$DB_DRIVER);
		self::$db->ALLOW_AUTO_CREATE_DB = true;
		self::$db->NO_AUTO_CONNECT = true;
		self::$db->RECONNECT_NUM_TRIES = 1;
		self::$db->CACHE_TABLE_NAMES = false;
		self::$db->ERROR_AUTO_REPAIR = false;
		self::$db->FIX_DATA_SAFE = true;
		self::$db->_init();
		$res = self::$db->connect(array(
			'host'	=> 'localhost',
			'name'	=> self::$DB_NAME,
			'user'	=> DB_USER,
			'pswd'	=> DB_PSWD,
			'prefix'=> DB_PREFIX,
			'force' => true,
		));
		self::$server_version = self::$db->get_server_version();
		if (getenv('CI') === 'true' && getenv('DRONE') === 'true') {
			self::$CI_SERVER = 'DRONE';
		}
		if (self::$CI_SERVER != 'DRONE') {
			// These actions needed to ensure database is empty
			self::$db->query('DROP DATABASE IF EXISTS '.self::$DB_NAME);
			self::$db->query('CREATE DATABASE IF NOT EXISTS '.self::$DB_NAME);
		}
	}
	public static function tearDownAfterClass() {
		if (self::$CI_SERVER == 'DRONE') { return ; }
		self::$db->utils()->drop_database(self::$DB_NAME);
	}
	protected function setUp() {
		if (self::$CI_SERVER == 'DRONE') {
			$this->markTestSkipped('Right now we skip this test, when running inside DRONE.IO.');
			return false;
		}
		if (defined('HHVM_VERSION')) {
			$this->markTestSkipped('Right now we skip this test, when running inside HHVM.');
			return ;
    	}
	}
	private static function db() {
		return self::$db;
	}
	public function test_connected() {
		$this->assertNotEmpty( self::$db );
		$this->assertTrue( is_object(self::$db) );
		$this->assertTrue( self::$db->_connected );
		$this->assertTrue( is_object(self::$db->db) );
		$this->assertTrue( is_resource(self::$db->db->db_connect_id) || is_object(self::$db->db->db_connect_id));
	}
	public function test_driver() {
		$this->assertEquals( self::$DB_DRIVER, self::$db->DB_TYPE );
		$this->assertEquals( self::$DB_DRIVER, self::db()->DB_TYPE );
		list(,$driver) = explode('_driver_', get_class(self::db()->db));
		$this->assertEquals( self::$DB_DRIVER, $driver );
	}
	public function test_basic() {
		$table = self::db()->DB_PREFIX. __FUNCTION__;
		$this->assertTrue( self::db()->query('CREATE TABLE '.self::$DB_NAME.'.'.$table.'(id INT(10)) ENGINE=InnoDB DEFAULT CHARSET=utf8') );
		$expected = array(
			'Table' => $table,
			'Create Table' => 'CREATE TABLE `'.$table.'` ('. PHP_EOL
				. '  `id` int(10) DEFAULT NULL'. PHP_EOL
				. ') ENGINE=InnoDB DEFAULT CHARSET=utf8',
		);
		$this->assertEquals( $expected, self::db()->fetch_assoc(self::db()->query('SHOW CREATE TABLE '.self::$DB_NAME.'.'.$table)) );
		$this->assertEquals( $expected, self::db()->query_fetch('SHOW CREATE TABLE '.self::$DB_NAME.'.'.$table) );
		$this->assertEquals( $expected, self::db()->get('SHOW CREATE TABLE '.self::$DB_NAME.'.'.$table) );

		$this->assertTrue( self::db()->query('INSERT INTO '.self::$DB_NAME.'.'.$table.' VALUES (1),(2),(3)') );
		$this->assertEquals( array('id' => 1), self::db()->get('SELECT * FROM '.self::$DB_NAME.'.'.$table) );
		$this->assertEquals( array(1 => array('id' => 1), 2 => array('id' => 2), 3 => array('id' => 3)), self::db()->get_all('SELECT * FROM '.self::$DB_NAME.'.'.$table) );
		$this->assertEquals( array(3 => array('id' => 3), 2 => array('id' => 2), 1 => array('id' => 1)), self::db()->get_all('SELECT * FROM '.self::$DB_NAME.'.'.$table.' ORDER BY id DESC') );
		$this->assertEmpty( self::db()->get('SELECT * FROM '.self::$DB_NAME.'.'.$table.' WHERE id > 9999') );
		$this->assertEmpty( self::db()->get_all('SELECT * FROM '.self::$DB_NAME.'.'.$table.' WHERE id > 9999') );
	}
}