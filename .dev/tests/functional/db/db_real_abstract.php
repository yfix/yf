<?php

require_once dirname(__DIR__).'/yf_unit_tests_setup.php';
require_once dirname(__DIR__).'/db_setup.php';

abstract class db_real_abstract extends yf_unit_tests {

	public static $db = null;
	public static $server_version = '';
	public static $DB_NAME = '';
	public static $DB_DRIVER = '';
	public static $CI_SERVER = '';
	public static $_bak = array();

	/**
	*/
	public static function _need_skip_test($name) {
		return false;
	}

	/**
	*/
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
		self::$db->NO_CACHE = true;
		self::$db->_init();
		if (false !== strpos(self::$DB_DRIVER, 'mysql')) {
			return self::_connect_mysql($params);
		} elseif (false !== strpos(self::$DB_DRIVER, 'sqlite')) {
			return self::_connect_sqlite($params);
		} elseif (false !== strpos(self::$DB_DRIVER, 'pgsql')) {
			return self::_connect_pgsql($params);
		}
	}

	/**
	*/
	public static function _connect_mysql($params = array()) {
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

	/**
	*/
	public static function _connect_sqlite($params = array()) {
		return self::_connect_mysql($params);
	}

	/**
	*/
	public static function _connect_pgsql($params = array()) {
		self::$DB_NAME = $params['name'] ?: (is_string(getenv('YF_DB_PG_NAME')) ? getenv('YF_DB_PG_NAME') : DB_NAME);
		$res = self::$db->connect(array(
			'host'	=> $params['host'] ?: (is_string(getenv('YF_DB_PG_HOST')) ? getenv('YF_DB_PG_HOST') : '127.0.0.1'),
			'name'	=> self::$DB_NAME,
			'user'	=> $params['user'] ?: (is_string(getenv('YF_DB_PG_USER')) ? getenv('YF_DB_PG_USER') : 'yf'),
			'pswd'	=> is_string($params['pswd']) ? $params['pswd'] : (is_string(getenv('YF_DB_PG_PSWD')) ? getenv('YF_DB_PG_PSWD') : DB_PSWD),
			'prefix'=> $params['prefix'] ?: (is_string(getenv('YF_DB_PG_PREFIX')) ? getenv('YF_DB_PG_PREFIX') : DB_PREFIX),
			'force' => true,
		));
		return !empty($res) ? true : false;
	}

	/**
	*/
	protected function _innodb_has_fulltext() {
		$db_server_version = self::db()->get_server_version();
		return (bool)version_compare($db_server_version, '5.6.0', '>');
	}

	/**
	*/
	protected static function db() {
		return self::$db;
	}

	/**
	*/
	protected static function utils() {
		return self::$db->utils();
	}

	/**
	*/
	protected static function qb() {
		return self::$db->query_builder();
	}

	/**
	*/
	public function test_connected() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertNotEmpty( self::$db );
		$this->assertTrue( is_object(self::$db) );
		$this->assertTrue( self::$db->_connected );
		$this->assertTrue( is_object(self::$db->db) );
		$this->assertTrue( is_resource(self::$db->db->db_connect_id) || is_object(self::$db->db->db_connect_id));
	}

	/**
	*/
	public function test_driver() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertEquals( self::$DB_DRIVER, self::$db->DB_TYPE );
		$this->assertEquals( self::$DB_DRIVER, self::db()->DB_TYPE );
		list(,$driver) = explode('_driver_', get_class(self::db()->db));
		$this->assertEquals( self::$DB_DRIVER, $driver );
	}
}