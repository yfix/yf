<?php

require_once dirname(__DIR__).'/yf_unit_tests_setup.php';
require_once dirname(__DIR__).'/db_setup.php';

/**
 * @requires extension mysql
 */
class class_db_installer_mysql_real_test extends PHPUnit_Framework_TestCase {
	public static $db = null;
	public static $server_version = '';
	public static $DB_NAME = '';
	public static $DB_DRIVER = 'mysql5';
	public static $CI_SERVER = '';
	public static function setUpBeforeClass() {
		self::_connect();
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
	public function _connect() {
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
		return !empty($res) ? true : false;
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

	private $data_good = array(
		'fields' => array(
			'id' => array(
				'type'		=> 'varchar',
				'length'	=> '32',
				'attrib'	=> NULL,
				'not_null'	=> 1,
				'default'	=> '',
				'auto_inc'	=> 0,
			),
			'user_id' => array(
				'type'		=> 'int',
				'length'	=> '10',
				'attrib'	=> NULL,
				'not_null'	=> 1,
				'default'	=> '0',
				'auto_inc'	=> 0,
			),
			'user_group' => array(
				'type'		=> 'int',
				'length'	=> '10',
				'attrib'	=> NULL,
				'not_null'	=> 1,
				'default'	=> '0',
				'auto_inc'	=> 0,
			),
			'time' => array(
				'type'		=> 'int',
				'length'	=> '10',
				'attrib'	=> NULL,
				'not_null'	=> 1,
				'default'	=> '0',
				'auto_inc'	=> 0,
			),
			'type' => array(
				'type'		=> 'enum',
				'length'	=> '\'user\',\'admin\'',
				'attrib'	=> NULL,
				'not_null'	=> 1,
				'default'	=> '',
				'auto_inc'	=> 0,
			),
			'ip' => array(
				'type'		=> 'varchar',
				'length'	=> '16',
				'attrib'	=> NULL,
				'not_null'	=> 1,
				'default'	=> '',
				'auto_inc'	=> 0,
			),
			'user_agent' => array(
				'type'		=> 'varchar',
				'length'	=> '255',
				'attrib'	=> NULL,
				'not_null'	=> 1,
				'default'	=> '',
				'auto_inc'	=> 0,
			),
			'query_string' => array(
				'type'		=> 'varchar',
				'length'	=> '255',
				'attrib'	=> NULL,
				'not_null'	=> 1,
				'default'	=> '',
				'auto_inc'	=> 0,
			),
			'site_id' => array(
				'type'		=> 'tinyint',
				'length'	=> '3',
				'attrib'	=> NULL,
				'not_null'	=> 1,
				'default'	=> '0',
				'auto_inc'	=> 0,
			),
		),
		'keys'	=> array(
			'id' => array(
				'fields'=> array('id'),
				'type'	=> 'primary',
			),
			'user_id' => array(
				'fields'=> array('user_id'),
				'type'	=> 'key',
			),
		),
	);

	public function test_sql_into_array_empty() {
		$this->assertEquals( array(), _class('db_installer_mysql', 'classes/db/')->_db_table_struct_into_array('') );
	}
	public function test_sql_into_array() {
		$sql = '
			`id` varchar(32) NOT NULL default \'\',
			`user_id` int(10) unsigned NOT NULL,
			`user_group` int(10) unsigned NOT NULL,
			`time` int(10) unsigned NOT NULL default \'0\',
			`type` enum(\'user\',\'admin\') NOT NULL,
			`ip` varchar(16) NOT NULL,
			`user_agent` varchar(255) NOT NULL,
			`query_string` varchar(255) NOT NULL,
			`site_id` tinyint(3) unsigned NOT NULL,
			PRIMARY KEY	(`id`),
			KEY `user_id` (`user_id`)
			/** ENGINE=MEMORY **/
		';
		$this->assertEquals( $this->data_good, _class('db_installer_mysql', 'classes/db/')->_db_table_struct_into_array($sql) );

		$sql = '
			`id` varchar(32) NOT NULL default \'\',
			`user_id` int(10) unsigned NOT NULL,
			`user_group` int(10) unsigned NOT NULL,
			`time` int(10) unsigned NOT NULL default \'0\',
			`type` enum(\'user\',\'admin\') NOT NULL,
			`ip` varchar(16) NOT NULL,
			`user_agent` varchar(255) NOT NULL,
			`query_string` varchar(255) NOT NULL,
			`site_id` tinyint(3) unsigned NOT NULL,
			PRIMARY KEY	(`id`),
			KEY `user_id` (`user_id`)
			/** ENGINE=MEMORY **/
		';

		$this->assertEquals( $this->data_good, _class('db_installer_mysql', 'classes/db/')->_db_table_struct_into_array($sql) );
		$sql = '
			`id` varchar(32) NOT NULL default \'\',
			`user_id` int(10) unsigned NOT NULL,
			`user_group` int(10) unsigned NOT NULL,
			`time` int(10) unsigned NOT NULL default \'0\',
			`type` enum(\'user\',\'admin\') NOT NULL,
			`ip` varchar(16) NOT NULL,
			`user_agent` varchar(255) NOT NULL,
			`query_string` varchar(255) NOT NULL,
			`site_id` tinyint(3) unsigned NOT NULL,
			PRIMARY KEY	(`id`),
			KEY `user_id` (`user_id`)
		';
		$this->assertEquals( $this->data_good, _class('db_installer_mysql', 'classes/db/')->_db_table_struct_into_array($sql) );
/*
		$sql = '`id` varchar(32) NOT NULL default \'\', `user_id` int(10) unsigned NOT NULL, `user_group` int(10) unsigned NOT NULL, `time` int(10) unsigned NOT NULL default \'0\',
			`type` enum(\'user\',\'admin\') NOT NULL, `ip` varchar(16) NOT NULL, `user_agent` varchar(255) NOT NULL, `query_string` varchar(255) NOT NULL,
			`site_id` tinyint(3) unsigned NOT NULL, PRIMARY KEY	(`id`), KEY `user_id` (`user_id`)
		';
		$this->assertEquals( $this->data_good, _class('db_installer_mysql', 'classes/db/')->_db_table_struct_into_array($sql) );
*/
	}
	public function test_sql_into_array_multi_key() {
		$data_good = array(
			'fields' => array(
				'key' => array('type' => 'varchar', 'length' => '32', 'attrib' => NULL, 'not_null'	=> 1, 'default' => '', 'auto_inc' => 0),
			),
			'keys'	=> array(
				'id' => array('fields' => array('key', 'name'), 'type' => 'primary'),
			),
		);
		$sql = '
			`key` varchar(32) NOT NULL default \'\',
			`name` varchar(32) NOT NULL default \'\',
			PRIMARY KEY	(`key`,`name`),
		';
#		$this->assertEquals( $data, _class('db_installer_mysql', 'classes/db/')->_db_table_struct_into_array($sql) );
	}
	public function test_sql_into_array_foreign_key() {
// TODO
	}
	public function test_sql_into_array_partition() {
// TODO
	}
	public function test_sql_into_array_collate() {
// TODO
	}
	public function test_mysql_create_table_from_array() {
// TODO
	}
	public function test_mysql_alter_table_from_array() {
// TODO
	}

}