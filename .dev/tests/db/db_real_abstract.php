<?php

require_once dirname(__DIR__).'/yf_unit_tests_setup.php';
require_once dirname(__DIR__).'/db_setup.php';

abstract class db_real_abstract extends PHPUnit_Framework_TestCase {
	public static $db = null;
	public static $server_version = '';
	public static $DB_NAME = '';
	public static $DB_DRIVER = '';
	public static $CI_SERVER = '';
	public static $_bak = array();
#	public static function setUpBeforeClass() {
#		self::_connect();
#		self::$server_version = self::$db->get_server_version();
#		if (getenv('CI') === 'true' && getenv('DRONE') === 'true') {
#			self::$CI_SERVER = 'DRONE';
#		}
#		if (self::$CI_SERVER != 'DRONE') {
#			// These actions needed to ensure database is empty
#			self::$db->query('DROP DATABASE IF EXISTS '.self::$DB_NAME);
#			self::$db->query('CREATE DATABASE IF NOT EXISTS '.self::$DB_NAME);
#		}
#	}
#	public static function tearDownAfterClass() {
#		if (self::$CI_SERVER == 'DRONE') { return ; }
#		self::$db->utils()->drop_database(self::$DB_NAME);
#	}
#	protected function setUp() {
#		if (self::$CI_SERVER == 'DRONE') {
#			$this->markTestSkipped('Right now we skip this test, when running inside DRONE.IO.');
#			return false;
#		}
#		if (defined('HHVM_VERSION')) {
#			$this->markTestSkipped('Right now we skip this test, when running inside HHVM.');
#			return ;
#    	}
#	}
	public function _need_skip_test($name) {
		return false;
	}
	public static function _connect($params = array()) {
		self::$DB_NAME = $params['name'] ?: DB_NAME;
		if ($params['driver']) {
			self::$DB_DRIVER = $params['driver'];
		}
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
			'host'	=> $params['host'] ?: '127.0.0.1',
			'name'	=> self::$DB_NAME,
			'user'	=> $params['user'] ?: DB_USER,
			'pswd'	=> $params['pswd'] ?: DB_PSWD,
			'prefix'=> $params['prefix'] ?: DB_PREFIX,
			'force' => true,
		));
		return !empty($res) ? true : false;
	}
	protected static function db() {
		return self::$db;
	}
	protected static function utils() {
		return self::$db->utils();
	}
	protected static function qb() {
		return self::$db->query_builder();
	}
	public function test_connected() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertNotEmpty( self::$db );
		$this->assertTrue( is_object(self::$db) );
		$this->assertTrue( self::$db->_connected );
		$this->assertTrue( is_object(self::$db->db) );
		$this->assertTrue( is_resource(self::$db->db->db_connect_id) || is_object(self::$db->db->db_connect_id));
	}
	public function test_driver() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertEquals( self::$DB_DRIVER, self::$db->DB_TYPE );
		$this->assertEquals( self::$DB_DRIVER, self::db()->DB_TYPE );
		list(,$driver) = explode('_driver_', get_class(self::db()->db));
		$this->assertEquals( self::$DB_DRIVER, $driver );
	}
}