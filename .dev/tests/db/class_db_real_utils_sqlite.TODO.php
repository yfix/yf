<?php

require_once __DIR__.'/db_real_abstract.php';

/**
 * @requires extension sqlite3
 */
class class_db_real_utils_sqlite_test extends db_real_abstract {
	public static function setUpBeforeClass() {
		self::$_bak['DB_DRIVER'] = self::$DB_DRIVER;
		self::$DB_DRIVER = 'sqlite';
		self::_connect(array('name' => STORAGE_PATH. DB_NAME.'.db'));
	}
	public static function tearDownAfterClass() {
		$db_file = STORAGE_PATH. DB_NAME.'.db';
		if (file_exists($db_file)) {
			unlink($db_file);
		}
		self::$DB_DRIVER = self::$_bak['DB_DRIVER'];
	}
	public function _need_skip_test($name) {
		if (substr($name, 0, 5) !== 'test_') {
			return false;
		}
		$short = substr($name, 5);
		return in_array($short, array(
		));
	}
	public static function db_name() {
		return '';
	}
	public function table_name($name) {
		return $name;
	}


/*
	public function test_demo() {
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$this->assertTrue( (bool)self::utils()->db->query('CREATE TABLE '.$this->table_name($table).' (id INTEGER PRIMARY KEY, id2 INTEGER)') );
		self::utils()->db->query('CREATE UNIQUE INDEX IF NOT EXISTS MyUniqueIndexName ON '.$table.' (id2)');

#		$sql = 'SELECT * FROM sqlite_master WHERE type = "table" AND name <> "sqlite_sequence"';
		$sql = 'PRAGMA table_info('.$table.')';
		print_r(self::db()->get_all($sql));

		$sql = 'PRAGMA index_list('.$table.')';
		print_r(self::db()->get_all($sql));
	}
*/

	public function test_list_tables() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertEquals( array(), self::utils()->list_tables($this->db_name()) );
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$this->assertTrue( (bool)self::utils()->db->query('CREATE TABLE '.$this->table_name($table).'(id INTEGER PRIMARY KEY)') );
		$this->assertEquals( array($table => $table), self::utils()->list_tables($this->db_name()) );
		$this->assertNotEmpty( self::utils()->db->query('DROP TABLE '.$this->table_name($table).'') );
		$this->assertEquals( array(), self::utils()->list_tables($this->db_name()) );
	}
	public function test_table_exists() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$this->assertFalse( self::utils()->table_exists($this->table_name($table)) );
		$this->assertNotEmpty( self::utils()->db->query('CREATE TABLE '.$this->table_name($table).'(id INTEGER PRIMARY KEY)') );
		$this->assertNotEmpty( self::utils()->table_exists($this->table_name($table)) );
		$this->assertNotEmpty( self::utils()->db->query('DROP TABLE '.$this->table_name($table).'') );
		$this->assertFalse( self::utils()->table_exists($this->table_name($table)) );
	}
	public function test_drop_table() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$this->assertFalse( self::utils()->table_exists($this->table_name($table)) );
		$this->assertNotEmpty( self::utils()->db->query('CREATE TABLE '.$this->table_name($table).' (id INTEGER PRIMARY KEY)') );
		$this->assertNotEmpty( self::utils()->table_exists($this->table_name($table)) );
		$this->assertNotEmpty( self::utils()->drop_table($this->table_name($table)) );
		$this->assertFalse( self::utils()->table_exists($this->table_name($table)) );
	}
	public function test_table_get_columns() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$data = array(
			array('name' => 'id', 'type' => 'int', 'length' => 10, 'auto_inc' => true),
			array('name' => 'name', 'type' => 'varchar', 'length' => 255, 'default' => '', 'not_null' => true),
		);
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table), $data) );
		$this->assertNotEmpty( self::utils()->table_exists($this->table_name($table)) );
		$expected = array(
			'id' => array(
				'name' => 'id','type' => 'int','length' => '','unsigned' => false,'collation' => NULL,'null' => false,
				'default' => NULL,'auto_inc' => true,'is_primary' => true,'is_unique' => true,'type_raw' => 'INT UNSIGNED',
			),
			'name' => array(
				'name' => 'name','type' => 'varchar','length' => '','unsigned' => false,'collation' => null,'null' => false,
				'default' => '\'\'','auto_inc' => false,'is_primary' => false,'is_unique' => false,'type_raw' => 'VARCHAR',
			),
		);
		$this->assertEquals( $expected, self::utils()->table_get_columns($this->table_name($table)) );
	}
	public function test_table_info() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$data = array(
			array('name' => 'id', 'type' => 'int', 'length' => 10, 'auto_inc' => true),
			array('name' => 'name', 'type' => 'varchar', 'length' => 255, 'default' => '', 'not_null' => true),
		);
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table), $data) );
		$this->assertNotEmpty( self::utils()->table_exists($this->table_name($table)) );
		$expected_columns = array(
			'id' => array(
				'name' => 'id','type' => 'int','length' => '','unsigned' => false,'collation' => NULL,'null' => false,
				'default' => NULL,'auto_inc' => true,'is_primary' => true,'is_unique' => true,'type_raw' => 'INT UNSIGNED',
			),
			'name' => array(
				'name' => 'name','type' => 'varchar','length' => '','unsigned' => false,'collation' => null,'null' => false,
				'default' => '\'\'','auto_inc' => false,'is_primary' => false,'is_unique' => false,'type_raw' => 'VARCHAR',
			),
		);
		$expected = array(
			'name' => $table,
			'db_name' => null,
			'columns' => $expected_columns,
			'row_format' => null,
			'collation' => null,
			'engine' => null,
			'rows' => null,
			'data_size' => null,
			'auto_inc' => null,
			'comment' => null,
			'create_time' => null,
			'update_time' => null,
			'charset' => null,
		);
		$received = self::utils()->table_info($this->table_name($table));
		$this->assertEquals( $expected, $received );
	}
	public function test_rename_table() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$data = array(
			array('name' => 'id', 'type' => 'int', 'length' => 10),
		);
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
		$data = array(array('name' => 'id', 'type' => 'int', 'length' => 10));
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table), $data) );
		$this->assertNotEmpty( self::utils()->table_exists($this->table_name($table)) );
		$to_insert = array(
			1 => array('id' => 1),
			2 => array('id' => 2),
		);
		$this->assertNotEmpty( self::utils()->db->insert($this->table_name($table), $to_insert) );
		$this->assertEquals( $to_insert, self::utils()->db->from($this->table_name($table))->get_all() );
		$this->assertNotEmpty( self::utils()->truncate_table($this->table_name($table)) );
	}
	public function test__compile_create_table() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$in = array(
			array('name' => 'id', 'type' => 'int', 'length' => 10, 'auto_inc' => true),
			array('name' => 'name', 'type' => 'varchar', 'length' => 255, 'default' => '', 'not_null' => true),
		);
		$expected = 
			'`id` INT UNSIGNED NOT NULL PRIMARY KEY,'.PHP_EOL
			.'`name` VARCHAR NOT NULL DEFAULT \'\'';
		$this->assertEquals( $expected, self::utils()->_compile_create_table($in) );
	}
	public function test_create_table() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$data = array(
			array('name' => 'id', 'type' => 'int', 'length' => 10, 'auto_inc' => true),
			array('name' => 'name', 'type' => 'varchar', 'length' => 255, 'default' => '', 'not_null' => true),
		);
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table), $data) );
		$this->assertNotEmpty( self::utils()->table_exists($this->table_name($table)) );
	}

	public function test_column_exists() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$data = array(array('name' => 'id', 'type' => 'int', 'length' => 10));
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table), $data) );
		$this->assertNotEmpty( self::utils()->table_exists($this->table_name($table)) );
		$this->assertNotEmpty( self::utils()->column_exists($this->table_name($table), 'id') );
		$this->assertFalse( self::utils()->column_exists($this->table_name($table), 'id33') );
	}
	public function test_column_info() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$col_info = array('name' => 'id', 'type' => 'int');
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table), array($col_info)) );
		$result = self::utils()->column_info($this->table_name($table), 'id');
		foreach (array('name','type') as $f) {
			$this->assertEquals( $col_info[$f], $result[$f] );
		}
	}
	public function test_add_column() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$col_info = array('name' => 'id', 'type' => 'int');
		$col_info2 = array('name' => 'id2', 'type' => 'int');
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table), array($col_info)) );
		$this->assertNotEmpty( self::utils()->column_exists($this->table_name($table), 'id') );
		$this->assertFalse( self::utils()->column_exists($this->table_name($table), 'id2') );
		$this->assertFalse( self::utils()->column_info($this->table_name($table), 'id2') );
		$this->assertNotEmpty( self::utils()->add_column($this->table_name($table), $col_info2) );
		$result = self::utils()->column_info($this->table_name($table), 'id2');
		foreach (array('name','type') as $f) {
			$this->assertEquals( $col_info2[$f], $result[$f] );
		}
	}
/*
	public function test_drop_column() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$col_info = array('name' => 'id', 'type' => 'int');
		$col_info2 = array('name' => 'id2', 'type' => 'int');
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table), array($col_info, $col_info2)) );
		$this->assertNotEmpty( self::utils()->column_exists($this->table_name($table), 'id') );
		$this->assertNotEmpty( self::utils()->column_exists($this->table_name($table), 'id2') );
		$this->assertNotEmpty( self::utils()->drop_column($this->table_name($table), 'id2') );
		$this->assertFalse( self::utils()->column_exists($this->table_name($table), 'id2') );
	}
	public function test_rename_column() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$col_info = array('name' => 'id', 'type' => 'int');
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table), array($col_info)) );
		$this->assertNotEmpty( self::utils()->column_exists($this->table_name($table), 'id') );
		$this->assertFalse( self::utils()->column_exists($this->table_name($table), 'id2') );
		$this->assertNotEmpty( self::utils()->rename_column($this->table_name($table), 'id', 'id2') );
		$this->assertNotEmpty( self::utils()->column_exists($this->table_name($table), 'id2') );
	}
*/
	public function test_list_indexes() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$data = array(
			array('name' => 'id', 'type' => 'int', 'auto_inc' => true),
		);
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table), $data) );
		$this->assertNotEmpty( self::utils()->table_exists($this->table_name($table)) );
		$expected = array(
			'PRIMARY' => array('name' => 'PRIMARY', 'type' => 'primary','columns' => array('id')),
		);
		$this->assertEquals( $expected, self::utils()->list_indexes($this->table_name($table)) );
	}
	public function test_index_info() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$data = array(
			array('name' => 'id', 'type' => 'int', 'auto_inc' => true),
		);
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table), $data) );
		$this->assertNotEmpty( self::utils()->table_exists($this->table_name($table)) );
		$this->assertEquals( array('name' => 'PRIMARY', 'type' => 'primary', 'columns' => array('id')), self::utils()->index_info($this->table_name($table), 'PRIMARY') );
	}
	public function test_index_exists() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$data = array(
			array('name' => 'id', 'type' => 'int', 'length' => 10, 'auto_inc' => true),
			array('name' => 'primary', 'key' => 'primary', 'key_cols' => 'id'),
		);
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table), $data) );
		$this->assertNotEmpty( self::utils()->table_exists($this->table_name($table)) );
		$this->assertNotEmpty( self::utils()->index_exists($this->table_name($table), 'PRIMARY') );
	}
	public function test_drop_index() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$data = array(
			array('name' => 'id', 'type' => 'int', 'length' => 10),
			array('key' => 'primary', 'key_cols' => 'id'),
		);
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table), $data) );
		$this->assertNotEmpty( self::utils()->table_exists($this->table_name($table)) );
		$this->assertNotEmpty( self::utils()->index_exists($this->table_name($table), 'PRIMARY') );
		$this->assertNotEmpty( self::utils()->drop_index($this->table_name($table), 'PRIMARY') );
		$this->assertFalse( self::utils()->index_exists($this->table_name($table), 'PRIMARY') );
	}
	public function test_add_index() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$data = array(
			array('name' => 'id', 'type' => 'int', 'length' => 10),
		);
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table), $data) );
		$this->assertNotEmpty( self::utils()->table_exists($this->table_name($table)) );
		$this->assertFalse( self::utils()->index_exists($this->table_name($table), 'PRIMARY') );
		$this->assertNotEmpty( self::utils()->add_index($this->table_name($table), 'PRIMARY', array('id'), array('type' => 'primary')) );
		$this->assertNotEmpty( self::utils()->index_exists($this->table_name($table), 'PRIMARY') );
	}
	public function test_update_index() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$data = array(
			array('name' => 'id', 'type' => 'int', 'length' => 10),
			array('name' => 'id2', 'type' => 'int', 'length' => 10),
		);
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table), $data) );
		$this->assertNotEmpty( self::utils()->table_exists($this->table_name($table)) );
		$this->assertNotEmpty( self::utils()->add_index($this->table_name($table), 'PRIMARY', array('id'), array('type' => 'primary')) );
		$this->assertEquals( array('name' => 'PRIMARY', 'type' => 'primary', 'columns' => array('id')), self::utils()->index_info($this->table_name($table), 'PRIMARY') );
		$this->assertNotEmpty( self::utils()->update_index($this->table_name($table), 'PRIMARY', array('id2'), array('type' => 'primary')) );
		$this->assertEquals( array('name' => 'PRIMARY', 'type' => 'primary', 'columns' => array('id2')), self::utils()->index_info($this->table_name($table), 'PRIMARY') );
	}

	public function test_list_foreign_keys() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table1 = self::utils()->db->DB_PREFIX. __FUNCTION__.'_1';
		$table2 = self::utils()->db->DB_PREFIX. __FUNCTION__.'_2';
		$data = array(
			array('name' => 'id', 'type' => 'int', 'length' => 10),
			array('name' => 'primary', 'key' => 'primary', 'key_cols' => 'id'),
		);
		$fkey = 'fkey_'.__FUNCTION__;
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table1), $data) );
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table2), $data) );
		$this->assertEmpty( self::utils()->list_foreign_keys($this->table_name($table1)) );
		$this->assertNotEmpty( self::utils()->add_foreign_key($this->table_name($table1), $fkey, array('id'), $this->table_name($table2), array('id')) );
		$expected = array(
			$fkey => array('name' => $fkey, 'local' => 'id', 'table' => $table2, 'foreign' => 'id'),
		);
		$this->assertEquals( $expected, self::utils()->list_foreign_keys($this->table_name($table1)) );
	}
	public function test_foreign_key_info() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table1 = self::utils()->db->DB_PREFIX. __FUNCTION__.'_1';
		$table2 = self::utils()->db->DB_PREFIX. __FUNCTION__.'_2';
		$data = array(
			array('name' => 'id', 'type' => 'int', 'length' => 10),
			array('name' => 'primary', 'key' => 'primary', 'key_cols' => 'id'),
		);
		$fkey = 'fkey_'.__FUNCTION__;
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table1), $data) );
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table2), $data) );
		$this->assertEmpty( self::utils()->foreign_key_info($this->table_name($table1), $fkey) );
		$this->assertNotEmpty( self::utils()->add_foreign_key($this->table_name($table1), $fkey, array('id'), $this->table_name($table2), array('id')) );
		$this->assertEquals( array('name' => $fkey, 'local' => 'id', 'table' => $table2, 'foreign' => 'id'), self::utils()->foreign_key_info($this->table_name($table1), $fkey) );
	}
	public function test_foreign_key_exists() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table1 = self::utils()->db->DB_PREFIX. __FUNCTION__.'_1';
		$table2 = self::utils()->db->DB_PREFIX. __FUNCTION__.'_2';
		$data = array(
			array('name' => 'id', 'type' => 'int', 'length' => 10),
			array('name' => 'primary', 'key' => 'primary', 'key_cols' => 'id'),
		);
		$fkey = 'fkey_'.__FUNCTION__;
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table1), $data) );
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table2), $data) );
		$this->assertFalse( self::utils()->foreign_key_exists($this->table_name($table1), $fkey) );
		$this->assertNotEmpty( self::utils()->add_foreign_key($this->table_name($table1), $fkey, array('id'), $this->table_name($table2), array('id')) );
		$this->assertNotEmpty( self::utils()->foreign_key_exists($this->table_name($table1), $fkey) );
	}
	public function test_drop_foreign_key() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table1 = self::utils()->db->DB_PREFIX. __FUNCTION__.'_1';
		$table2 = self::utils()->db->DB_PREFIX. __FUNCTION__.'_2';
		$data = array(
			array('name' => 'id', 'type' => 'int', 'length' => 10),
			array('name' => 'primary', 'key' => 'primary', 'key_cols' => 'id'),
		);
		$fkey = 'fkey_'.__FUNCTION__;
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table1), $data) );
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table2), $data) );
		$this->assertFalse( self::utils()->foreign_key_exists($this->table_name($table1), $fkey) );
		$this->assertNotEmpty( self::utils()->add_foreign_key($this->table_name($table1), $fkey, array('id'), $this->table_name($table2), array('id')) );
		$this->assertNotEmpty( self::utils()->foreign_key_exists($this->table_name($table1), $fkey) );
		$this->assertNotEmpty( self::utils()->drop_foreign_key($this->table_name($table1), $fkey) );
		$this->assertFalse( self::utils()->foreign_key_exists($this->table_name($table1), $fkey) );
	}
	public function test_add_foreign_key() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table1 = self::utils()->db->DB_PREFIX. __FUNCTION__.'_1';
		$table2 = self::utils()->db->DB_PREFIX. __FUNCTION__.'_2';
		$data = array(
			array('name' => 'id', 'type' => 'int', 'length' => 10),
			array('name' => 'primary', 'key' => 'primary', 'key_cols' => 'id'),
		);
		$fkey = 'fkey_'.__FUNCTION__;
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table1), $data) );
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table2), $data) );
		$this->assertEmpty( self::utils()->foreign_key_info($this->table_name($table1), $fkey) );
		$this->assertNotEmpty( self::utils()->add_foreign_key($this->table_name($table1), $fkey, array('id'), $this->table_name($table2), array('id')) );
		$this->assertEquals( array('name' => $fkey, 'local' => 'id', 'table' => $table2, 'foreign' => 'id'), self::utils()->foreign_key_info($this->table_name($table1), $fkey) );
	}
	public function test_update_foreign_key() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table1 = self::utils()->db->DB_PREFIX. __FUNCTION__.'_1';
		$table2 = self::utils()->db->DB_PREFIX. __FUNCTION__.'_2';
		$data = array(
			array('name' => 'id', 'type' => 'int', 'length' => 10),
			array('name' => 'id2', 'type' => 'int', 'length' => 10),
			array('name' => 'primary', 'key' => 'primary', 'key_cols' => 'id'),
			array('name' => 'unique', 'key' => 'unique', 'key_cols' => 'id2'),
		);
		$fkey = 'fkey_'.__FUNCTION__;
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table1), $data) );
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table2), $data) );
		$this->assertNotEmpty( self::utils()->add_foreign_key($this->table_name($table1), $fkey, array('id'), $this->table_name($table2), array('id')) );
		$this->assertEquals( array('name' => $fkey, 'local' => 'id', 'table' => $table2, 'foreign' => 'id'), self::utils()->foreign_key_info($this->table_name($table1), $fkey) );
		$this->assertNotEmpty( self::utils()->update_foreign_key($this->table_name($table1), $fkey, array('id2'), $this->table_name($table2), array('id2')) );
		$this->assertEquals( array('name' => $fkey, 'local' => 'id2', 'table' => $table2, 'foreign' => 'id2'), self::utils()->foreign_key_info($this->table_name($table1), $fkey) );
	}

	public function test_list_views() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$view = self::utils()->db->DB_PREFIX. 'view_'.__FUNCTION__;
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$data = array(array('name' => 'id', 'type' => 'int', 'length' => 10));
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
		$data = array(array('name' => 'id', 'type' => 'int', 'length' => 10));
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
		$data = array(array('name' => 'id', 'type' => 'int', 'length' => 10));
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
		$data = array(array('name' => 'id', 'type' => 'int', 'length' => 10));
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
		$data = array(array('name' => 'id', 'type' => 'int', 'length' => 10));
		$this->assertFalse( self::utils()->view_exists($this->db_name().'.'.$view) );
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table), $data) );
		$this->assertNotEmpty( self::utils()->table_exists($this->table_name($table)) );
		$this->assertNotEmpty( self::utils()->create_view($this->db_name().'.'.$view, 'SELECT * FROM '.$this->table_name($table)) );
		$this->assertNotEmpty( self::utils()->view_exists($this->db_name().'.'.$view) );
	}

	public function test_list_triggers() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table), array(array('name' => 'id', 'type' => 'int', 'length' => 10))) );

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
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table), array(array('name' => 'id', 'type' => 'int', 'length' => 10))) );
		$trg = self::utils()->db->DB_PREFIX. 'trg_'.__FUNCTION__;
		$this->assertFalse( self::utils()->trigger_exists($this->db_name().'.'.$trg) );
		$sql = 'CREATE TRIGGER '.$this->db_name().'.'.$trg.' BEFORE INSERT ON '.$this->table_name($table).' FOR EACH ROW SET @sum = @sum + NEW.id';
		$this->assertNotEmpty( self::utils()->db->query($sql) );
		$this->assertNotEmpty( self::utils()->trigger_exists($this->db_name().'.'.$trg) );
	}
	public function test_trigger_info() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table), array(array('name' => 'id', 'type' => 'int', 'length' => 10))) );
		$trg = self::utils()->db->DB_PREFIX. 'trg_'.__FUNCTION__;
		$sql = 'CREATE TRIGGER '.$this->db_name().'.'.$trg.' BEFORE INSERT ON '.$this->table_name($table).' FOR EACH ROW SET @sum = @sum + NEW.id';
		$this->assertNotEmpty( self::utils()->db->query($sql) );
		$expected = array(
			'name' => $trg,
			'table' => $table,
			'event' => 'INSERT',
			'timing' => 'BEFORE',
			'statement' => 'SET @sum = @sum + NEW.id',
			'definer' => NULL,
		);
		$result = self::utils()->trigger_info($this->db_name().'.'.$trg);
		foreach ($expected as $k => $_expected) {
			$this->assertEquals( $_expected, $result[$k] );
		}
	}
	public function test_drop_trigger() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table), array(array('name' => 'id', 'type' => 'int', 'length' => 10))) );
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
		$this->assertNotEmpty( self::utils()->create_table($this->table_name($table), array(array('name' => 'id', 'type' => 'int', 'length' => 10))) );
		$trg = self::utils()->db->DB_PREFIX. 'trg_'.__FUNCTION__;
		$this->assertNotEmpty( self::utils()->create_trigger($trg, $this->table_name($table), 'before', 'insert', 'SET @sum = @sum + NEW.id') );
		$expected = array(
			'name' => $trg,
			'table' => $table,
			'event' => 'INSERT',
			'timing' => 'BEFORE',
			'statement' => 'SET @sum = @sum + NEW.id',
			'definer' => NULL,
		);
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
		$fields = array('id1','id2','test_field');
		$expected = array('`id1`','`id2`','`test_field`');
		$this->assertEquals( $expected, self::utils()->_escape_fields($fields) );
	}
	public function test__es() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertEquals( 'hello world', self::utils()->_es('hello world') );
		$this->assertEquals( 'hello\\\'world\\\'', self::utils()->_es('hello\'world\'') );
	}

	public function test_split_sql() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
#		$this->assertEquals( self::utils()-> );
	}
	public function test_get_table_structure_from_db_installer() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
#		$this->assertEquals( self::utils()-> );
	}

	public function test_table() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
#		$this->assertEquals( self::utils()-> );
	}
	public function test_column() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
#		$this->assertEquals( self::utils()-> );
	}
	public function test_view() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
#		$this->assertEquals( self::utils()-> );
	}
	public function test_trigger() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
#		$this->assertEquals( self::utils()-> );
	}
}
