<?php

require_once __DIR__.'/db_real_abstract.php';

/**
 * @requires extension mysql
 */
class class_db_real_utils_mysql_test extends db_real_abstract {
	public static function setUpBeforeClass() {
		self::_connect();
		self::$server_version = self::$db->get_server_version();
		if (getenv('CI') === 'true' && getenv('DRONE') === 'true') {
			self::$CI_SERVER = 'DRONE';
		}
		if (self::$CI_SERVER != 'DRONE') {
			self::$db->query('DROP DATABASE IF EXISTS '.self::$DB_NAME);
		}
	}

	public function test_list_collations() {
		$this->assertNotEmpty( self::utils()->list_collations() );
	}
	public function test_list_charsets() {
		$this->assertNotEmpty( self::utils()->list_charsets() );
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
		$this->assertTrue( self::utils()->create_database(self::$DB_NAME) );

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
	public function test_table_get_columns() {
		$this->assertTrue( self::utils()->create_database(self::$DB_NAME) );

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
	public function test_rename_table() {
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$data = array(
			array('name' => 'id', 'type' => 'int', 'length' => 10),
		);
		$this->assertTrue( self::utils()->create_table(self::$DB_NAME.'.'.$table, $data) );
		$this->assertTrue( self::utils()->table_exists(self::$DB_NAME.'.'.$table) );
		$new_table = $table.'_new';
		$this->assertTrue( self::utils()->rename_table(self::$DB_NAME.'.'.$table, self::$DB_NAME.'.'.$new_table) );
		$this->assertFalse( self::utils()->table_exists(self::$DB_NAME.'.'.$table) );
		$this->assertTrue( self::utils()->table_exists(self::$DB_NAME.'.'.$new_table ) );
	}
	public function test_truncate_table() {
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$data = array(array('name' => 'id', 'type' => 'int', 'length' => 10));
		$this->assertTrue( self::utils()->create_table(self::$DB_NAME.'.'.$table, $data) );
		$this->assertTrue( self::utils()->table_exists(self::$DB_NAME.'.'.$table) );
		$this->assertTrue( self::utils()->db->db->select_db(self::$DB_NAME) );
		$to_insert = array(
			1 => array('id' => 1),
			2 => array('id' => 2),
		);
		$this->assertTrue( self::utils()->db->insert($table, $to_insert) );
		$this->assertEquals( $to_insert, self::utils()->db->from($table)->get_all() );
		$this->assertTrue( self::utils()->truncate_table($table) );
	}
	public function test_check_table() {
		$table = current(self::utils()->list_tables(self::$DB_NAME));
		$this->assertNotEmpty( self::utils()->check_table($table) );
	}
	public function test_optimize_table() {
		$table = current(self::utils()->list_tables(self::$DB_NAME));
		$this->assertNotEmpty( self::utils()->optimize_table($table) );
	}
	public function test_repair_table() {
		$table = current(self::utils()->list_tables(self::$DB_NAME));
		$this->assertNotEmpty( self::utils()->repair_table($table) );
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
		$old_info = self::utils()->table_info(self::$DB_NAME.'.'.$table);
		$this->assertEquals( 'InnoDB', $old_info['engine'] );
		$this->assertTrue( self::utils()->alter_table(self::$DB_NAME.'.'.$table, array('engine' => 'ARCHIVE')) );
		$new_info = self::utils()->table_info(self::$DB_NAME.'.'.$table);
		$this->assertEquals( 'ARCHIVE', $new_info['engine'] );
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

	public function test_column_exists() {
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$data = array(array('name' => 'id', 'type' => 'int', 'length' => 10));
		$this->assertTrue( self::utils()->create_table(self::$DB_NAME.'.'.$table, $data) );
		$this->assertTrue( self::utils()->table_exists(self::$DB_NAME.'.'.$table) );
		$this->assertTrue( self::utils()->column_exists(self::$DB_NAME.'.'.$table, 'id') );
		$this->assertFalse( self::utils()->column_exists(self::$DB_NAME.'.'.$table, 'id33') );
	}
	public function test_column_info() {
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$col_info = array('name' => 'id', 'type' => 'int', 'length' => 10);
		$this->assertTrue( self::utils()->create_table(self::$DB_NAME.'.'.$table, array($col_info)) );
		$result = self::utils()->column_info(self::$DB_NAME.'.'.$table, 'id');
		foreach (array('name','type','length') as $f) {
			$this->assertEquals( $col_info[$f], $result[$f] );
		}
	}
	public function test_add_column() {
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$col_info = array('name' => 'id', 'type' => 'int', 'length' => 10);
		$col_info2 = array('name' => 'id2', 'type' => 'int', 'length' => 8);
		$this->assertTrue( self::utils()->create_table(self::$DB_NAME.'.'.$table, array($col_info)) );
		$this->assertTrue( self::utils()->column_exists(self::$DB_NAME.'.'.$table, 'id') );
		$this->assertFalse( self::utils()->column_exists(self::$DB_NAME.'.'.$table, 'id2') );
		$this->assertFalse( self::utils()->column_info(self::$DB_NAME.'.'.$table, 'id2') );
		$this->assertTrue( self::utils()->add_column(self::$DB_NAME.'.'.$table, $col_info2) );
		$result = self::utils()->column_info(self::$DB_NAME.'.'.$table, 'id2');
		foreach (array('name','type','length') as $f) {
			$this->assertEquals( $col_info2[$f], $result[$f] );
		}
	}
	public function test_drop_column() {
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$col_info = array('name' => 'id', 'type' => 'int', 'length' => 10);
		$col_info2 = array('name' => 'id2', 'type' => 'int', 'length' => 8);
		$this->assertTrue( self::utils()->create_table(self::$DB_NAME.'.'.$table, array($col_info, $col_info2)) );
		$this->assertTrue( self::utils()->column_exists(self::$DB_NAME.'.'.$table, 'id') );
		$this->assertTrue( self::utils()->column_exists(self::$DB_NAME.'.'.$table, 'id2') );
		$this->assertTrue( self::utils()->drop_column(self::$DB_NAME.'.'.$table, 'id2') );
		$this->assertFalse( self::utils()->column_exists(self::$DB_NAME.'.'.$table, 'id2') );
	}
	public function test_rename_column() {
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$col_info = array('name' => 'id', 'type' => 'int', 'length' => 10);
		$this->assertTrue( self::utils()->create_table(self::$DB_NAME.'.'.$table, array($col_info)) );
		$this->assertTrue( self::utils()->column_exists(self::$DB_NAME.'.'.$table, 'id') );
		$this->assertFalse( self::utils()->column_exists(self::$DB_NAME.'.'.$table, 'id2') );
		$this->assertTrue( self::utils()->rename_column(self::$DB_NAME.'.'.$table, 'id', 'id2') );
		$this->assertTrue( self::utils()->column_exists(self::$DB_NAME.'.'.$table, 'id2') );
	}
	public function test_alter_column() {
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$col_info = array('name' => 'id', 'type' => 'int', 'length' => 10);
		$col_info2 = array('name' => 'id2', 'type' => 'int', 'length' => 8);
		$this->assertTrue( self::utils()->create_table(self::$DB_NAME.'.'.$table, array($col_info, $col_info2)) );
		$this->assertTrue( self::utils()->column_exists(self::$DB_NAME.'.'.$table, 'id') );
		$this->assertEquals( '10', self::utils()->column_info_item(self::$DB_NAME.'.'.$table, 'id', 'length') );
		$this->assertTrue( self::utils()->alter_column(self::$DB_NAME.'.'.$table, 'id', array('length' => 8)) );
		$this->assertEquals( '8', self::utils()->column_info_item(self::$DB_NAME.'.'.$table, 'id', 'length') );

		$this->assertEquals( array('id', 'id2'), array_keys(self::utils()->table_get_columns(self::$DB_NAME.'.'.$table)) );
		$this->assertTrue( self::utils()->alter_column(self::$DB_NAME.'.'.$table, 'id2', array('first' => true)) );
		$this->assertEquals( array('id2', 'id'), array_keys(self::utils()->table_get_columns(self::$DB_NAME.'.'.$table)) );
		$this->assertTrue( self::utils()->alter_column(self::$DB_NAME.'.'.$table, 'id2', array('after' => 'id')) );
		$this->assertEquals( array('id', 'id2'), array_keys(self::utils()->table_get_columns(self::$DB_NAME.'.'.$table)) );
	}

	public function test_list_indexes() {
		$this->assertTrue( self::utils()->create_database(self::$DB_NAME) );
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$data = array(
			array('name' => 'id', 'type' => 'int', 'length' => 10, 'auto_inc' => true),
			array('name' => 'primary', 'key' => 'primary', 'key_cols' => 'id'),
		);
		$this->assertTrue( self::utils()->create_table(self::$DB_NAME.'.'.$table, $data) );
		$this->assertTrue( self::utils()->table_exists(self::$DB_NAME.'.'.$table) );
		$expected = array(
			'PRIMARY' => array('name' => 'PRIMARY', 'type' => 'primary','columns' => array('id')),
		);
		$this->assertEquals( $expected, self::utils()->list_indexes(self::$DB_NAME.'.'.$table) );
	}
	public function test_index_info() {
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$data = array(
			array('name' => 'id', 'type' => 'int', 'length' => 10, 'auto_inc' => true),
			array('name' => 'primary', 'key' => 'primary', 'key_cols' => 'id'),
		);
		$this->assertTrue( self::utils()->create_table(self::$DB_NAME.'.'.$table, $data) );
		$this->assertTrue( self::utils()->table_exists(self::$DB_NAME.'.'.$table) );
		$this->assertEquals( array('name' => 'PRIMARY', 'type' => 'primary', 'columns' => array('id')), self::utils()->index_info(self::$DB_NAME.'.'.$table, 'PRIMARY') );
	}
	public function test_index_exists() {
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$data = array(
			array('name' => 'id', 'type' => 'int', 'length' => 10, 'auto_inc' => true),
			array('name' => 'primary', 'key' => 'primary', 'key_cols' => 'id'),
		);
		$this->assertTrue( self::utils()->create_table(self::$DB_NAME.'.'.$table, $data) );
		$this->assertTrue( self::utils()->table_exists(self::$DB_NAME.'.'.$table) );
		$this->assertTrue( self::utils()->index_exists(self::$DB_NAME.'.'.$table, 'PRIMARY') );
	}
	public function test_drop_index() {
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$data = array(
			array('name' => 'id', 'type' => 'int', 'length' => 10),
			array('key' => 'primary', 'key_cols' => 'id'),
		);
		$this->assertTrue( self::utils()->create_table(self::$DB_NAME.'.'.$table, $data) );
		$this->assertTrue( self::utils()->table_exists(self::$DB_NAME.'.'.$table) );
		$this->assertTrue( self::utils()->index_exists(self::$DB_NAME.'.'.$table, 'PRIMARY') );
		$this->assertTrue( self::utils()->drop_index(self::$DB_NAME.'.'.$table, 'PRIMARY') );
		$this->assertFalse( self::utils()->index_exists(self::$DB_NAME.'.'.$table, 'PRIMARY') );
	}
	public function test_add_index() {
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$data = array(
			array('name' => 'id', 'type' => 'int', 'length' => 10),
		);
		$this->assertTrue( self::utils()->create_table(self::$DB_NAME.'.'.$table, $data) );
		$this->assertTrue( self::utils()->table_exists(self::$DB_NAME.'.'.$table) );
		$this->assertFalse( self::utils()->index_exists(self::$DB_NAME.'.'.$table, 'PRIMARY') );
		$this->assertTrue( self::utils()->add_index(self::$DB_NAME.'.'.$table, 'PRIMARY', array('id'), array('type' => 'primary')) );
		$this->assertTrue( self::utils()->index_exists(self::$DB_NAME.'.'.$table, 'PRIMARY') );
	}
	public function test_update_index() {
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$data = array(
			array('name' => 'id', 'type' => 'int', 'length' => 10),
			array('name' => 'id2', 'type' => 'int', 'length' => 10),
		);
		$this->assertTrue( self::utils()->create_table(self::$DB_NAME.'.'.$table, $data) );
		$this->assertTrue( self::utils()->table_exists(self::$DB_NAME.'.'.$table) );
		$this->assertTrue( self::utils()->add_index(self::$DB_NAME.'.'.$table, 'PRIMARY', array('id'), array('type' => 'primary')) );
		$this->assertEquals( array('name' => 'PRIMARY', 'type' => 'primary', 'columns' => array('id')), self::utils()->index_info(self::$DB_NAME.'.'.$table, 'PRIMARY') );
		$this->assertTrue( self::utils()->update_index(self::$DB_NAME.'.'.$table, 'PRIMARY', array('id2'), array('type' => 'primary')) );
		$this->assertEquals( array('name' => 'PRIMARY', 'type' => 'primary', 'columns' => array('id2')), self::utils()->index_info(self::$DB_NAME.'.'.$table, 'PRIMARY') );
	}

	public function test_list_foreign_keys() {
		$this->assertTrue( self::utils()->create_database(self::$DB_NAME) );
		$table1 = self::utils()->db->DB_PREFIX. __FUNCTION__.'_1';
		$table2 = self::utils()->db->DB_PREFIX. __FUNCTION__.'_2';
		$data = array(
			array('name' => 'id', 'type' => 'int', 'length' => 10),
			array('name' => 'primary', 'key' => 'primary', 'key_cols' => 'id'),
		);
		$fkey = 'fkey_'.__FUNCTION__;
		$this->assertTrue( self::utils()->create_table(self::$DB_NAME.'.'.$table1, $data) );
		$this->assertTrue( self::utils()->create_table(self::$DB_NAME.'.'.$table2, $data) );
		$this->assertEmpty( self::utils()->list_foreign_keys(self::$DB_NAME.'.'.$table1) );
		$this->assertTrue( self::utils()->add_foreign_key(self::$DB_NAME.'.'.$table1, $fkey, array('id'), self::$DB_NAME.'.'.$table2, array('id')) );
		$expected = array(
			$fkey => array('name' => $fkey, 'local' => 'id', 'table' => $table2, 'foreign' => 'id'),
		);
		$this->assertEquals( $expected, self::utils()->list_foreign_keys(self::$DB_NAME.'.'.$table1) );
	}
	public function test_foreign_key_info() {
		$table1 = self::utils()->db->DB_PREFIX. __FUNCTION__.'_1';
		$table2 = self::utils()->db->DB_PREFIX. __FUNCTION__.'_2';
		$data = array(
			array('name' => 'id', 'type' => 'int', 'length' => 10),
			array('name' => 'primary', 'key' => 'primary', 'key_cols' => 'id'),
		);
		$fkey = 'fkey_'.__FUNCTION__;
		$this->assertTrue( self::utils()->create_table(self::$DB_NAME.'.'.$table1, $data) );
		$this->assertTrue( self::utils()->create_table(self::$DB_NAME.'.'.$table2, $data) );
		$this->assertEmpty( self::utils()->foreign_key_info(self::$DB_NAME.'.'.$table1, $fkey) );
		$this->assertTrue( self::utils()->add_foreign_key(self::$DB_NAME.'.'.$table1, $fkey, array('id'), self::$DB_NAME.'.'.$table2, array('id')) );
		$this->assertEquals( array('name' => $fkey, 'local' => 'id', 'table' => $table2, 'foreign' => 'id'), self::utils()->foreign_key_info(self::$DB_NAME.'.'.$table1, $fkey) );
	}
	public function test_foreign_key_exists() {
		$table1 = self::utils()->db->DB_PREFIX. __FUNCTION__.'_1';
		$table2 = self::utils()->db->DB_PREFIX. __FUNCTION__.'_2';
		$data = array(
			array('name' => 'id', 'type' => 'int', 'length' => 10),
			array('name' => 'primary', 'key' => 'primary', 'key_cols' => 'id'),
		);
		$fkey = 'fkey_'.__FUNCTION__;
		$this->assertTrue( self::utils()->create_table(self::$DB_NAME.'.'.$table1, $data) );
		$this->assertTrue( self::utils()->create_table(self::$DB_NAME.'.'.$table2, $data) );
		$this->assertFalse( self::utils()->foreign_key_exists(self::$DB_NAME.'.'.$table1, $fkey) );
		$this->assertTrue( self::utils()->add_foreign_key(self::$DB_NAME.'.'.$table1, $fkey, array('id'), self::$DB_NAME.'.'.$table2, array('id')) );
		$this->assertTrue( self::utils()->foreign_key_exists(self::$DB_NAME.'.'.$table1, $fkey) );
	}
	public function test_drop_foreign_key() {
		$table1 = self::utils()->db->DB_PREFIX. __FUNCTION__.'_1';
		$table2 = self::utils()->db->DB_PREFIX. __FUNCTION__.'_2';
		$data = array(
			array('name' => 'id', 'type' => 'int', 'length' => 10),
			array('name' => 'primary', 'key' => 'primary', 'key_cols' => 'id'),
		);
		$fkey = 'fkey_'.__FUNCTION__;
		$this->assertTrue( self::utils()->create_table(self::$DB_NAME.'.'.$table1, $data) );
		$this->assertTrue( self::utils()->create_table(self::$DB_NAME.'.'.$table2, $data) );
		$this->assertFalse( self::utils()->foreign_key_exists(self::$DB_NAME.'.'.$table1, $fkey) );
		$this->assertTrue( self::utils()->add_foreign_key(self::$DB_NAME.'.'.$table1, $fkey, array('id'), self::$DB_NAME.'.'.$table2, array('id')) );
		$this->assertTrue( self::utils()->foreign_key_exists(self::$DB_NAME.'.'.$table1, $fkey) );
		$this->assertTrue( self::utils()->drop_foreign_key(self::$DB_NAME.'.'.$table1, $fkey) );
		$this->assertFalse( self::utils()->foreign_key_exists(self::$DB_NAME.'.'.$table1, $fkey) );
	}
	public function test_add_foreign_key() {
		$table1 = self::utils()->db->DB_PREFIX. __FUNCTION__.'_1';
		$table2 = self::utils()->db->DB_PREFIX. __FUNCTION__.'_2';
		$data = array(
			array('name' => 'id', 'type' => 'int', 'length' => 10),
			array('name' => 'primary', 'key' => 'primary', 'key_cols' => 'id'),
		);
		$fkey = 'fkey_'.__FUNCTION__;
		$this->assertTrue( self::utils()->create_table(self::$DB_NAME.'.'.$table1, $data) );
		$this->assertTrue( self::utils()->create_table(self::$DB_NAME.'.'.$table2, $data) );
		$this->assertEmpty( self::utils()->foreign_key_info(self::$DB_NAME.'.'.$table1, $fkey) );
		$this->assertTrue( self::utils()->add_foreign_key(self::$DB_NAME.'.'.$table1, $fkey, array('id'), self::$DB_NAME.'.'.$table2, array('id')) );
		$this->assertEquals( array('name' => $fkey, 'local' => 'id', 'table' => $table2, 'foreign' => 'id'), self::utils()->foreign_key_info(self::$DB_NAME.'.'.$table1, $fkey) );
	}
	public function test_update_foreign_key() {
		$table1 = self::utils()->db->DB_PREFIX. __FUNCTION__.'_1';
		$table2 = self::utils()->db->DB_PREFIX. __FUNCTION__.'_2';
		$data = array(
			array('name' => 'id', 'type' => 'int', 'length' => 10),
			array('name' => 'id2', 'type' => 'int', 'length' => 10),
			array('name' => 'primary', 'key' => 'primary', 'key_cols' => 'id'),
			array('name' => 'unique', 'key' => 'unique', 'key_cols' => 'id2'),
		);
		$fkey = 'fkey_'.__FUNCTION__;
		$this->assertTrue( self::utils()->create_table(self::$DB_NAME.'.'.$table1, $data) );
		$this->assertTrue( self::utils()->create_table(self::$DB_NAME.'.'.$table2, $data) );
		$this->assertTrue( self::utils()->add_foreign_key(self::$DB_NAME.'.'.$table1, $fkey, array('id'), self::$DB_NAME.'.'.$table2, array('id')) );
		$this->assertEquals( array('name' => $fkey, 'local' => 'id', 'table' => $table2, 'foreign' => 'id'), self::utils()->foreign_key_info(self::$DB_NAME.'.'.$table1, $fkey) );
		$this->assertTrue( self::utils()->update_foreign_key(self::$DB_NAME.'.'.$table1, $fkey, array('id2'), self::$DB_NAME.'.'.$table2, array('id2')) );
		$this->assertEquals( array('name' => $fkey, 'local' => 'id2', 'table' => $table2, 'foreign' => 'id2'), self::utils()->foreign_key_info(self::$DB_NAME.'.'.$table1, $fkey) );
	}

	public function test_list_views() {
		$this->assertTrue( self::utils()->create_database(self::$DB_NAME) );

		$view = self::utils()->db->DB_PREFIX. 'view_'.__FUNCTION__;
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$data = array(array('name' => 'id', 'type' => 'int', 'length' => 10));
		$this->assertEmpty( self::utils()->list_views(self::$DB_NAME) );
		$this->assertTrue( self::utils()->create_table(self::$DB_NAME.'.'.$table, $data) );
		$this->assertTrue( self::utils()->table_exists(self::$DB_NAME.'.'.$table) );
		$this->assertTrue( self::utils()->create_view(self::$DB_NAME.'.'.$view, 'SELECT * FROM '.self::$DB_NAME.'.'.$table) );
		$this->assertNotEmpty( self::utils()->list_views(self::$DB_NAME) );
	}
	public function test_view_exists() {
		$view = self::utils()->db->DB_PREFIX. 'view_'.__FUNCTION__;
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$data = array(array('name' => 'id', 'type' => 'int', 'length' => 10));
		$this->assertFalse( self::utils()->view_exists(self::$DB_NAME.'.'.$view) );
		$this->assertTrue( self::utils()->create_table(self::$DB_NAME.'.'.$table, $data) );
		$this->assertTrue( self::utils()->table_exists(self::$DB_NAME.'.'.$table) );
		$this->assertTrue( self::utils()->create_view(self::$DB_NAME.'.'.$view, 'SELECT * FROM '.self::$DB_NAME.'.'.$table) );
		$this->assertTrue( self::utils()->view_exists(self::$DB_NAME.'.'.$view) );
	}
	public function test_view_info() {
		$view = self::utils()->db->DB_PREFIX. 'view_'.__FUNCTION__;
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$data = array(array('name' => 'id', 'type' => 'int', 'length' => 10));
		$this->assertFalse( self::utils()->view_exists(self::$DB_NAME.'.'.$view) );
		$this->assertTrue( self::utils()->create_table(self::$DB_NAME.'.'.$table, $data) );
		$this->assertTrue( self::utils()->table_exists(self::$DB_NAME.'.'.$table) );
		$this->assertTrue( self::utils()->create_view(self::$DB_NAME.'.'.$view, 'SELECT * FROM '.self::$DB_NAME.'.'.$table) );
		$this->assertNotEmpty( self::utils()->view_info(self::$DB_NAME.'.'.$view) );
	}
	public function test_drop_view() {
		$view = self::utils()->db->DB_PREFIX. 'view_'.__FUNCTION__;
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$data = array(array('name' => 'id', 'type' => 'int', 'length' => 10));
		$this->assertFalse( self::utils()->view_exists(self::$DB_NAME.'.'.$view) );
		$this->assertTrue( self::utils()->create_table(self::$DB_NAME.'.'.$table, $data) );
		$this->assertTrue( self::utils()->table_exists(self::$DB_NAME.'.'.$table) );
		$this->assertTrue( self::utils()->create_view(self::$DB_NAME.'.'.$view, 'SELECT * FROM '.self::$DB_NAME.'.'.$table) );
		$this->assertTrue( self::utils()->view_exists(self::$DB_NAME.'.'.$view) );
		$this->assertTrue( self::utils()->drop_view(self::$DB_NAME.'.'.$view) );
		$this->assertFalse( self::utils()->view_exists(self::$DB_NAME.'.'.$view) );
	}
	public function test_create_view() {
		$view = self::utils()->db->DB_PREFIX. 'view_'.__FUNCTION__;
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$data = array(array('name' => 'id', 'type' => 'int', 'length' => 10));
		$this->assertFalse( self::utils()->view_exists(self::$DB_NAME.'.'.$view) );
		$this->assertTrue( self::utils()->create_table(self::$DB_NAME.'.'.$table, $data) );
		$this->assertTrue( self::utils()->table_exists(self::$DB_NAME.'.'.$table) );
		$this->assertTrue( self::utils()->create_view(self::$DB_NAME.'.'.$view, 'SELECT * FROM '.self::$DB_NAME.'.'.$table) );
		$this->assertTrue( self::utils()->view_exists(self::$DB_NAME.'.'.$view) );
	}

	public function test_list_procedures() {
		$this->assertTrue( self::utils()->create_database(self::$DB_NAME) );

		$proc = self::utils()->db->DB_PREFIX. 'proc_'.__FUNCTION__;
		$procedures = self::utils()->list_procedures();
		if (empty($procedures)) {
			$sql = 'CREATE PROCEDURE '.self::$DB_NAME.'.'.$proc.' (OUT param1 INT) BEGIN SELECT COUNT(*) INTO param1 FROM t; END';
			$this->assertTrue( self::utils()->db->query($sql) );
		}
		$this->assertNotEmpty( self::utils()->list_procedures() );
	}
	public function test_procedure_exists() {
		$proc = self::utils()->db->DB_PREFIX. 'proc_'.__FUNCTION__;
		$this->assertFalse( self::utils()->procedure_exists(self::$DB_NAME.'.'.$proc) );
		$sql = 'CREATE PROCEDURE '.self::$DB_NAME.'.'.$proc.' (OUT param1 INT) BEGIN SELECT COUNT(*) INTO param1 FROM t; END';
		$this->assertTrue( self::utils()->db->query($sql) );
		$this->assertTrue( self::utils()->procedure_exists(self::$DB_NAME.'.'.$proc) );
	}
	public function test_procedure_info() {
		$proc = self::utils()->db->DB_PREFIX. 'proc_'.__FUNCTION__;
		$this->assertFalse( self::utils()->procedure_exists(self::$DB_NAME.'.'.$proc) );
		$sql = 'CREATE PROCEDURE '.self::$DB_NAME.'.'.$proc.' (OUT param1 INT) BEGIN SELECT COUNT(*) INTO param1 FROM t; END';
		$this->assertTrue( self::utils()->db->query($sql) );
		$this->assertTrue( self::utils()->procedure_exists(self::$DB_NAME.'.'.$proc) );
		$expected = array(
			'db' => self::$DB_NAME,
			'name' => $proc,
			'type' => 'PROCEDURE',
			'security_type' => 'DEFINER',
			'comment' => '',
		);
		$result = self::utils()->procedure_info(self::$DB_NAME.'.'.$proc);
		foreach ($expected as $k => $_expected) {
			$this->assertEquals( $_expected, $result[$k] );
		}
	}
	public function test_drop_procedure() {
		$proc = self::utils()->db->DB_PREFIX. 'proc_'.__FUNCTION__;
		$this->assertFalse( self::utils()->procedure_exists(self::$DB_NAME.'.'.$proc) );
		$sql = 'CREATE PROCEDURE '.self::$DB_NAME.'.'.$proc.' (OUT param1 INT) BEGIN SELECT COUNT(*) INTO param1 FROM t; END';
		$this->assertTrue( self::utils()->db->query($sql) );
		$this->assertTrue( self::utils()->procedure_exists(self::$DB_NAME.'.'.$proc) );
		$this->assertTrue( self::utils()->drop_procedure(self::$DB_NAME.'.'.$proc) );
		$this->assertFalse( self::utils()->procedure_exists(self::$DB_NAME.'.'.$proc) );
	}
	public function test_create_procedure() {
		$proc = self::utils()->db->DB_PREFIX. 'proc_'.__FUNCTION__;
		$this->assertFalse( self::utils()->procedure_exists(self::$DB_NAME.'.'.$proc) );
		$this->assertTrue( self::utils()->create_procedure(self::$DB_NAME.'.'.$proc, 'SELECT COUNT(*) INTO param1 FROM t;', 'OUT param1 INT') );
		$this->assertTrue( self::utils()->procedure_exists(self::$DB_NAME.'.'.$proc) );
		$expected = array(
			'db' => self::$DB_NAME,
			'name' => $proc,
			'type' => 'PROCEDURE',
			'security_type' => 'DEFINER',
			'comment' => '',
		);
		$result = self::utils()->procedure_info(self::$DB_NAME.'.'.$proc);
		foreach ($expected as $k => $_expected) {
			$this->assertEquals( $_expected, $result[$k] );
		}
	}

	public function test_list_functions() {
		$this->assertTrue( self::utils()->create_database(self::$DB_NAME) );

		$func = self::utils()->db->DB_PREFIX. 'func_'.__FUNCTION__;
		$funcs = self::utils()->list_functions();
		if (empty($funcs)) {
			$sql = 'CREATE FUNCTION '.self::$DB_NAME.'.'.$func.' (s CHAR(20)) RETURNS CHAR(50) DETERMINISTIC RETURN CONCAT("Hello, ",s,"!");';
			$this->assertTrue( self::utils()->db->query($sql) );
		}
		$this->assertNotEmpty( self::utils()->list_functions() );
	}
	public function test_function_exists() {
		$func = self::utils()->db->DB_PREFIX. 'func_'.__FUNCTION__;
		$this->assertFalse( self::utils()->function_exists($func) );
		$sql = 'CREATE FUNCTION '.self::$DB_NAME.'.'.$func.' (s CHAR(20)) RETURNS CHAR(50) DETERMINISTIC RETURN CONCAT("Hello, ",s,"!");';
		$this->assertTrue( self::utils()->db->query($sql) );
		$this->assertTrue( self::utils()->function_exists($func) );
	}
	public function test_function_info() {
		$func = self::utils()->db->DB_PREFIX. 'func_'.__FUNCTION__;
		$this->assertFalse( self::utils()->function_exists(self::$DB_NAME.'.'.$func) );
		$sql = 'CREATE FUNCTION '.self::$DB_NAME.'.'.$func.' (s CHAR(20)) RETURNS CHAR(50) DETERMINISTIC RETURN CONCAT("Hello, ",s,"!");';
		$this->assertTrue( self::utils()->db->query($sql) );
		$expected = array(
			'db' => self::$DB_NAME,
			'name' => $func,
			'type' => 'FUNCTION',
			'comment' => '',
		);
		$result = self::utils()->function_info(self::$DB_NAME.'.'.$func);
		foreach ($expected as $k => $_expected) {
			$this->assertEquals( $_expected, $result[$k] );
		}
	}
	public function test_drop_function() {
		$func = self::utils()->db->DB_PREFIX. 'func_'.__FUNCTION__;
		$this->assertFalse( self::utils()->function_exists($func) );
		$sql = 'CREATE FUNCTION '.self::$DB_NAME.'.'.$func.' (s CHAR(20)) RETURNS CHAR(50) DETERMINISTIC RETURN CONCAT("Hello, ",s,"!");';
		$this->assertTrue( self::utils()->db->query($sql) );
		$this->assertTrue( self::utils()->function_exists($func) );
		$this->assertTrue( self::utils()->drop_function($func) );
		$this->assertFalse( self::utils()->function_exists($func) );
	}
	public function test_create_function() {
		$func = self::utils()->db->DB_PREFIX. 'func_'.__FUNCTION__;
		$this->assertFalse( self::utils()->function_exists($func) );
		$this->assertTrue( self::utils()->create_function(self::$DB_NAME.'.'.$func, 'CONCAT("Hello, ",s,"!")', 'CHAR(50)', 's CHAR(20)') );
		$this->assertTrue( self::utils()->function_exists($func) );
		$expected = array(
			'db' => self::$DB_NAME,
			'name' => $func,
			'type' => 'FUNCTION',
			'comment' => '',
		);
		$result = self::utils()->function_info($func);
		foreach ($expected as $k => $_expected) {
			$this->assertEquals( $_expected, $result[$k] );
		}
	}

	public function test_list_triggers() {
		$this->assertTrue( self::utils()->create_database(self::$DB_NAME) );

		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$this->assertTrue( self::utils()->create_table(self::$DB_NAME.'.'.$table, array(array('name' => 'id', 'type' => 'int', 'length' => 10))) );

		$trg = self::utils()->db->DB_PREFIX. 'trg_'.__FUNCTION__;
		$triggers = self::utils()->list_triggers(self::$DB_NAME);
		if (empty($triggers)) {
			$sql = 'CREATE TRIGGER '.self::$DB_NAME.'.'.$trg.' BEFORE INSERT ON '.self::$DB_NAME.'.'.$table.' FOR EACH ROW SET @sum = @sum + NEW.id';
			$this->assertTrue( self::utils()->db->query($sql) );
		}
		$this->assertNotEmpty( self::utils()->list_triggers(self::$DB_NAME) );
	}
	public function test_trigger_exists() {
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$this->assertTrue( self::utils()->create_table(self::$DB_NAME.'.'.$table, array(array('name' => 'id', 'type' => 'int', 'length' => 10))) );
		$trg = self::utils()->db->DB_PREFIX. 'trg_'.__FUNCTION__;
		$this->assertFalse( self::utils()->trigger_exists(self::$DB_NAME.'.'.$trg) );
		$sql = 'CREATE TRIGGER '.self::$DB_NAME.'.'.$trg.' BEFORE INSERT ON '.self::$DB_NAME.'.'.$table.' FOR EACH ROW SET @sum = @sum + NEW.id';
		$this->assertTrue( self::utils()->db->query($sql) );
		$this->assertTrue( self::utils()->trigger_exists(self::$DB_NAME.'.'.$trg) );
	}
	public function test_trigger_info() {
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$this->assertTrue( self::utils()->create_table(self::$DB_NAME.'.'.$table, array(array('name' => 'id', 'type' => 'int', 'length' => 10))) );
		$trg = self::utils()->db->DB_PREFIX. 'trg_'.__FUNCTION__;
		$sql = 'CREATE TRIGGER '.self::$DB_NAME.'.'.$trg.' BEFORE INSERT ON '.self::$DB_NAME.'.'.$table.' FOR EACH ROW SET @sum = @sum + NEW.id';
		$this->assertTrue( self::utils()->db->query($sql) );
		$expected = array(
			'name' => $trg,
			'table' => $table,
			'event' => 'INSERT',
			'timing' => 'BEFORE',
			'statement' => 'SET @sum = @sum + NEW.id',
			'definer' => NULL,
		);
		$result = self::utils()->trigger_info(self::$DB_NAME.'.'.$trg);
		foreach ($expected as $k => $_expected) {
			$this->assertEquals( $_expected, $result[$k] );
		}
	}
	public function test_drop_trigger() {
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$this->assertTrue( self::utils()->create_table(self::$DB_NAME.'.'.$table, array(array('name' => 'id', 'type' => 'int', 'length' => 10))) );
		$trg = self::utils()->db->DB_PREFIX. 'trg_'.__FUNCTION__;
		$this->assertFalse( self::utils()->trigger_exists(self::$DB_NAME.'.'.$trg) );
		$sql = 'CREATE TRIGGER '.self::$DB_NAME.'.'.$trg.' BEFORE INSERT ON '.self::$DB_NAME.'.'.$table.' FOR EACH ROW SET @sum = @sum + NEW.id';
		$this->assertTrue( self::utils()->db->query($sql) );
		$this->assertTrue( self::utils()->trigger_exists(self::$DB_NAME.'.'.$trg) );
		$this->assertTrue( self::utils()->drop_trigger(self::$DB_NAME.'.'.$trg) );
		$this->assertFalse( self::utils()->trigger_exists(self::$DB_NAME.'.'.$trg) );
	}
	public function test_create_trigger() {
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$this->assertTrue( self::utils()->create_table(self::$DB_NAME.'.'.$table, array(array('name' => 'id', 'type' => 'int', 'length' => 10))) );
		$trg = self::utils()->db->DB_PREFIX. 'trg_'.__FUNCTION__;
		$this->assertTrue( self::utils()->create_trigger($trg, self::$DB_NAME.'.'.$table, 'before', 'insert', 'SET @sum = @sum + NEW.id') );
		$expected = array(
			'name' => $trg,
			'table' => $table,
			'event' => 'INSERT',
			'timing' => 'BEFORE',
			'statement' => 'SET @sum = @sum + NEW.id',
			'definer' => NULL,
		);
		$result = self::utils()->trigger_info(self::$DB_NAME.'.'.$trg);
		foreach ($expected as $k => $_expected) {
			$this->assertEquals( $_expected, $result[$k] );
		}
	}

	public function test_list_events() {
		$this->assertTrue( self::utils()->create_database(self::$DB_NAME) );

		$evt = self::utils()->db->DB_PREFIX. 'evt_'.__FUNCTION__;
		$events = self::utils()->list_events(self::$DB_NAME);
		if (empty($events)) {
			$sql = 'CREATE EVENT '.self::$DB_NAME.'.'.$evt.'  ON SCHEDULE AT "2014-10-10 23:59:00"  DO INSERT INTO '.self::$DB_NAME.'.totals VALUES (NOW());';
			$this->assertTrue( self::utils()->db->query($sql) );
		}
		$this->assertNotEmpty( self::utils()->list_events(self::$DB_NAME) );
	}
	public function test_event_exists() {
		$evt = self::utils()->db->DB_PREFIX. 'evt_'.__FUNCTION__;
		$this->assertFalse( self::utils()->event_exists(self::$DB_NAME.'.'.$evt) );
		$sql = 'CREATE EVENT '.self::$DB_NAME.'.'.$evt.'  ON SCHEDULE AT "2014-10-10 23:59:00"  DO INSERT INTO '.self::$DB_NAME.'.totals VALUES (NOW());';
		$this->assertTrue( self::utils()->db->query($sql) );
		$this->assertTrue( self::utils()->event_exists(self::$DB_NAME.'.'.$evt) );
	}
	public function test_event_info() {
		$evt = self::utils()->db->DB_PREFIX. 'evt_'.__FUNCTION__;
		$this->assertFalse( self::utils()->event_exists(self::$DB_NAME.'.'.$evt) );
		$sql = 'CREATE EVENT '.self::$DB_NAME.'.'.$evt.'  ON SCHEDULE AT "2014-10-10 23:59:00"  DO INSERT INTO '.self::$DB_NAME.'.totals VALUES (NOW());';
		$this->assertTrue( self::utils()->db->query($sql) );
		$expected = array(
			'name' => $evt,
			'db' => self::$DB_NAME,
			'definer' => NULL,
			'timezone' => NULL,
			'type' => 'ONE TIME',
			'execute_at' => NULL,
			'interval_value' => NULL,
			'interval_field' => NULL,
			'starts' => NULL,
			'ends' => NULL,
			'status' => 'ENABLED',
			'originator' => '0',
		);
		$result = self::utils()->event_info(self::$DB_NAME.'.'.$evt);
		foreach ($expected as $k => $_expected) {
			$this->assertEquals( $_expected, $result[$k] );
		}
	}
	public function test_drop_event() {
		$evt = self::utils()->db->DB_PREFIX. 'evt_'.__FUNCTION__;
		$this->assertFalse( self::utils()->event_exists(self::$DB_NAME.'.'.$evt) );
		$sql = 'CREATE EVENT '.self::$DB_NAME.'.'.$evt.'  ON SCHEDULE AT "2014-10-10 23:59:00"  DO INSERT INTO '.self::$DB_NAME.'.totals VALUES (NOW());';
		$this->assertTrue( self::utils()->db->query($sql) );
		$this->assertTrue( self::utils()->event_exists(self::$DB_NAME.'.'.$evt) );
		$this->assertTrue( self::utils()->drop_event(self::$DB_NAME.'.'.$evt) );
		$this->assertFalse( self::utils()->event_exists(self::$DB_NAME.'.'.$evt) );
	}
	public function test_create_event() {
		$evt = self::utils()->db->DB_PREFIX. 'evt_'.__FUNCTION__;
		$this->assertFalse( self::utils()->event_exists(self::$DB_NAME.'.'.$evt) );
		$this->assertTrue( self::utils()->create_event(self::$DB_NAME.'.'.$evt, 'AT "2014-10-10 23:59:00"', 'INSERT INTO '.self::$DB_NAME.'.totals VALUES (NOW())') );
		$expected = array(
			'name' => $evt,
			'db' => self::$DB_NAME,
			'definer' => NULL,
			'timezone' => NULL,
			'type' => 'ONE TIME',
			'execute_at' => NULL,
			'interval_value' => NULL,
			'interval_field' => NULL,
			'starts' => NULL,
			'ends' => NULL,
			'status' => 'ENABLED',
			'originator' => '0',
		);
		$result = self::utils()->event_info(self::$DB_NAME.'.'.$evt);
		foreach ($expected as $k => $_expected) {
			$this->assertEquals( $_expected, $result[$k] );
		}
	}

	public function test_list_users() {
		$this->assertNotEmpty( self::utils()->list_users() );
	}
	public function test_user_exists() {
		$this->assertTrue( self::utils()->user_exists('root@localhost') );
	}
	public function test_user_info() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_delete_user() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_add_user() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_update_user() {
#		$this->assertEquals( self::utils()-> );
	}

	public function test_escape_database_name() {
		$this->assertEquals( '`test_db`', self::utils()->_escape_database_name('test_db') );
	}
	public function test_escape_table_name() {
		$this->assertEquals( '', self::utils()->_escape_table_name('') );
		$this->assertEquals( '`'.self::utils()->db->DB_PREFIX.'test_table`', self::utils()->_escape_table_name('test_table') );
		$this->assertEquals( '`test_db`.`'.self::utils()->db->DB_PREFIX.'test_table`', self::utils()->_escape_table_name('test_db.test_table') );
	}
	public function test_escape_key() {
		$this->assertEquals( '`test_key`', self::utils()->_escape_key('test_key') );
	}
	public function test_escape_val() {
		$this->assertEquals( '\'test_val\'', self::utils()->_escape_val('test_val') );
	}
	public function test_escape_fields() {
		$fields = array('id1','id2','test_field');
		$expected = array('`id1`','`id2`','`test_field`');
		$this->assertEquals( $expected, self::utils()->_escape_fields($fields) );
	}
	public function test__es() {
		$this->assertEquals( 'hello world', self::utils()->_es('hello world') );
		$this->assertEquals( 'hello\\\'world\\\'', self::utils()->_es('hello\'world\'') );
	}

	public function test_split_sql() {
#		$this->assertEquals( self::utils()-> );
	}
	public function test_get_table_structure_from_db_installer() {
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
}