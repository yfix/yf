<?php

require_once __DIR__.'/db_real_abstract.php';

/**
 * @requires extension pgsql
 */
class class_db_real_utils_pgsql_test extends db_real_abstract {
	public static function setUpBeforeClass() {
		self::$_bak['DB_DRIVER'] = self::$DB_DRIVER;
		self::$DB_DRIVER = 'pgsql';
		self::_connect();
		self::utils()->truncate_database(self::db_name());
	}
	public static function tearDownAfterClass() {
		self::utils()->truncate_database(self::db_name());
		self::$DB_DRIVER = self::$_bak['DB_DRIVER'];
	}
	public static function _need_skip_test($name) {
		return false;
	}
	public static function db_name() {
		return self::$DB_NAME;
	}
	public static function table_name($name) {
#		return self::db_name().'.'.$name;
		return $name;
	}

	public function test_list_databases() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$all_dbs = self::utils()->list_databases();
		$this->assertTrue( is_array($all_dbs) );
		$this->assertNotEmpty( $all_dbs );
		$this->assertTrue( in_array('postgres', $all_dbs) );
	}
	public function test_database_exists() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertFalse( self::utils()->database_exists($this->db_name().'ggdfgdf') );
		$this->assertNotEmpty( self::utils()->database_exists($this->db_name()) );
	}
	public function test_database_info() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertNotEmpty( self::utils()->database_info($this->db_name()) );
	}
	public function test_truncate_database() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$this->assertFalse( self::utils()->table_exists($this->table_name($table)) );
		$this->assertNotEmpty( self::utils()->db->query('CREATE TABLE '.$this->table_name($table).' (id serial NOT NULL)') );
		$this->assertNotEmpty( self::utils()->table_exists($this->table_name($table)) );
		$this->assertNotEmpty( self::utils()->truncate_database($this->db_name()) );
		$this->assertFalse( self::utils()->table_exists($this->table_name($table)) );
		$this->assertEquals( [], self::utils()->list_tables($this->db_name()) );
	}

	public function test_list_tables() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }

		$this->assertEquals( [], self::utils()->list_tables($this->db_name()) );
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$this->assertTrue( (bool)self::utils()->db->query('CREATE TABLE '.$this->table_name($table).' (id serial NOT NULL)') );
		$this->assertEquals( [$table => $table], self::utils()->list_tables($this->db_name()) );
		$this->assertNotEmpty( self::utils()->db->query('DROP TABLE '.$this->table_name($table).'') );
		$this->assertEquals( [], self::utils()->list_tables($this->db_name()) );
	}
	public function test_table_exists() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$this->assertFalse( self::utils()->table_exists($this->table_name($table)) );
		$this->assertNotEmpty( self::utils()->db->query('CREATE TABLE '.$this->table_name($table).' (id serial NOT NULL)') );
		$this->assertNotEmpty( self::utils()->table_exists($this->table_name($table)) );
		$this->assertNotEmpty( self::utils()->db->query('DROP TABLE '.$this->table_name($table).'') );
		$this->assertFalse( self::utils()->table_exists($this->table_name($table)) );
	}
	public function test_drop_table() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$this->assertFalse( self::utils()->table_exists($this->table_name($table)) );
		$this->assertNotEmpty( self::utils()->db->query('CREATE TABLE '.$this->table_name($table).' (id serial NOT NULL)') );
		$this->assertNotEmpty( self::utils()->table_exists($this->table_name($table)) );
		$this->assertNotEmpty( self::utils()->drop_table($this->table_name($table)) );
		$this->assertFalse( self::utils()->table_exists($this->table_name($table)) );
	}
	public function test_table_get_columns() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }

		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$data = [
			['name' => 'id', 'type' => 'int', 'length' => 10, 'auto_inc' => true],
			['name' => 'name', 'type' => 'varchar', 'length' => 255, 'default' => '', 'not_null' => true],
			['name' => 'active', 'type' => 'enum', 'length' => '\'0\',\'1\'', 'default' => '0', 'not_null' => true],
			['key' => 'primary', 'key_cols' => 'id'],
		];
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table), $data) );
		$this->assertNotEmpty( self::utils()->table_exists($this->table_name($table)) );
		$expected = [
			'id' => [
				'name' => 'id','type' => 'int','length' => '10','unsigned' => true,'collate' => NULL,'nullable' => false,
				'default' => NULL,'auto_inc' => true,'primary' => true,'unique' => false,'type_raw' => 'int(10) unsigned',
			],
			'name' => [
				'name' => 'name','type' => 'varchar','length' => '255','unsigned' => false,'collate' => 'utf8_general_ci','nullable' => false,
				'default' => '','auto_inc' => false,'primary' => false,'unique' => false,'type_raw' => 'varchar(255)',
			],
			'active' => [
				'name' => 'active','type' => 'enum','length' => '','unsigned' => false,'collate' => 'utf8_general_ci','nullable' => false,
				'default' => '0','auto_inc' => false,'primary' => false,'unique' => false,'type_raw' => 'enum(\'0\',\'1\')',
			],
		];
		$this->assertEquals( $expected, self::utils()->table_get_columns($this->table_name($table)) );
	}
	public function test_table_info() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$data = [
			['name' => 'id', 'type' => 'int', 'length' => 10, 'auto_inc' => true],
			['name' => 'name', 'type' => 'varchar', 'length' => 255, 'default' => '', 'not_null' => true],
			['name' => 'active', 'type' => 'enum', 'length' => '\'0\',\'1\'', 'default' => '0', 'not_null' => true],
			['key' => 'primary', 'key_cols' => 'id'],
		];
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table), $data) );
		$this->assertNotEmpty( self::utils()->table_exists($this->table_name($table)) );
		$expected_columns = [
			'id' => [
				'name' => 'id','type' => 'int','length' => '10','unsigned' => true,'collate' => NULL,'nullable' => false,
				'default' => NULL,'auto_inc' => true,'primary' => true,'unique' => false,'type_raw' => 'int(10) unsigned',
			],
			'name' => [
				'name' => 'name','type' => 'varchar','length' => '255','unsigned' => false,'collate' => 'utf8_general_ci','nullable' => false,
				'default' => '','auto_inc' => false,'primary' => false,'unique' => false,'type_raw' => 'varchar(255)',
			],
			'active' => [
				'name' => 'active','type' => 'enum','length' => '','unsigned' => false,'collate' => 'utf8_general_ci','nullable' => false,
				'default' => '0','auto_inc' => false,'primary' => false,'unique' => false,'type_raw' => 'enum(\'0\',\'1\')',
			],
		];
		$expected = [
			'name' => $table,
			'db_name' => $this->db_name(),
			'columns' => $expected_columns,
			'row_format' => 'Compact',
			'collate' => 'utf8_general_ci',
			'engine' => 'InnoDB',
			'rows' => '0',
			'data_size' => '16384',
			'auto_inc' => '1',
			'comment' => '',
			'create_time' => '2014-01-01 01:01:01',
			'update_time' => null,
			'charset' => 'utf8',
		];
		$received = self::utils()->table_info($this->table_name($table));
		$received && $received['create_time'] = '2014-01-01 01:01:01';
		$this->assertEquals( $expected, $received );
	}
	public function test_rename_table() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$data = [
			['name' => 'id', 'type' => 'int', 'length' => 10],
		];
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table), $data) );
		$this->assertNotEmpty( self::utils()->table_exists($this->table_name($table)) );
		$new_table = $table.'_new';
		$this->assertNotEmpty( self::utils()->rename_table($this->table_name($table), $this->db_name().'.'.$new_table) );
		$this->assertFalse( self::utils()->table_exists($this->table_name($table)) );
		$this->assertNotEmpty( self::utils()->table_exists($this->db_name().'.'.$new_table ) );
	}
	public function test_truncate_table() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$data = [['name' => 'id', 'type' => 'int', 'length' => 10]];
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table), $data) );
		$this->assertNotEmpty( self::utils()->table_exists($this->table_name($table)) );
		$to_insert = [
			1 => ['id' => 1],
			2 => ['id' => 2],
		];
		$this->assertNotEmpty( self::utils()->db->insert($this->table_name($table), $to_insert) );
		$this->assertEquals( $to_insert, self::utils()->db->from($this->table_name($table))->get_all() );
		$this->assertNotEmpty( self::utils()->truncate_table($this->table_name($table)) );
	}
	public function test_alter_table() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$data = [
			['name' => 'id', 'type' => 'int', 'length' => 10, 'auto_inc' => true],
			['name' => 'name', 'type' => 'varchar', 'length' => 255, 'default' => '', 'not_null' => true],
			['name' => 'active', 'type' => 'enum', 'length' => '\'0\',\'1\'', 'default' => '0', 'not_null' => true],
			['key' => 'primary', 'key_cols' => 'id'],
		];
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table), $data) );
		$this->assertNotEmpty( self::utils()->table_exists($this->table_name($table)) );
		$old_info = self::utils()->table_info($this->table_name($table));
		$this->assertEquals( 'InnoDB', $old_info['engine'] );
		$this->assertNotEmpty( self::utils()->alter_table($this->table_name($table), ['engine' => 'ARCHIVE']) );
		$new_info = self::utils()->table_info($this->table_name($table));
		$this->assertEquals( 'ARCHIVE', $new_info['engine'] );
	}
	public function test__compile_create_table() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$in = [
			['name' => 'id', 'type' => 'int', 'length' => 10, 'auto_inc' => true],
			['name' => 'name', 'type' => 'varchar', 'length' => 255, 'default' => '', 'not_null' => true],
			['name' => 'active', 'type' => 'enum', 'length' => '\'0\',\'1\'', 'default' => '0', 'not_null' => true],
			['key' => 'primary', 'key_cols' => 'id'],
		];
		$expected = 
			'`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,'.PHP_EOL
			.'`name` VARCHAR(255) NOT NULL DEFAULT \'\','.PHP_EOL
			.'`active` ENUM(\'0\',\'1\') NOT NULL DEFAULT \'0\','.PHP_EOL
			.'PRIMARY KEY (id)';
		$this->assertEquals( $expected, self::utils()->_compile_create_table($in) );
	}
	public function test_create_table() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$data = [
			['name' => 'id', 'type' => 'int', 'length' => 10, 'auto_inc' => true],
			['name' => 'name', 'type' => 'varchar', 'length' => 255, 'default' => '', 'not_null' => true],
			['name' => 'active', 'type' => 'enum', 'length' => '\'0\',\'1\'', 'default' => '0', 'not_null' => true],
			['key' => 'primary', 'key_cols' => 'id'],
		];
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table), $data) );
		$this->assertNotEmpty( self::utils()->table_exists($this->table_name($table)) );
	}

	public function test__parse_column_type() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertEquals( ['type' => 'int','length' => null,'unsigned' => false,'decimals' => null,'values' => null], self::utils()->_parse_column_type('int') );
		$this->assertEquals( ['type' => 'int','length' => 8,'unsigned' => false,'decimals' => null,'values' => null], self::utils()->_parse_column_type('int(8)') );
		$this->assertEquals( ['type' => 'int','length' => 11,'unsigned' => true,'decimals' => null,'values' => null], self::utils()->_parse_column_type('tinyint(11) unsigned') );
		$this->assertEquals( ['type' => 'int','length' => 8,'unsigned' => false,'decimals' => null,'values' => null], self::utils()->_parse_column_type('integer(8)') );
		$this->assertEquals( ['type' => 'bit','length' => null,'unsigned' => false,'decimals' => null,'values' => null], self::utils()->_parse_column_type('bit') );
		$this->assertEquals( ['type' => 'decimal','length' => 6,'unsigned' => false,'decimals' => 2,'values' => null], self::utils()->_parse_column_type('decimal(6,2)') );
		$this->assertEquals( ['type' => 'decimal','length' => 6,'unsigned' => true,'decimals' => 2,'values' => null], self::utils()->_parse_column_type('decimal(6,2) unsigned') );
		$this->assertEquals( ['type' => 'numeric','length' => 6,'unsigned' => true,'decimals' => 2,'values' => null], self::utils()->_parse_column_type('numeric(6,2) unsigned') );
		$this->assertEquals( ['type' => 'real','length' => 6,'unsigned' => true,'decimals' => 2,'values' => null], self::utils()->_parse_column_type('real(6,2) unsigned') );
		$this->assertEquals( ['type' => 'float','length' => 6,'unsigned' => true,'decimals' => 2,'values' => null], self::utils()->_parse_column_type('float(6,2) unsigned') );
		$this->assertEquals( ['type' => 'double','length' => 6,'unsigned' => true,'decimals' => 2,'values' => null], self::utils()->_parse_column_type('double(6,2) unsigned') );
		$this->assertEquals( ['type' => 'char','length' => 6,'unsigned' => false,'decimals' => null,'values' => null], self::utils()->_parse_column_type('char(6)') );
		$this->assertEquals( ['type' => 'varchar','length' => 256,'unsigned' => false,'decimals' => null,'values' => null], self::utils()->_parse_column_type('varchar(256)') );
		$this->assertEquals( ['type' => 'tinytext','length' => null,'unsigned' => false,'decimals' => null,'values' => null], self::utils()->_parse_column_type('tinytext') );
		$this->assertEquals( ['type' => 'mediumtext','length' => null,'unsigned' => false,'decimals' => null,'values' => null], self::utils()->_parse_column_type('mediumtext') );
		$this->assertEquals( ['type' => 'longtext','length' => null,'unsigned' => false,'decimals' => null,'values' => null], self::utils()->_parse_column_type('longtext') );
		$this->assertEquals( ['type' => 'text','length' => null,'unsigned' => false,'decimals' => null,'values' => null], self::utils()->_parse_column_type('text') );
		$this->assertEquals( ['type' => 'tinyblob','length' => null,'unsigned' => false,'decimals' => null,'values' => null], self::utils()->_parse_column_type('tinyblob') );
		$this->assertEquals( ['type' => 'mediumblob','length' => null,'unsigned' => false,'decimals' => null,'values' => null], self::utils()->_parse_column_type('mediumblob') );
		$this->assertEquals( ['type' => 'longblob','length' => null,'unsigned' => false,'decimals' => null,'values' => null], self::utils()->_parse_column_type('longblob') );
		$this->assertEquals( ['type' => 'blob','length' => null,'unsigned' => false,'decimals' => null,'values' => null], self::utils()->_parse_column_type('blob') );
		$this->assertEquals( ['type' => 'binary','length' => null,'unsigned' => false,'decimals' => null,'values' => null], self::utils()->_parse_column_type('binary') );
		$this->assertEquals( ['type' => 'varbinary','length' => null,'unsigned' => false,'decimals' => null,'values' => null], self::utils()->_parse_column_type('varbinary') );
		$this->assertEquals( ['type' => 'timestamp','length' => null,'unsigned' => false,'decimals' => null,'values' => null], self::utils()->_parse_column_type('timestamp') );
		$this->assertEquals( ['type' => 'datetime','length' => null,'unsigned' => false,'decimals' => null,'values' => null], self::utils()->_parse_column_type('datetime') );
		$this->assertEquals( ['type' => 'date','length' => null,'unsigned' => false,'decimals' => null,'values' => null], self::utils()->_parse_column_type('date') );
		$this->assertEquals( ['type' => 'time','length' => null,'unsigned' => false,'decimals' => null,'values' => null], self::utils()->_parse_column_type('time') );
		$this->assertEquals( ['type' => 'year','length' => null,'unsigned' => false,'decimals' => null,'values' => null], self::utils()->_parse_column_type('year') );
		$this->assertEquals( ['type' => 'enum','length' => null,'unsigned' => false,'decimals' => null,'values' => ['0','1']], self::utils()->_parse_column_type('enum(\'0\',\'1\')') );
		$this->assertEquals( ['type' => 'set','length' => null,'unsigned' => false,'decimals' => null,'values' => ['0','1']], self::utils()->_parse_column_type('set(\'0\',\'1\')') );
	}

	public function test_column_exists() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$data = [['name' => 'id', 'type' => 'int', 'length' => 10]];
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table), $data) );
		$this->assertNotEmpty( self::utils()->table_exists($this->table_name($table)) );
		$this->assertNotEmpty( self::utils()->column_exists($this->table_name($table), 'id') );
		$this->assertFalse( self::utils()->column_exists($this->table_name($table), 'id33') );
	}
	public function test_column_info() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$col_info = ['name' => 'id', 'type' => 'int', 'length' => 10];
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table), [$col_info]) );
		$result = self::utils()->column_info($this->table_name($table), 'id');
		foreach (['name','type','length'] as $f) {
			$this->assertEquals( $col_info[$f], $result[$f] );
		}
	}
	public function test_add_column() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$col_info = ['name' => 'id', 'type' => 'int', 'length' => 10];
		$col_info2 = ['name' => 'id2', 'type' => 'int', 'length' => 8];
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table), [$col_info]) );
		$this->assertNotEmpty( self::utils()->column_exists($this->table_name($table), 'id') );
		$this->assertFalse( self::utils()->column_exists($this->table_name($table), 'id2') );
		$this->assertFalse( self::utils()->column_info($this->table_name($table), 'id2') );
		$this->assertNotEmpty( self::utils()->add_column($this->table_name($table), $col_info2) );
		$result = self::utils()->column_info($this->table_name($table), 'id2');
		foreach (['name','type','length'] as $f) {
			$this->assertEquals( $col_info2[$f], $result[$f] );
		}
	}
	public function test_drop_column() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$col_info = ['name' => 'id', 'type' => 'int', 'length' => 10];
		$col_info2 = ['name' => 'id2', 'type' => 'int', 'length' => 8];
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table), [$col_info, $col_info2]) );
		$this->assertNotEmpty( self::utils()->column_exists($this->table_name($table), 'id') );
		$this->assertNotEmpty( self::utils()->column_exists($this->table_name($table), 'id2') );
		$this->assertNotEmpty( self::utils()->drop_column($this->table_name($table), 'id2') );
		$this->assertFalse( self::utils()->column_exists($this->table_name($table), 'id2') );
	}
	public function test_rename_column() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$col_info = ['name' => 'id', 'type' => 'int', 'length' => 10];
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table), [$col_info]) );
		$this->assertNotEmpty( self::utils()->column_exists($this->table_name($table), 'id') );
		$this->assertFalse( self::utils()->column_exists($this->table_name($table), 'id2') );
		$this->assertNotEmpty( self::utils()->rename_column($this->table_name($table), 'id', 'id2') );
		$this->assertNotEmpty( self::utils()->column_exists($this->table_name($table), 'id2') );
	}
	public function test_alter_column() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$col_info = ['name' => 'id', 'type' => 'int', 'length' => 10];
		$col_info2 = ['name' => 'id2', 'type' => 'int', 'length' => 8];
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table), [$col_info, $col_info2]) );
		$this->assertNotEmpty( self::utils()->column_exists($this->table_name($table), 'id') );
		$this->assertEquals( '10', self::utils()->column_info_item($this->table_name($table), 'id', 'length') );
		$this->assertNotEmpty( self::utils()->alter_column($this->table_name($table), 'id', ['length' => 8]) );
		$this->assertEquals( '8', self::utils()->column_info_item($this->table_name($table), 'id', 'length') );

		$this->assertEquals( ['id', 'id2'], array_keys(self::utils()->table_get_columns($this->table_name($table))) );
		$this->assertNotEmpty( self::utils()->alter_column($this->table_name($table), 'id2', ['first' => true]) );
		$this->assertEquals( ['id2', 'id'], array_keys(self::utils()->table_get_columns($this->table_name($table))) );
		$this->assertNotEmpty( self::utils()->alter_column($this->table_name($table), 'id2', ['after' => 'id']) );
		$this->assertEquals( ['id', 'id2'], array_keys(self::utils()->table_get_columns($this->table_name($table))) );
	}

	public function test_list_indexes() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$data = [
			['name' => 'id', 'type' => 'int', 'length' => 10, 'auto_inc' => true],
			['name' => 'primary', 'key' => 'primary', 'key_cols' => 'id'],
		];
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table), $data) );
		$this->assertNotEmpty( self::utils()->table_exists($this->table_name($table)) );
		$expected = [
			'PRIMARY' => ['name' => 'PRIMARY', 'type' => 'primary','columns' => ['id']],
		];
		$this->assertEquals( $expected, self::utils()->list_indexes($this->table_name($table)) );
	}
	public function test_index_info() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$data = [
			['name' => 'id', 'type' => 'int', 'length' => 10, 'auto_inc' => true],
			['name' => 'primary', 'key' => 'primary', 'key_cols' => 'id'],
		];
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table), $data) );
		$this->assertNotEmpty( self::utils()->table_exists($this->table_name($table)) );
		$this->assertEquals( ['name' => 'PRIMARY', 'type' => 'primary', 'columns' => ['id']], self::utils()->index_info($this->table_name($table), 'PRIMARY') );
	}
	public function test_index_exists() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$data = [
			['name' => 'id', 'type' => 'int', 'length' => 10, 'auto_inc' => true],
			['name' => 'primary', 'key' => 'primary', 'key_cols' => 'id'],
		];
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table), $data) );
		$this->assertNotEmpty( self::utils()->table_exists($this->table_name($table)) );
		$this->assertNotEmpty( self::utils()->index_exists($this->table_name($table), 'PRIMARY') );
	}
	public function test_drop_index() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$data = [
			['name' => 'id', 'type' => 'int', 'length' => 10],
			['key' => 'primary', 'key_cols' => 'id'],
		];
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table), $data) );
		$this->assertNotEmpty( self::utils()->table_exists($this->table_name($table)) );
		$this->assertNotEmpty( self::utils()->index_exists($this->table_name($table), 'PRIMARY') );
		$this->assertNotEmpty( self::utils()->drop_index($this->table_name($table), 'PRIMARY') );
		$this->assertFalse( self::utils()->index_exists($this->table_name($table), 'PRIMARY') );
	}
	public function test_add_index() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$data = [
			['name' => 'id', 'type' => 'int', 'length' => 10],
		];
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table), $data) );
		$this->assertNotEmpty( self::utils()->table_exists($this->table_name($table)) );
		$this->assertFalse( self::utils()->index_exists($this->table_name($table), 'PRIMARY') );
		$this->assertNotEmpty( self::utils()->add_index($this->table_name($table), 'PRIMARY', ['id'], ['type' => 'primary']) );
		$this->assertNotEmpty( self::utils()->index_exists($this->table_name($table), 'PRIMARY') );
	}
	public function test_update_index() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$data = [
			['name' => 'id', 'type' => 'int', 'length' => 10],
			['name' => 'id2', 'type' => 'int', 'length' => 10],
		];
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table), $data) );
		$this->assertNotEmpty( self::utils()->table_exists($this->table_name($table)) );
		$this->assertNotEmpty( self::utils()->add_index($this->table_name($table), 'PRIMARY', ['id'], ['type' => 'primary']) );
		$this->assertEquals( ['name' => 'PRIMARY', 'type' => 'primary', 'columns' => ['id']], self::utils()->index_info($this->table_name($table), 'PRIMARY') );
		$this->assertNotEmpty( self::utils()->update_index($this->table_name($table), 'PRIMARY', ['id2'], ['type' => 'primary']) );
		$this->assertEquals( ['name' => 'PRIMARY', 'type' => 'primary', 'columns' => ['id2']], self::utils()->index_info($this->table_name($table), 'PRIMARY') );
	}

	public function test_list_foreign_keys() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table1 = self::utils()->db->DB_PREFIX. __FUNCTION__.'_1';
		$table2 = self::utils()->db->DB_PREFIX. __FUNCTION__.'_2';
		$data = [
			['name' => 'id', 'type' => 'int', 'length' => 10],
			['name' => 'primary', 'key' => 'primary', 'key_cols' => 'id'],
		];
		$fkey = 'fkey_'.__FUNCTION__;
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table1), $data) );
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table2), $data) );
		$this->assertEmpty( self::utils()->list_foreign_keys($this->table_name($table1)) );
		$this->assertNotEmpty( self::utils()->add_foreign_key($this->table_name($table1), $fkey, ['id'], $this->table_name($table2), ['id']) );
		$expected = [
			$fkey => ['name' => $fkey, 'local' => 'id', 'table' => $table2, 'foreign' => 'id'],
		];
		$this->assertEquals( $expected, self::utils()->list_foreign_keys($this->table_name($table1)) );
	}
	public function test_foreign_key_info() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table1 = self::utils()->db->DB_PREFIX. __FUNCTION__.'_1';
		$table2 = self::utils()->db->DB_PREFIX. __FUNCTION__.'_2';
		$data = [
			['name' => 'id', 'type' => 'int', 'length' => 10],
			['name' => 'primary', 'key' => 'primary', 'key_cols' => 'id'],
		];
		$fkey = 'fkey_'.__FUNCTION__;
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table1), $data) );
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table2), $data) );
		$this->assertEmpty( self::utils()->foreign_key_info($this->table_name($table1), $fkey) );
		$this->assertNotEmpty( self::utils()->add_foreign_key($this->table_name($table1), $fkey, ['id'], $this->table_name($table2), ['id']) );
		$this->assertEquals( ['name' => $fkey, 'local' => 'id', 'table' => $table2, 'foreign' => 'id'], self::utils()->foreign_key_info($this->table_name($table1), $fkey) );
	}
	public function test_foreign_key_exists() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table1 = self::utils()->db->DB_PREFIX. __FUNCTION__.'_1';
		$table2 = self::utils()->db->DB_PREFIX. __FUNCTION__.'_2';
		$data = [
			['name' => 'id', 'type' => 'int', 'length' => 10],
			['name' => 'primary', 'key' => 'primary', 'key_cols' => 'id'],
		];
		$fkey = 'fkey_'.__FUNCTION__;
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table1), $data) );
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table2), $data) );
		$this->assertFalse( self::utils()->foreign_key_exists($this->table_name($table1), $fkey) );
		$this->assertNotEmpty( self::utils()->add_foreign_key($this->table_name($table1), $fkey, ['id'], $this->table_name($table2), ['id']) );
		$this->assertNotEmpty( self::utils()->foreign_key_exists($this->table_name($table1), $fkey) );
	}
	public function test_drop_foreign_key() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table1 = self::utils()->db->DB_PREFIX. __FUNCTION__.'_1';
		$table2 = self::utils()->db->DB_PREFIX. __FUNCTION__.'_2';
		$data = [
			['name' => 'id', 'type' => 'int', 'length' => 10],
			['name' => 'primary', 'key' => 'primary', 'key_cols' => 'id'],
		];
		$fkey = 'fkey_'.__FUNCTION__;
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table1), $data) );
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table2), $data) );
		$this->assertFalse( self::utils()->foreign_key_exists($this->table_name($table1), $fkey) );
		$this->assertNotEmpty( self::utils()->add_foreign_key($this->table_name($table1), $fkey, ['id'], $this->table_name($table2), ['id']) );
		$this->assertNotEmpty( self::utils()->foreign_key_exists($this->table_name($table1), $fkey) );
		$this->assertNotEmpty( self::utils()->drop_foreign_key($this->table_name($table1), $fkey) );
		$this->assertFalse( self::utils()->foreign_key_exists($this->table_name($table1), $fkey) );
	}
	public function test_add_foreign_key() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table1 = self::utils()->db->DB_PREFIX. __FUNCTION__.'_1';
		$table2 = self::utils()->db->DB_PREFIX. __FUNCTION__.'_2';
		$data = [
			['name' => 'id', 'type' => 'int', 'length' => 10],
			['name' => 'primary', 'key' => 'primary', 'key_cols' => 'id'],
		];
		$fkey = 'fkey_'.__FUNCTION__;
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table1), $data) );
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table2), $data) );
		$this->assertEmpty( self::utils()->foreign_key_info($this->table_name($table1), $fkey) );
		$this->assertNotEmpty( self::utils()->add_foreign_key($this->table_name($table1), $fkey, ['id'], $this->table_name($table2), ['id']) );
		$this->assertEquals( ['name' => $fkey, 'local' => 'id', 'table' => $table2, 'foreign' => 'id'], self::utils()->foreign_key_info($this->table_name($table1), $fkey) );
	}
	public function test_update_foreign_key() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table1 = self::utils()->db->DB_PREFIX. __FUNCTION__.'_1';
		$table2 = self::utils()->db->DB_PREFIX. __FUNCTION__.'_2';
		$data = [
			['name' => 'id', 'type' => 'int', 'length' => 10],
			['name' => 'id2', 'type' => 'int', 'length' => 10],
			['name' => 'primary', 'key' => 'primary', 'key_cols' => 'id'],
			['name' => 'unique', 'key' => 'unique', 'key_cols' => 'id2'],
		];
		$fkey = 'fkey_'.__FUNCTION__;
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table1), $data) );
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table2), $data) );
		$this->assertNotEmpty( self::utils()->add_foreign_key($this->table_name($table1), $fkey, ['id'], $this->table_name($table2), ['id']) );
		$this->assertEquals( ['name' => $fkey, 'local' => 'id', 'table' => $table2, 'foreign' => 'id'], self::utils()->foreign_key_info($this->table_name($table1), $fkey) );
		$this->assertNotEmpty( self::utils()->update_foreign_key($this->table_name($table1), $fkey, ['id2'], $this->table_name($table2), ['id2']) );
		$this->assertEquals( ['name' => $fkey, 'local' => 'id2', 'table' => $table2, 'foreign' => 'id2'], self::utils()->foreign_key_info($this->table_name($table1), $fkey) );
	}

	public function test_list_views() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }

		$view = self::utils()->db->DB_PREFIX. 'view_'.__FUNCTION__;
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$data = [['name' => 'id', 'type' => 'int', 'length' => 10]];
		$this->assertEmpty( self::utils()->list_views($this->db_name()) );
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table), $data) );
		$this->assertNotEmpty( self::utils()->table_exists($this->table_name($table)) );
		$this->assertNotEmpty( self::utils()->create_view($this->db_name().'.'.$view, 'SELECT * FROM '.$this->table_name($table)) );
		$this->assertNotEmpty( self::utils()->list_views($this->db_name()) );
	}
	public function test_view_exists() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$view = self::utils()->db->DB_PREFIX. 'view_'.__FUNCTION__;
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$data = [['name' => 'id', 'type' => 'int', 'length' => 10]];
		$this->assertFalse( self::utils()->view_exists($this->db_name().'.'.$view) );
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table), $data) );
		$this->assertNotEmpty( self::utils()->table_exists($this->table_name($table)) );
		$this->assertNotEmpty( self::utils()->create_view($this->db_name().'.'.$view, 'SELECT * FROM '.$this->table_name($table)) );
		$this->assertNotEmpty( self::utils()->view_exists($this->db_name().'.'.$view) );
	}
	public function test_view_info() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$view = self::utils()->db->DB_PREFIX. 'view_'.__FUNCTION__;
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$data = [['name' => 'id', 'type' => 'int', 'length' => 10]];
		$this->assertFalse( self::utils()->view_exists($this->db_name().'.'.$view) );
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table), $data) );
		$this->assertNotEmpty( self::utils()->table_exists($this->table_name($table)) );
		$this->assertNotEmpty( self::utils()->create_view($this->db_name().'.'.$view, 'SELECT * FROM '.$this->table_name($table)) );
		$this->assertNotEmpty( self::utils()->view_info($this->db_name().'.'.$view) );
	}
	public function test_drop_view() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$view = self::utils()->db->DB_PREFIX. 'view_'.__FUNCTION__;
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$data = [['name' => 'id', 'type' => 'int', 'length' => 10]];
		$this->assertFalse( self::utils()->view_exists($this->db_name().'.'.$view) );
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table), $data) );
		$this->assertNotEmpty( self::utils()->table_exists($this->table_name($table)) );
		$this->assertNotEmpty( self::utils()->create_view($this->db_name().'.'.$view, 'SELECT * FROM '.$this->table_name($table)) );
		$this->assertNotEmpty( self::utils()->view_exists($this->db_name().'.'.$view) );
		$this->assertNotEmpty( self::utils()->drop_view($this->db_name().'.'.$view) );
		$this->assertFalse( self::utils()->view_exists($this->db_name().'.'.$view) );
	}
	public function test_create_view() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$view = self::utils()->db->DB_PREFIX. 'view_'.__FUNCTION__;
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$data = [['name' => 'id', 'type' => 'int', 'length' => 10]];
		$this->assertFalse( self::utils()->view_exists($this->db_name().'.'.$view) );
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table), $data) );
		$this->assertNotEmpty( self::utils()->table_exists($this->table_name($table)) );
		$this->assertNotEmpty( self::utils()->create_view($this->db_name().'.'.$view, 'SELECT * FROM '.$this->table_name($table)) );
		$this->assertNotEmpty( self::utils()->view_exists($this->db_name().'.'.$view) );
	}

	public function test_list_triggers() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }

		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table), [['name' => 'id', 'type' => 'int', 'length' => 10]]) );

		$trg = self::utils()->db->DB_PREFIX. 'trg_'.__FUNCTION__;
		$triggers = self::utils()->list_triggers($this->db_name());
		if (empty($triggers)) {
			$sql = 'CREATE TRIGGER '.$this->db_name().'.'.$trg.' BEFORE INSERT ON '.$this->table_name($table).' FOR EACH ROW SET @sum = @sum + NEW.id';
			$this->assertNotEmpty( self::utils()->db->query($sql) );
		}
		$this->assertNotEmpty( self::utils()->list_triggers($this->db_name()) );
	}
	public function test_trigger_exists() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table), [['name' => 'id', 'type' => 'int', 'length' => 10]]) );
		$trg = self::utils()->db->DB_PREFIX. 'trg_'.__FUNCTION__;
		$this->assertFalse( self::utils()->trigger_exists($this->db_name().'.'.$trg) );
		$sql = 'CREATE TRIGGER '.$this->db_name().'.'.$trg.' BEFORE INSERT ON '.$this->table_name($table).' FOR EACH ROW SET @sum = @sum + NEW.id';
		$this->assertNotEmpty( self::utils()->db->query($sql) );
		$this->assertNotEmpty( self::utils()->trigger_exists($this->db_name().'.'.$trg) );
	}
	public function test_trigger_info() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table), [['name' => 'id', 'type' => 'int', 'length' => 10]]) );
		$trg = self::utils()->db->DB_PREFIX. 'trg_'.__FUNCTION__;
		$sql = 'CREATE TRIGGER '.$this->db_name().'.'.$trg.' BEFORE INSERT ON '.$this->table_name($table).' FOR EACH ROW SET @sum = @sum + NEW.id';
		$this->assertNotEmpty( self::utils()->db->query($sql) );
		$expected = [
			'name' => $trg,
			'table' => $table,
			'event' => 'INSERT',
			'timing' => 'BEFORE',
			'statement' => 'SET @sum = @sum + NEW.id',
			'definer' => NULL,
		];
		$result = self::utils()->trigger_info($this->db_name().'.'.$trg);
		foreach ($expected as $k => $_expected) {
			$this->assertEquals( $_expected, $result[$k] );
		}
	}
	public function test_drop_trigger() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table), [['name' => 'id', 'type' => 'int', 'length' => 10]]) );
		$trg = self::utils()->db->DB_PREFIX. 'trg_'.__FUNCTION__;
		$this->assertFalse( self::utils()->trigger_exists($this->db_name().'.'.$trg) );
		$sql = 'CREATE TRIGGER '.$this->db_name().'.'.$trg.' BEFORE INSERT ON '.$this->table_name($table).' FOR EACH ROW SET @sum = @sum + NEW.id';
		$this->assertNotEmpty( self::utils()->db->query($sql) );
		$this->assertNotEmpty( self::utils()->trigger_exists($this->db_name().'.'.$trg) );
		$this->assertNotEmpty( self::utils()->drop_trigger($this->db_name().'.'.$trg) );
		$this->assertFalse( self::utils()->trigger_exists($this->db_name().'.'.$trg) );
	}
	public function test_create_trigger() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table), [['name' => 'id', 'type' => 'int', 'length' => 10]]) );
		$trg = self::utils()->db->DB_PREFIX. 'trg_'.__FUNCTION__;
		$this->assertNotEmpty( self::utils()->create_trigger($trg, $this->table_name($table), 'before', 'insert', 'SET @sum = @sum + NEW.id') );
		$expected = [
			'name' => $trg,
			'table' => $table,
			'event' => 'INSERT',
			'timing' => 'BEFORE',
			'statement' => 'SET @sum = @sum + NEW.id',
			'definer' => NULL,
		];
		$result = self::utils()->trigger_info($this->db_name().'.'.$trg);
		foreach ($expected as $k => $_expected) {
			$this->assertEquals( $_expected, $result[$k] );
		}
	}

	public function test_escape_database_name() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertEquals( '`test_db`', self::utils()->_escape_database_name('test_db') );
	}
	public function test_escape_table_name() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertEquals( '', self::utils()->_escape_table_name('') );
		$this->assertEquals( '`'.self::utils()->db->DB_PREFIX.'test_table`', self::utils()->_escape_table_name('test_table') );
		$this->assertEquals( '`test_db`.`'.self::utils()->db->DB_PREFIX.'test_table`', self::utils()->_escape_table_name('test_db.test_table') );
	}
	public function test_escape_key() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertEquals( '`test_key`', self::utils()->_escape_key('test_key') );
	}
	public function test_escape_val() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertEquals( '\'test_val\'', self::utils()->_escape_val('test_val') );
	}
	public function test_escape_fields() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$fields = ['id1','id2','test_field'];
		$expected = ['`id1`','`id2`','`test_field`'];
		$this->assertEquals( $expected, self::utils()->_escape_fields($fields) );
	}
	public function test__es() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertEquals( 'hello world', self::utils()->_es('hello world') );
		$this->assertEquals( 'hello\\\'world\\\'', self::utils()->_es('hello\'world\'') );
	}
}
