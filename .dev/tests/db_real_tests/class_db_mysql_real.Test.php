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
	public function test_disconnect_connect() {
		$this->assertTrue( self::db()->close() );
		$this->assertFalse( self::$db->_connected );
		$this->assertFalse( self::$db->_tried_to_connect );
		$this->assertNull( self::$db->db );
		$this->assertTrue( self::_connect() );
		$this->assertTrue( self::$db->_connected );
		$this->assertTrue( self::$db->_tried_to_connect );
		$this->assertTrue( is_object(self::$db->db) );
		$this->assertTrue( !empty(self::$db->db->db_connect_id) );
	}
	public function test_basic_queries_and_fetching() {
		$table = self::db()->DB_PREFIX. __FUNCTION__;
		$this->assertTrue( self::db()->query('CREATE TABLE '.self::$DB_NAME.'.'.$table.'(id INT(10) AUTO_INCREMENT, PRIMARY KEY(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8') );
		$expected = array(
			'Table' => $table,
			'Create Table' => 'CREATE TABLE `'.$table.'` ('. PHP_EOL
				. '  `id` int(10) NOT NULL AUTO_INCREMENT,'. PHP_EOL
				. '  PRIMARY KEY (`id`)'. PHP_EOL
				. ') ENGINE=InnoDB DEFAULT CHARSET=utf8',
		);
		$this->assertEquals( $expected, self::db()->fetch_assoc(self::db()->query('SHOW CREATE TABLE '.self::$DB_NAME.'.'.$table)) );
		$this->assertEquals( $expected, self::db()->fetch_assoc(self::db()->unbuffered_query('SHOW CREATE TABLE '.self::$DB_NAME.'.'.$table)) );
		$this->assertEquals( $expected, self::db()->query_fetch('SHOW CREATE TABLE '.self::$DB_NAME.'.'.$table) );
		$this->assertEquals( $expected, self::db()->get('SHOW CREATE TABLE '.self::$DB_NAME.'.'.$table) );

		$this->assertTrue( self::db()->query('INSERT INTO '.self::$DB_NAME.'.'.$table.' VALUES (1),(2),(3)') );
		$this->assertEquals( 3, self::db()->affected_rows() );
		$this->assertEquals( 3, self::db()->insert_id() );
		$this->assertEquals( array('id' => 1), self::db()->get('SELECT * FROM '.self::$DB_NAME.'.'.$table) );
		$this->assertEquals( array(1 => array('id' => 1), 2 => array('id' => 2), 3 => array('id' => 3)), self::db()->get_all('SELECT * FROM '.self::$DB_NAME.'.'.$table) );
		$this->assertEquals( array(3 => array('id' => 3), 2 => array('id' => 2), 1 => array('id' => 1)), self::db()->get_all('SELECT * FROM '.self::$DB_NAME.'.'.$table.' ORDER BY id DESC') );
		$this->assertEmpty( self::db()->get('SELECT * FROM '.self::$DB_NAME.'.'.$table.' WHERE id > 9999') );
		$this->assertEmpty( self::db()->get_all('SELECT * FROM '.self::$DB_NAME.'.'.$table.' WHERE id > 9999') );

		$this->assertEquals( 3, self::db()->num_rows(self::db()->query('SELECT * FROM '.self::$DB_NAME.'.'.$table)) );
		$this->assertEquals( 3, self::db()->query_num_rows('SELECT * FROM '.self::$DB_NAME.'.'.$table) );

		$q = self::db()->query('SELECT * FROM '.self::$DB_NAME.'.'.$table);
		$this->assertEquals( 3, self::db()->num_rows($q) );
		$this->assertEquals( array('id' => 1), self::db()->fetch_assoc($q) );
		$this->assertTrue( self::db()->free_result($q) );

		$this->assertEquals( array('message' => '', 'code' => 0), self::db()->error() );

		$this->assertEquals( array(1), self::db()->fetch_row(self::db()->query('SELECT * FROM '.self::$DB_NAME.'.'.$table)) );
		$obj = new stdClass();
		$obj->id = 1;
		$this->assertEquals( $obj, self::db()->fetch_object(self::db()->query('SELECT * FROM '.self::$DB_NAME.'.'.$table)) );
	}
	public function test_escape_key() {
		$this->assertEquals( '`mykey`', self::db()->escape_key('mykey') );
	}
	public function test_escape_val() {
		$this->assertEquals( '\'myval\'', self::db()->escape_val('myval') );
	}
	public function test_real_name() {
		$table = self::db()->DB_PREFIX. __FUNCTION__;
		$table_wo_prefix = 'tbl_'.__FUNCTION__;
		$prefixed_table = 'prfx_'.$table;
		$this->assertEquals( $table, self::db()->_real_name($table) );
		self::db()->_found_tables = array($table => $prefixed_table);
		$this->assertEquals( array($table => $prefixed_table), self::db()->_found_tables );
		$this->assertEquals( $prefixed_table, self::db()->_real_name($table) );
		$table_wo_prefix = 'tbl_'.__FUNCTION__;
		$this->assertEquals( self::db()->DB_PREFIX.$table_wo_prefix, self::db()->_real_name($table_wo_prefix) );
		$this->assertEquals( self::db()->DB_PREFIX.$table_wo_prefix, self::db()->_real_name(self::db()->DB_PREFIX.$table_wo_prefix) );
	}
	public function test_fix_table_name() {
		$table_wo_prefix = 'tbl_'.__FUNCTION__;
		$table_sys = self::db()->DB_PREFIX. 'sys_'. $table_wo_prefix;
		$table = self::db()->DB_PREFIX. $table_wo_prefix;
		$this->assertEquals( $table, self::db()->_fix_table_name($table) );
		$this->assertEquals( $table, self::db()->_fix_table_name('dbt_'.$table) );
		$this->assertEquals( $table, self::db()->_fix_table_name($table_wo_prefix) );
		$this->assertEquals( $table, self::db()->_fix_table_name('dbt_'.$table_wo_prefix) );
		self::db()->_need_sys_prefix = array($table_wo_prefix);
		$this->assertEquals( array($table_wo_prefix), self::db()->_need_sys_prefix );
		$this->assertEquals( $table_sys, self::db()->_fix_table_name($table_wo_prefix) );
		$this->assertEquals( $table_sys, self::db()->_fix_table_name('dbt_'.$table_wo_prefix) );
		$this->assertEquals( $table_sys, self::db()->_fix_table_name($table_sys) );
		$this->assertEquals( $table_sys, self::db()->_fix_table_name('dbt_'.$table_sys) );
		$this->assertEquals( $table_sys, self::db()->_fix_table_name($table) );
		$this->assertEquals( $table_sys, self::db()->_fix_table_name('dbt_'.$table) );
	}
	public function test_escape_with_filter() {
// TODO: esf (escape with filter)
#		$this->assertEquals( $expected, self::db()-> );
	}
	public function test_real_escape_string() {
// TODO: real_escape_string and all its aliases: escape, escape_string, es
#		$this->assertEquals( $expected, self::db()-> );
	}
	public function test_mysql_escape_mimic() {
// TODO: _mysql_escape_mimic
#		$this->assertEquals( $expected, self::db()-> );
	}
	public function test_multi_query() {
#		$this->assertEquals( $expected, self::db()-> );
	}
	public function test_prepare_and_exec() {
#		$this->assertEquals( $expected, self::db()-> );
	}
	public function test_get_cached() {
#		$this->assertEquals( $expected, self::db()-> );
	}
	public function test_get_all_cached() {
#		$this->assertEquals( $expected, self::db()-> );
	}
	public function test_get_one() {
#		$this->assertEquals( $expected, self::db()-> );
	}
	public function test_get_2d() {
#		$this->assertEquals( $expected, self::db()-> );
	}
	public function test_get_deep_array() {
#		$this->assertEquals( $expected, self::db()-> );
	}
	public function test_insert() {
#		$this->assertEquals( $expected, self::db()-> );
	}
	public function test_insert_ignore() {
#		$this->assertEquals( $expected, self::db()-> );
	}
	public function test_insert_on_duplicate_key_update() {
#		$this->assertEquals( $expected, self::db()-> );
	}
	public function test_replace() {
#		$this->assertEquals( $expected, self::db()-> );
	}
	public function test_update() {
#		$this->assertEquals( $expected, self::db()-> );
	}
	public function test_update_batch() {
#		$this->assertEquals( $expected, self::db()-> );
	}
	public function test_insert_safe() {
#		$this->assertEquals( $expected, self::db()-> );
	}
	public function test_replace_safe() {
#		$this->assertEquals( $expected, self::db()-> );
	}
	public function test_update_safe() {
#		$this->assertEquals( $expected, self::db()-> );
	}
	public function test_update_batch_safe() {
#		$this->assertEquals( $expected, self::db()-> );
	}
	public function test_meta_columns() {
#		$this->assertEquals( $expected, self::db()-> );
	}
	public function test_meta_tables() {
#		$this->assertEquals( $expected, self::db()-> );
	}
	public function test_get_server_version() {
#		$this->assertEquals( $expected, self::db()-> );
	}
	public function test_get_host_info() {
#		$this->assertEquals( $expected, self::db()-> );
	}
	public function test_shutdown_queries() {
#		$this->assertEquals( $expected, self::db()-> );
	}
	public function test_repair_table() {
#		$this->assertEquals( $expected, self::db()-> );
	}
	public function test_split_sql() {
#		$this->assertEquals( $expected, self::db()-> );
	}
	public function test_utils() {
#		$this->assertEquals( $expected, self::db()-> );
	}
	public function test_query_builder() {
#		$this->assertEquals( $expected, self::db()-> );
	}
	public function test_model() {
#		$this->assertEquals( $expected, self::db()-> );
	}
	public function test_select() {
#		$this->assertEquals( $expected, self::db()-> );
	}
	public function test_from() {
#		$this->assertEquals( $expected, self::db()-> );
	}
	public function test_delete() {
#		$this->assertEquals( $expected, self::db()-> );
	}
	public function test_limit() {
#		$this->assertEquals( $expected, self::db()-> );
	}
	public function test_count() {
#		$this->assertEquals( $expected, self::db()-> );
	}
	public function test_transactions() {
// TODO: begin
// TODO: commit
// TODO: rollback
#		$this->assertEquals( $expected, self::db()-> );
	}
}