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
			self::$db->query('DROP DATABASE IF EXISTS '.self::$DB_NAME);
		}
	}
	public static function tearDownAfterClass() {
		if (self::$CI_SERVER == 'DRONE') { return ; }
		self::utils()->drop_database(self::$DB_NAME);
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
	public function test_driver() {
		$this->assertEquals( self::$DB_DRIVER, self::$db->DB_TYPE );
		$this->assertEquals( self::$DB_DRIVER, self::utils()->db->DB_TYPE );
		list(,$driver) = explode('_driver_', get_class(self::utils()->db->db));
		$this->assertEquals( self::$DB_DRIVER, $driver );
	}
	public function test_list_databases() {
		if (self::$CI_SERVER == 'DRONE') { return ; }
		$all_dbs = self::utils()->list_databases();
		$this->assertTrue( is_array($all_dbs) );
		$this->assertNotEmpty( $all_dbs );
		$this->assertTrue( in_array('mysql', $all_dbs) );
		$this->assertTrue( in_array('information_schema', $all_dbs) );
		if (version_compare(self::$server_version, '5.5.0') >= 0) {
			$this->assertTrue( in_array('performance_schema', $all_dbs) );
		}
		if (version_compare(self::$server_version, '5.6.0') >= 0) {
			$this->assertTrue( in_array('sys', $all_dbs) );
		}
	}
	public function test_drop_database() {
		if (self::$CI_SERVER == 'DRONE') { return ; }
		$all_dbs = self::utils()->list_databases();
		if (in_array(self::$DB_NAME, $all_dbs)) {
			self::utils()->drop_database(self::$DB_NAME);
			$all_dbs = self::utils()->list_databases();
		}
		$this->assertFalse( in_array(self::$DB_NAME, $all_dbs) );
	}
	public function test_create_database() {
		if (self::$CI_SERVER == 'DRONE') { return ; }
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
		if (self::$CI_SERVER == 'DRONE') { return ; }
		$this->assertFalse( self::utils()->database_exists(self::$DB_NAME.'ggdfgdf') );
		$this->assertTrue( self::utils()->database_exists(self::$DB_NAME) );
	}
	public function test_database_info() {
		if (self::$CI_SERVER == 'DRONE') { return ; }
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
		if (self::$CI_SERVER == 'DRONE') { return ; }
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
		if (self::$CI_SERVER == 'DRONE') { return ; }
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
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$this->assertTrue( self::utils()->db->query('CREATE TABLE '.self::$DB_NAME.'.'.$table.'(id INT(10))') );
		$this->assertEquals( array($table => $table), self::utils()->list_tables(self::$DB_NAME) );
		$this->assertTrue( self::utils()->db->query('DROP TABLE '.self::$DB_NAME.'.'.$table.'') );
		$this->assertEquals( array(), self::utils()->list_tables(self::$DB_NAME) );
	}
	public function test_table_exists() {
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$this->assertFalse( self::utils()->table_exists(self::$DB_NAME.'.'.$table) );
		$this->assertTrue( self::utils()->db->query('CREATE TABLE '.self::$DB_NAME.'.'.$table.'(id INT(10))') );
		$this->assertTrue( self::utils()->table_exists(self::$DB_NAME.'.'.$table) );
		$this->assertTrue( self::utils()->db->query('DROP TABLE '.self::$DB_NAME.'.'.$table.'') );
		$this->assertFalse( self::utils()->table_exists(self::$DB_NAME.'.'.$table) );
	}
	public function test_drop_table() {
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$this->assertFalse( self::utils()->table_exists(self::$DB_NAME.'.'.$table) );
		$this->assertTrue( self::utils()->db->query('CREATE TABLE '.self::$DB_NAME.'.'.$table.' (id INT(10))') );
		$this->assertTrue( self::utils()->table_exists(self::$DB_NAME.'.'.$table) );
		$this->assertTrue( self::utils()->drop_table(self::$DB_NAME.'.'.$table) );
		$this->assertFalse( self::utils()->table_exists(self::$DB_NAME.'.'.$table) );
	}
	public function test__compile_create_table() {
		$in = array(
			array('name' => 'id', 'type' => 'int', 'length' => 10, 'auto_inc' => true),
			array('name' => 'name', 'type' => 'varchar', 'length' => 255, 'default' => '', 'not_null' => true),
			array('name' => 'active', 'type' => 'enum', 'length' => '\'0\',\'1\'', 'default' => '0', 'not_null' => true),
			array('key' => 'primary', 'key_cols' => 'id'),
		);
		$expected = 
			'`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,'.PHP_EOL
			.'`name` VARCHAR(255) NOT NULL DEFAULT \'\','.PHP_EOL
			.'`active` ENUM(\'0\',\'1\') NOT NULL DEFAULT \'0\','.PHP_EOL
			.'PRIMARY KEY (id)';
		$this->assertEquals( $expected, self::utils()->_compile_create_table($in) );
	}
	public function test_create_table() {
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$data = array(
			array('name' => 'id', 'type' => 'int', 'length' => 10, 'auto_inc' => true),
			array('name' => 'name', 'type' => 'varchar', 'length' => 255, 'default' => '', 'not_null' => true),
			array('name' => 'active', 'type' => 'enum', 'length' => '\'0\',\'1\'', 'default' => '0', 'not_null' => true),
			array('key' => 'primary', 'key_cols' => 'id'),
		);
		$this->assertTrue( self::utils()->create_table(self::$DB_NAME.'.'.$table, $data) );
		$this->assertTrue( self::utils()->table_exists(self::$DB_NAME.'.'.$table) );
	}
	public function test__parse_column_type() {
		$this->assertEquals( array('type' => 'int','length' => null,'unsigned' => false,'decimals' => null,'values' => null), self::utils()->_parse_column_type('int') );
		$this->assertEquals( array('type' => 'int','length' => 8,'unsigned' => false,'decimals' => null,'values' => null), self::utils()->_parse_column_type('int(8)') );
		$this->assertEquals( array('type' => 'int','length' => 11,'unsigned' => true,'decimals' => null,'values' => null), self::utils()->_parse_column_type('tinyint(11) unsigned') );
		$this->assertEquals( array('type' => 'int','length' => 8,'unsigned' => false,'decimals' => null,'values' => null), self::utils()->_parse_column_type('integer(8)') );
		$this->assertEquals( array('type' => 'bit','length' => null,'unsigned' => false,'decimals' => null,'values' => null), self::utils()->_parse_column_type('bit') );
		$this->assertEquals( array('type' => 'decimal','length' => 6,'unsigned' => false,'decimals' => 2,'values' => null), self::utils()->_parse_column_type('decimal(6,2)') );
		$this->assertEquals( array('type' => 'decimal','length' => 6,'unsigned' => true,'decimals' => 2,'values' => null), self::utils()->_parse_column_type('decimal(6,2) unsigned') );
		$this->assertEquals( array('type' => 'numeric','length' => 6,'unsigned' => true,'decimals' => 2,'values' => null), self::utils()->_parse_column_type('numeric(6,2) unsigned') );
		$this->assertEquals( array('type' => 'real','length' => 6,'unsigned' => true,'decimals' => 2,'values' => null), self::utils()->_parse_column_type('real(6,2) unsigned') );
		$this->assertEquals( array('type' => 'float','length' => 6,'unsigned' => true,'decimals' => 2,'values' => null), self::utils()->_parse_column_type('float(6,2) unsigned') );
		$this->assertEquals( array('type' => 'double','length' => 6,'unsigned' => true,'decimals' => 2,'values' => null), self::utils()->_parse_column_type('double(6,2) unsigned') );
		$this->assertEquals( array('type' => 'char','length' => 6,'unsigned' => false,'decimals' => null,'values' => null), self::utils()->_parse_column_type('char(6)') );
		$this->assertEquals( array('type' => 'varchar','length' => 256,'unsigned' => false,'decimals' => null,'values' => null), self::utils()->_parse_column_type('varchar(256)') );
		$this->assertEquals( array('type' => 'tinytext','length' => null,'unsigned' => false,'decimals' => null,'values' => null), self::utils()->_parse_column_type('tinytext') );
		$this->assertEquals( array('type' => 'mediumtext','length' => null,'unsigned' => false,'decimals' => null,'values' => null), self::utils()->_parse_column_type('mediumtext') );
		$this->assertEquals( array('type' => 'longtext','length' => null,'unsigned' => false,'decimals' => null,'values' => null), self::utils()->_parse_column_type('longtext') );
		$this->assertEquals( array('type' => 'text','length' => null,'unsigned' => false,'decimals' => null,'values' => null), self::utils()->_parse_column_type('text') );
		$this->assertEquals( array('type' => 'tinyblob','length' => null,'unsigned' => false,'decimals' => null,'values' => null), self::utils()->_parse_column_type('tinyblob') );
		$this->assertEquals( array('type' => 'mediumblob','length' => null,'unsigned' => false,'decimals' => null,'values' => null), self::utils()->_parse_column_type('mediumblob') );
		$this->assertEquals( array('type' => 'longblob','length' => null,'unsigned' => false,'decimals' => null,'values' => null), self::utils()->_parse_column_type('longblob') );
		$this->assertEquals( array('type' => 'blob','length' => null,'unsigned' => false,'decimals' => null,'values' => null), self::utils()->_parse_column_type('blob') );
		$this->assertEquals( array('type' => 'binary','length' => null,'unsigned' => false,'decimals' => null,'values' => null), self::utils()->_parse_column_type('binary') );
		$this->assertEquals( array('type' => 'varbinary','length' => null,'unsigned' => false,'decimals' => null,'values' => null), self::utils()->_parse_column_type('varbinary') );
		$this->assertEquals( array('type' => 'timestamp','length' => null,'unsigned' => false,'decimals' => null,'values' => null), self::utils()->_parse_column_type('timestamp') );
		$this->assertEquals( array('type' => 'datetime','length' => null,'unsigned' => false,'decimals' => null,'values' => null), self::utils()->_parse_column_type('datetime') );
		$this->assertEquals( array('type' => 'date','length' => null,'unsigned' => false,'decimals' => null,'values' => null), self::utils()->_parse_column_type('date') );
		$this->assertEquals( array('type' => 'time','length' => null,'unsigned' => false,'decimals' => null,'values' => null), self::utils()->_parse_column_type('time') );
		$this->assertEquals( array('type' => 'year','length' => null,'unsigned' => false,'decimals' => null,'values' => null), self::utils()->_parse_column_type('year') );
		$this->assertEquals( array('type' => 'enum','length' => null,'unsigned' => false,'decimals' => null,'values' => array('0','1')), self::utils()->_parse_column_type('enum(\'0\',\'1\')') );
		$this->assertEquals( array('type' => 'set','length' => null,'unsigned' => false,'decimals' => null,'values' => array('0','1')), self::utils()->_parse_column_type('set(\'0\',\'1\')') );
	}
	public function test_table_get_columns() {
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$data = array(
			array('name' => 'id', 'type' => 'int', 'length' => 10, 'auto_inc' => true),
			array('name' => 'name', 'type' => 'varchar', 'length' => 255, 'default' => '', 'not_null' => true),
			array('name' => 'active', 'type' => 'enum', 'length' => '\'0\',\'1\'', 'default' => '0', 'not_null' => true),
			array('key' => 'primary', 'key_cols' => 'id'),
		);
		$this->assertTrue( self::utils()->create_table(self::$DB_NAME.'.'.$table, $data) );
		$this->assertTrue( self::utils()->table_exists(self::$DB_NAME.'.'.$table) );
		$expected = array(
			'id' => array(
				'name' => 'id','type' => 'int','length' => '10','unsigned' => true,'collation' => NULL,'null' => false,
				'default' => NULL,'auto_inc' => true,'is_primary' => true,'is_unique' => false,'type_raw' => 'int(10) unsigned',
			),
			'name' => array(
				'name' => 'name','type' => 'varchar','length' => '255','unsigned' => false,'collation' => 'utf8_general_ci','null' => false,
				'default' => '','auto_inc' => false,'is_primary' => false,'is_unique' => false,'type_raw' => 'varchar(255)',
			),
			'active' => array(
				'name' => 'active','type' => 'enum','length' => '','unsigned' => false,'collation' => 'utf8_general_ci','null' => false,
				'default' => '0','auto_inc' => false,'is_primary' => false,'is_unique' => false,'type_raw' => 'enum(\'0\',\'1\')',
			),
		);
		$this->assertEquals( $expected, self::utils()->table_get_columns(self::$DB_NAME.'.'.$table) );
	}
	public function test_table_info() {
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$data = array(
			array('name' => 'id', 'type' => 'int', 'length' => 10, 'auto_inc' => true),
			array('name' => 'name', 'type' => 'varchar', 'length' => 255, 'default' => '', 'not_null' => true),
			array('name' => 'active', 'type' => 'enum', 'length' => '\'0\',\'1\'', 'default' => '0', 'not_null' => true),
			array('key' => 'primary', 'key_cols' => 'id'),
		);
		$this->assertTrue( self::utils()->create_table(self::$DB_NAME.'.'.$table, $data) );
		$this->assertTrue( self::utils()->table_exists(self::$DB_NAME.'.'.$table) );
		$expected_columns = array(
			'id' => array(
				'name' => 'id','type' => 'int','length' => '10','unsigned' => true,'collation' => NULL,'null' => false,
				'default' => NULL,'auto_inc' => true,'is_primary' => true,'is_unique' => false,'type_raw' => 'int(10) unsigned',
			),
			'name' => array(
				'name' => 'name','type' => 'varchar','length' => '255','unsigned' => false,'collation' => 'utf8_general_ci','null' => false,
				'default' => '','auto_inc' => false,'is_primary' => false,'is_unique' => false,'type_raw' => 'varchar(255)',
			),
			'active' => array(
				'name' => 'active','type' => 'enum','length' => '','unsigned' => false,'collation' => 'utf8_general_ci','null' => false,
				'default' => '0','auto_inc' => false,'is_primary' => false,'is_unique' => false,'type_raw' => 'enum(\'0\',\'1\')',
			),
		);
		$expected = array(
			'name' => $table,
			'db_name' => self::$DB_NAME,
			'columns' => $expected_columns,
			'row_format' => 'Compact',
			'collation' => 'utf8_general_ci',
			'engine' => 'InnoDB',
			'rows' => '0',
			'data_size' => '16384',
			'auto_inc' => '1',
			'comment' => '',
			'create_time' => '2014-01-01 01:01:01',
			'update_time' => null,
			'charset' => 'utf8',
		);
		$received = self::utils()->table_info(self::$DB_NAME.'.'.$table);
		$received && $received['create_time'] = '2014-01-01 01:01:01';
		$this->assertEquals( $expected, $received );
	}
	public function test_alter_table() {
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$data = array(
			array('name' => 'id', 'type' => 'int', 'length' => 10, 'auto_inc' => true),
			array('name' => 'name', 'type' => 'varchar', 'length' => 255, 'default' => '', 'not_null' => true),
			array('name' => 'active', 'type' => 'enum', 'length' => '\'0\',\'1\'', 'default' => '0', 'not_null' => true),
			array('key' => 'primary', 'key_cols' => 'id'),
		);
		$this->assertTrue( self::utils()->create_table(self::$DB_NAME.'.'.$table, $data) );
		$this->assertTrue( self::utils()->table_exists(self::$DB_NAME.'.'.$table) );
#		$old_info = self::utils()->table_info(self::$DB_NAME.'.'.$table);
#		$this->assertEquals( 'utf8_general_ci', $old_info['collation'] );
#		$this->assertTrue( self::utils()->alter_table(self::$DB_NAME.'.'.$table, array('collation' => 'latin1_general_ci')) );
#		$new_info = self::utils()->table_info(self::$DB_NAME.'.'.$table);
#		$this->assertEquals( 'latin1_general_ci', $new_info['collation'] );
	}
	public function test_rename_table() {
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$data = array(
			array('name' => 'id', 'type' => 'int', 'length' => 10),
		);
		$this->assertTrue( self::utils()->create_table(self::$DB_NAME.'.'.$table, $data) );
		$this->assertTrue( self::utils()->table_exists(self::$DB_NAME.'.'.$table) );
		$new_table = $table.'_new';
#		$this->assertTrue( self::utils()->rename_table(self::$DB_NAME.'.'.$table, $new_name) );
#		$this->assertFalse( self::utils()->table_exists(self::$DB_NAME.'.'.$table) );
#		$this->assertTrue( self::utils()->table_exists(self::$DB_NAME.'.'.$new_table, ) );
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