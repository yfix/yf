<?php

require dirname(__DIR__).'/yf_unit_tests_setup.php';
require dirname(__DIR__).'/db_setup.php';

/**
 * @requires extension mysql
 */
class class_db_utils_mysql_real_test extends PHPUnit_Framework_TestCase {
	public static $db = null;
	public static $server_version = '';
	public static $DB_NAME = '';
	public static function setUpBeforeClass() {
		self::$DB_NAME = DB_NAME;
		$db_class = load_db_class();
		self::$db = new $db_class('mysql5');
		self::$db->DB_PREFIX = DB_PREFIX;
		self::$db->RECONNECT_NUM_TRIES = 1;
		self::$db->CACHE_TABLE_NAMES = false;
		self::$db->ERROR_AUTO_REPAIR = false;
		self::$db->FIX_DATA_SAFE = true;
		self::$db->connect(array(
			'host'	=> 'localhost',
			'name'	=> self::$DB_NAME,
			'user'	=> DB_USER,
			'pswd'	=> DB_PSWD,
			'force' => true,
		));
		self::$db->query('DROP DATABASE IF EXISTS '.self::$DB_NAME);
		self::$server_version = self::$db->get_server_version();
	}
	public static function tearDownAfterClass() {
		self::utils()->drop_database(self::$DB_NAME);
	}
	private static function utils() {
		return self::$db->utils();
	}
	public function test_connected() {
		$this->assertNotEmpty( self::$db );
		$this->assertTrue( is_object(self::$db) );
		$this->assertTrue( self::$db->_connected );
		$this->assertTrue( is_object(self::$db->db) );
		$this->assertTrue( is_resource(self::$db->db->db_connect_id) );
	}
	public function test_list_databases() {
		$all_dbs = self::utils()->list_databases();
		$this->assertTrue( is_array($all_dbs) );
		$this->assertNotEmpty( $all_dbs );
		$this->assertTrue( in_array('mysql', $all_dbs) );
		$this->assertTrue( in_array('information_schema', $all_dbs) );
		if (version_compare(self::$server_version, '5.6.0') >= 0) {
			$this->assertTrue( in_array('performance_schema', $all_dbs) );
			$this->assertTrue( in_array('sys', $all_dbs) );
		}
	}
	public function test_drop_database() {
		$all_dbs = self::utils()->list_databases();
		if (in_array(self::$DB_NAME, $all_dbs)) {
			self::utils()->drop_database(self::$DB_NAME);
			$all_dbs = self::utils()->list_databases();
		}
		$this->assertFalse( in_array(self::$DB_NAME, $all_dbs) );
	}
	public function test_create_database() {
		$all_dbs = self::utils()->list_databases();
		if (in_array(self::$DB_NAME, $all_dbs)) {
			self::utils()->drop_database(self::$DB_NAME);
			$all_dbs = self::utils()->list_databases();
		}
		$this->assertFalse( in_array(self::$DB_NAME, $all_dbs) );
		$this->assertTrue( self::utils()->create_database(self::$DB_NAME) );
		$all_dbs = self::utils()->list_databases();
		$this->assertTrue( in_array(self::$DB_NAME, $all_dbs) );
	}
	public function test_database_exists() {
		$this->assertFalse( self::utils()->database_exists(self::$DB_NAME.'ggdfgdf') );
		$this->assertTrue( self::utils()->database_exists(self::$DB_NAME) );
	}
	public function test_database_info() {
		$expected = array(
			'name'		=> self::$DB_NAME,
			'charset'	=> 'utf8',
			'collation'	=> 'utf8_general_ci',
		);
		$this->assertNotEmpty( self::utils()->database_info(self::$DB_NAME) );
		$this->assertTrue( self::utils()->db->query('ALTER DATABASE '.self::$DB_NAME.' CHARACTER SET "utf8" COLLATE "utf8_general_ci"') );
		$this->assertEquals( $expected, self::utils()->database_info(self::$DB_NAME) );
	}
	public function test_alter_database() {
		$expected = array(
			'name'		=> self::$DB_NAME,
			'charset'	=> 'utf8',
			'collation'	=> 'utf8_general_ci',
		);
		$this->assertNotEmpty( self::utils()->database_info(self::$DB_NAME) );
		$this->assertTrue( self::utils()->db->query('ALTER DATABASE '.self::$DB_NAME.' CHARACTER SET "latin1" COLLATE "latin1_general_ci"') );
		$this->assertNotEquals( $expected, self::utils()->database_info(self::$DB_NAME) );
		$this->assertTrue( self::utils()->alter_database(self::$DB_NAME, array('charset' => 'utf8','collation' => 'utf8_general_ci')) );
		$this->assertEquals( $expected, self::utils()->database_info(self::$DB_NAME) );
	}
	public function test_rename_database() {
		$NEW_DB_NAME = self::$DB_NAME.'_new';
		$this->assertTrue( self::utils()->database_exists(self::$DB_NAME) );
		$this->assertFalse( self::utils()->database_exists($NEW_DB_NAME) );
		$this->assertTrue( self::utils()->rename_database(self::$DB_NAME, $NEW_DB_NAME) );
		$this->assertFalse( self::utils()->database_exists(self::$DB_NAME) );
		$this->assertTrue( self::utils()->database_exists($NEW_DB_NAME) );
		$this->assertTrue( self::utils()->rename_database($NEW_DB_NAME, self::$DB_NAME) );
		$this->assertTrue( self::utils()->database_exists(self::$DB_NAME) );
		$this->assertFalse( self::utils()->database_exists($NEW_DB_NAME) );
	}
	public function test_list_tables() {
		$this->assertEquals( array(), self::utils()->list_tables(self::$DB_NAME) );
		$table = 'testme1';
		$this->assertTrue( self::utils()->db->query('CREATE TABLE '.self::$DB_NAME.'.'.$table.'(id INT(10))') );
		$this->assertEquals( array($table => $table), self::utils()->list_tables(self::$DB_NAME) );
		$this->assertTrue( self::utils()->db->query('DROP TABLE '.self::$DB_NAME.'.'.$table.'') );
		$this->assertEquals( array(), self::utils()->list_tables(self::$DB_NAME) );
	}
	public function test_table_exists() {
		$table = 'testme2';
		$this->assertFalse( self::utils()->table_exists($table, self::$DB_NAME) );
		$this->assertTrue( self::utils()->db->query('CREATE TABLE '.self::$DB_NAME.'.'.$table.'(id INT(10))') );
		$this->assertTrue( self::utils()->table_exists($table, self::$DB_NAME) );
		$this->assertTrue( self::utils()->db->query('DROP TABLE '.self::$DB_NAME.'.'.$table.'') );
		$this->assertFalse( self::utils()->table_exists($table, self::$DB_NAME) );
	}
	public function test_drop_table() {
		$table = 'testme3';
		$this->assertFalse( self::utils()->table_exists($table, self::$DB_NAME) );
#		$this->assertTrue( self::utils()->db->query('CREATE TABLE '.self::$DB_NAME.'.'.$table.'(id INT(10))') );
#		$this->assertTrue( self::utils()->table_exists($table, self::$DB_NAME) );
#		$this->assertTrue( self::utils()->drop_table($table, self::$DB_NAME) );
#		$this->assertFalse( self::utils()->table_exists($table, self::$DB_NAME) );
	}
	public function test_create_table() {
		$table_name = 'my_test_table';
		$data = array(
			array('name' => 'id', 'type' => 'int', 'length' => 10, 'auto_inc' => true),
			array('name' => 'name', 'type' => 'varchar', 'length' => 255, 'default' => '', 'not_null' => true),
			array('name' => 'active', 'type' => 'enum', 'length' => '\'0\',\'1\'', 'default' => '0', 'not_null' => true),
			array('key' => 'primary', 'key_cols' => 'id'),
		);
#		$this->assertTrue( self::utils()->create_table($table_name, self::$DB_NAME, $data) );
#		$this->assertTrue( self::utils()->table_exists($table_name, self::$DB_NAME) );
	}
	public function test_table_get_columns() {
// TODO
	}
	public function test__parse_column_type() {
// TODO
	}
	public function test__compile_create_table() {
// TODO
	}
	public function test_table_info() {
#		$this->assertEquals( array(), self::utils()->table_info(self::$DB_NAME, array()) );
	}
	public function test_alter_table() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_rename_table() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_truncate_table() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_check_table() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_optimize_table() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_repair_table() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_list_indexes() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_add_index() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_drop_index() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_list_foreign_keys() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_add_foreign_key() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_drop_foreign_key() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_list_columns() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_add_column() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_rename_column() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_alter_column() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_drop_column() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_list_views() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_create_view() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_drop_view() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_list_procedures() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_create_procedure() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_drop_procedure() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_list_functions() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_create_function() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_drop_function() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_list_triggers() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_create_trigger() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_drop_trigger() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_list_events() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_create_event() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_drop_event() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_list_users() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_create_user() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_drop_user() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_split_sql() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_database() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_table() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_column() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_view() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_procedure() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_trigger() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_event() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_list_collations() {
#		$this->assertEquals( self::utils()->list_collations() );
	}
	public function test_list_charsets() {
#		$this->assertEquals( self::utils()->list_charsets() );
	}
}