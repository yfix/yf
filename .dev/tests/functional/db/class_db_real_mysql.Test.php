<?php

require_once __DIR__.'/db_real_abstract.php';

/**
 * @requires extension mysql
 */
class class_db_real_mysql_test extends db_real_abstract {
	public static function setUpBeforeClass() {
		self::$_bak['DB_DRIVER'] = self::$DB_DRIVER;
		self::$DB_DRIVER = 'mysql5';
		self::_connect();
		self::utils()->truncate_database(self::db_name());
	}
	public static function tearDownAfterClass() {
		self::utils()->truncate_database(self::db_name());
		self::$DB_DRIVER = self::$_bak['DB_DRIVER'];
	}
	public static function db_name() {
		return self::$DB_NAME;
	}
	public static function table_name($name) {
		return self::db_name().'.'.$name;
	}
	public static function create_table_sql($table) {
		return 'CREATE TABLE '.self::table_name($table).'(id INT(10) AUTO_INCREMENT, id2 INT(10), id3 INT(10), PRIMARY KEY(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8';
	}

	public function test_disconnect_connect() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
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
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::db()->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::db()->query($this->create_table_sql($table)) );
		$expected = [
			'Table' => $table,
			'Create Table' => 'CREATE TABLE `'.$table.'` ('. PHP_EOL
				. '  `id` int(10) NOT NULL AUTO_INCREMENT,'. PHP_EOL
				. '  `id2` int(10) DEFAULT NULL,'. PHP_EOL
				. '  `id3` int(10) DEFAULT NULL,'. PHP_EOL
				. '  PRIMARY KEY (`id`)'. PHP_EOL
				. ') ENGINE=InnoDB DEFAULT CHARSET=utf8',
		];
		$sql = 'SHOW CREATE TABLE '.$this->table_name($table);
		$this->assertSame( $expected, self::db()->fetch_assoc(self::db()->query($sql)) );
		$this->assertSame( $expected, self::db()->fetch_assoc(self::db()->unbuffered_query($sql)) );
		$this->assertSame( $expected, self::db()->query_fetch($sql) );
		$this->assertSame( $expected, self::db()->get($sql) );

		$this->assertNotEmpty( self::db()->query('INSERT INTO '.$this->table_name($table).' VALUES (1,1,1),(2,2,2),(3,3,3)') );
		$this->assertEquals( 3, self::db()->affected_rows() );
		$this->assertEquals( 3, self::db()->insert_id() );
		$this->assertEquals( ['id' => 1], self::db()->get('SELECT id FROM '.$this->table_name($table)) );
		$this->assertEquals( [1 => ['id' => 1], 2 => ['id' => 2], 3 => ['id' => 3]], self::db()->get_all('SELECT id FROM '.$this->table_name($table)) );
		$this->assertEquals( [3 => ['id' => 3], 2 => ['id' => 2], 1 => ['id' => 1]], self::db()->get_all('SELECT id FROM '.$this->table_name($table).' ORDER BY id DESC') );
		$this->assertEmpty( self::db()->get('SELECT id FROM '.$this->table_name($table).' WHERE id > 9999') );
		$this->assertEmpty( self::db()->get_all('SELECT id FROM '.$this->table_name($table).' WHERE id > 9999') );

		$this->assertEquals( 3, self::db()->num_rows(self::db()->query('SELECT id FROM '.$this->table_name($table))) );
		$this->assertEquals( 3, self::db()->query_num_rows('SELECT id FROM '.$this->table_name($table)) );

		$q = self::db()->query('SELECT id FROM '.$this->table_name($table));
		$this->assertEquals( 3, self::db()->num_rows($q) );
		$this->assertEquals( ['id' => 1], self::db()->fetch_assoc($q) );
		$this->assertTrue( self::db()->free_result($q) );

		$this->assertEquals( ['message' => '', 'code' => 0], self::db()->error() );

		$this->assertEquals( [1], self::db()->fetch_row(self::db()->query('SELECT id FROM '.$this->table_name($table))) );
		$obj = new stdClass();
		$obj->id = 1;
		$this->assertEquals( $obj, self::db()->fetch_object(self::db()->query('SELECT id FROM '.$this->table_name($table))) );
	}
	public function test_escape_key() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertSame( '`mykey`', self::db()->escape_key('mykey') );
		$this->assertSame( ['`mykey`'], self::db()->escape_key(['mykey']) );
		$this->assertSame( [['`mykey`']], self::db()->escape_key([['mykey']]) );
		$this->assertSame( ['`key1`','`key2`','`key3`'], self::db()->escape_key(['key1','key2','key3']) );
	}
	public function test_escape_val() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertSame( '\'myval\'', self::db()->escape_val('myval') );
		$this->assertSame( 'NULL', self::db()->escape_val(NULL) );
		$this->assertSame( 'NULL', self::db()->escape_val('NULL') );
		$this->assertSame( ['\'myval\''], self::db()->escape_val(['myval']) );
		$this->assertSame( [['\'myval\'']], self::db()->escape_val([['myval']]) );
		$this->assertSame( [['NULL']], self::db()->escape_val([[NULL]]) );
		$this->assertSame( [['NULL']], self::db()->escape_val([['NULL']]) );
		$this->assertSame( ['\'val1\'','\'val2\'','\'val3\''], self::db()->escape_val(['val1','val2','val3']) );
		$this->assertSame( ['\'val1\'','NULL','\'val3\''], self::db()->escape_val(['val1',NULL,'val3']) );
		$this->assertSame( ['\'val1\'','NULL','\'val3\''], self::db()->escape_val(['val1','NULL','val3']) );
	}
	public function test_real_name() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::db()->DB_PREFIX. __FUNCTION__;
		$table_wo_prefix = 'tbl_'.__FUNCTION__;
		$prefixed_table = 'prfx_'.$table;
		$this->assertSame( $table, self::db()->_real_name($table) );
		self::db()->_found_tables = [$table => $prefixed_table];
		$this->assertSame( [$table => $prefixed_table], self::db()->_found_tables );
		$this->assertSame( $prefixed_table, self::db()->_real_name($table) );
		$table_wo_prefix = 'tbl_'.__FUNCTION__;
		$this->assertSame( self::db()->DB_PREFIX.$table_wo_prefix, self::db()->_real_name($table_wo_prefix) );
		$this->assertSame( self::db()->DB_PREFIX.$table_wo_prefix, self::db()->_real_name(self::db()->DB_PREFIX.$table_wo_prefix) );
	}
	public function test_fix_table_name() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table_wo_prefix = 'tbl_'.__FUNCTION__;
		$table_sys = self::db()->DB_PREFIX. 'sys_'. $table_wo_prefix;
		$table = self::db()->DB_PREFIX. $table_wo_prefix;
		$this->assertSame( $table, self::db()->_fix_table_name($table) );
		$this->assertSame( $table, self::db()->_fix_table_name('dbt_'.$table) );
		$this->assertSame( $table, self::db()->_fix_table_name($table_wo_prefix) );
		$this->assertSame( $table, self::db()->_fix_table_name('dbt_'.$table_wo_prefix) );
		self::db()->_need_sys_prefix = [$table_wo_prefix];
		$this->assertSame( [$table_wo_prefix], self::db()->_need_sys_prefix );
		$this->assertSame( $table_sys, self::db()->_fix_table_name($table_wo_prefix) );
		$this->assertSame( $table_sys, self::db()->_fix_table_name('dbt_'.$table_wo_prefix) );
		$this->assertSame( $table_sys, self::db()->_fix_table_name($table_sys) );
		$this->assertSame( $table_sys, self::db()->_fix_table_name('dbt_'.$table_sys) );
		$this->assertSame( $table_sys, self::db()->_fix_table_name($table) );
		$this->assertSame( $table_sys, self::db()->_fix_table_name('dbt_'.$table) );
	}
	public function test_real_escape_string() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$input = 'He`llo\'_" wor`ld(){}*&^%#';
		$expected = 'He`llo\\\'_\" wor`ld(){}*&^%#';
		$this->assertSame( $expected, self::db()->real_escape_string($input) );
		$this->assertSame( $expected, self::db()->escape_string($input) );
		$this->assertSame( $expected, self::db()->escape($input) );
		$this->assertSame( $expected, self::db()->es($input) );
		$this->assertSame( $expected, self::db()->_mysql_escape_mimic($input) );

		$this->assertSame( 'NULL', self::db()->escape(NULL) );
		$this->assertSame( 'NULL', self::db()->escape('NULL') );
		$this->assertSame( ['myval'], self::db()->escape(['myval']) );
		$this->assertSame( ['NULL'], self::db()->escape([NULL]) );
		$this->assertSame( ['NULL'], self::db()->escape(['NULL']) );
		$this->assertSame( [['myval']], self::db()->escape([['myval']]) );
		$this->assertSame( [['NULL']], self::db()->escape([[NULL]]) );
		$this->assertSame( [['NULL']], self::db()->escape([['NULL']]) );
		$this->assertSame( ['val1','val2','val3'], self::db()->escape(['val1','val2','val3']) );
		$this->assertSame( ['val1','NULL','val3'], self::db()->escape(['val1',NULL,'val3']) );
		$this->assertSame( ['val1','NULL','val3'], self::db()->escape(['val1','NULL','val3']) );
		$this->assertSame( [$expected], self::db()->escape([$input]) );
		$this->assertSame( [[$expected]], self::db()->escape([[$input]]) );
		$this->assertSame( [['NULL',$expected]], self::db()->escape([[NULL, $input]]) );
		$this->assertSame( [['NULL',$expected]], self::db()->escape([['NULL', $input]]) );
		$this->assertSame( 3, self::db()->escape(3) );
		$this->assertSame( '3.5', self::db()->escape(3.5) );
		$this->assertSame( 1, self::db()->escape(true) );
		$this->assertSame( 0, self::db()->escape(false) );

		$this->assertSame( 'NULL', self::db()->_mysql_escape_mimic(NULL) );
		$this->assertSame( 'NULL', self::db()->_mysql_escape_mimic('NULL') );
		$this->assertSame( ['myval'], self::db()->_mysql_escape_mimic(['myval']) );
		$this->assertSame( ['NULL'], self::db()->_mysql_escape_mimic([NULL]) );
		$this->assertSame( ['NULL'], self::db()->_mysql_escape_mimic(['NULL']) );
		$this->assertSame( [['myval']], self::db()->_mysql_escape_mimic([['myval']]) );
		$this->assertSame( [['NULL']], self::db()->_mysql_escape_mimic([[NULL]]) );
		$this->assertSame( [['NULL']], self::db()->_mysql_escape_mimic([['NULL']]) );
		$this->assertSame( ['val1','val2','val3'], self::db()->_mysql_escape_mimic(['val1','val2','val3']) );
		$this->assertSame( ['val1','NULL','val3'], self::db()->_mysql_escape_mimic(['val1',NULL,'val3']) );
		$this->assertSame( ['val1','NULL','val3'], self::db()->_mysql_escape_mimic(['val1','NULL','val3']) );
		$this->assertSame( [$expected], self::db()->_mysql_escape_mimic([$input]) );
		$this->assertSame( [[$expected]], self::db()->_mysql_escape_mimic([[$input]]) );
		$this->assertSame( [['NULL',$expected]], self::db()->_mysql_escape_mimic([[NULL, $input]]) );
		$this->assertSame( [['NULL',$expected]], self::db()->_mysql_escape_mimic([['NULL', $input]]) );
		$this->assertSame( 3, self::db()->_mysql_escape_mimic(3) );
		$this->assertSame( '3.5', self::db()->_mysql_escape_mimic(3.5) );
		$this->assertSame( 1, self::db()->_mysql_escape_mimic(true) );
		$this->assertSame( 0, self::db()->_mysql_escape_mimic(false) );
	}
	public function test_get_one() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::db()->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::db()->query($this->create_table_sql($table)) );
		$this->assertNotEmpty( self::db()->query('INSERT INTO '.$this->table_name($table).' VALUES (1,1,1),(2,2,2),(3,3,3)') );
		$this->assertEquals( 1, self::db()->get_one('SELECT id FROM '.$this->table_name($table)) );
		$this->assertEquals( 1, self::db()->get_one('SELECT id FROM '.$this->table_name($table).' LIMIT 1') );
		$this->assertEquals( 3, self::db()->get_one('SELECT id FROM '.$this->table_name($table).' ORDER BY id DESC') );
	}
	public function test_get_2d() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::db()->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::db()->query($this->create_table_sql($table)) );
		$this->assertNotEmpty( self::db()->query('INSERT INTO '.$this->table_name($table).' VALUES (1,11,111),(2,22,222),(3,33,333)') );
		$this->assertEquals( [1 => '11', 2 => '22', '3' => 33], self::db()->get_2d('SELECT id, id2 FROM '.$this->table_name($table)) );
		$this->assertEquals( [11 => '1', 22 => '2', '33' => 3], self::db()->get_2d('SELECT id2, id FROM '.$this->table_name($table)) );
		$this->assertEquals( ['33' => 3, 22 => '2', 11 => '1'], self::db()->get_2d('SELECT id2, id FROM '.$this->table_name($table).' ORDER BY id DESC') );
	}
	public function test_insert() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::db()->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::db()->query($this->create_table_sql($table)) );
		$this->assertEmpty( self::db()->query_num_rows('SELECT id, id2, id3 FROM '.$this->table_name($table)) );
		$this->assertEmpty( self::db()->get('SELECT id, id2, id3 FROM '.$this->table_name($table)) );
		$data = ['id' => 1, 'id2' => 11, 'id3' => 111];
		$this->assertNotEmpty( self::db()->insert($this->table_name($table), $data) );
		$this->assertEquals( $data, self::db()->get('SELECT id, id2, id3 FROM '.$this->table_name($table)) );

		$this->assertNotEmpty( self::db()->query('TRUNCATE TABLE '.$this->table_name($table)) );
		$this->assertEmpty( self::db()->get('SELECT id, id2, id3 FROM '.$this->table_name($table)) );
		$data = [
			1 => ['id' => 1, 'id2' => 11, 'id3' => 111],
			2 => ['id' => 2, 'id2' => 22, 'id3' => 222],
		];
		$this->assertNotEmpty( self::db()->insert($this->table_name($table), $data) );
		$this->assertEquals( $data, self::db()->get_all('SELECT id, id2, id3 FROM '.$this->table_name($table)) );
	}
	public function test_insert_ignore() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::db()->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::db()->query($this->create_table_sql($table)) );
		$this->assertNotEmpty( self::db()->insert($this->table_name($table), ['id' => 1, 'id2' => 11, 'id3' => 111]) );
		$data = ['id' => 1, 'id2' => 11, 'id3' => 111];
		$this->assertEquals( $data, self::db()->get('SELECT id, id2, id3 FROM '.$this->table_name($table)) );
		$this->assertNotEmpty( self::db()->insert_ignore($this->table_name($table), $data) );
		$this->assertEquals( $data, self::db()->get('SELECT id, id2, id3 FROM '.$this->table_name($table)) );
	}
	public function test_insert_on_duplicate_key_update() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::db()->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::db()->query($this->create_table_sql($table)) );
		$this->assertNotEmpty( self::db()->insert($this->table_name($table), ['id' => 1, 'id2' => 11, 'id3' => 111]) );
		$data = ['id' => 1, 'id2' => 11, 'id3' => 111];
		$this->assertEquals( $data, self::db()->get('SELECT id, id2, id3 FROM '.$this->table_name($table)) );
		$this->assertNotEmpty( self::db()->insert_on_duplicate_key_update($this->table_name($table), $data) );
		$this->assertEquals( $data, self::db()->get('SELECT id, id2, id3 FROM '.$this->table_name($table)) );
		$new_data = ['id' => 1, 'id2' => 22, 'id3' => 333];
		$this->assertNotEmpty( self::db()->insert_on_duplicate_key_update($this->table_name($table), $new_data) );
		$this->assertEquals( $new_data, self::db()->get('SELECT id, id2, id3 FROM '.$this->table_name($table)) );
	}
	public function test_replace() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::db()->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::db()->query($this->create_table_sql($table)) );
		$this->assertEmpty( self::db()->get('SELECT id, id2, id3 FROM '.$this->table_name($table)) );
		$data = ['id' => 1, 'id2' => 11, 'id3' => 111];
		$this->assertNotEmpty( self::db()->replace($this->table_name($table), $data) );
		$this->assertEquals( $data, self::db()->get('SELECT id, id2, id3 FROM '.$this->table_name($table)) );
		$data = ['id' => 1, 'id2' => 22, 'id3' => 333];
		$this->assertNotEmpty( self::db()->replace($this->table_name($table), $data) );
		$this->assertEquals( $data, self::db()->get('SELECT id, id2, id3 FROM '.$this->table_name($table)) );

		$this->assertNotEmpty( self::db()->query('TRUNCATE TABLE '.$this->table_name($table)) );
		$this->assertEmpty( self::db()->get('SELECT id, id2, id3 FROM '.$this->table_name($table)) );
		$data = [
			1 => ['id' => 1, 'id2' => 11, 'id3' => 111],
			2 => ['id' => 2, 'id2' => 22, 'id3' => 222],
		];
		$this->assertNotEmpty( self::db()->replace($this->table_name($table), $data) );
		$this->assertEquals( $data, self::db()->get_all('SELECT id, id2, id3 FROM '.$this->table_name($table)) );
		$data = [
			1 => ['id' => 1, 'id2' => 115, 'id3' => 1115],
			2 => ['id' => 2, 'id2' => 225, 'id3' => 2225],
		];
		$this->assertNotEmpty( self::db()->replace($this->table_name($table), $data) );
		$this->assertEquals( $data, self::db()->get_all('SELECT id, id2, id3 FROM '.$this->table_name($table)) );
	}
	public function test_update() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::db()->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::db()->query($this->create_table_sql($table)) );
		$this->assertEmpty( self::db()->get('SELECT id, id2, id3 FROM '.$this->table_name($table)) );
		$data = ['id' => 1, 'id2' => 11, 'id3' => 111];
		$this->assertNotEmpty( self::db()->insert($this->table_name($table), $data) );
		$this->assertEquals( $data, self::db()->get('SELECT id, id2, id3 FROM '.$this->table_name($table)) );
		$data = ['id' => 2, 'id2' => 22, 'id3' => 222];
		$this->assertNotEmpty( self::db()->update($this->table_name($table), $data, 1) );
		$this->assertEquals( $data, self::db()->get('SELECT id, id2, id3 FROM '.$this->table_name($table)) );
	}
	public function test_update_batch() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::db()->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::db()->query($this->create_table_sql($table)) );
		$this->assertEmpty( self::db()->get('SELECT id, id2, id3 FROM '.$this->table_name($table)) );
		$data = [
			1 => ['id' => 1, 'id2' => 11, 'id3' => 111],
			2 => ['id' => 2, 'id2' => 22, 'id3' => 222],
		];
		$this->assertNotEmpty( self::db()->insert($this->table_name($table), $data) );
		$this->assertEquals( $data, self::db()->get_all('SELECT id, id2, id3 FROM '.$this->table_name($table)) );
		$data = [
			1 => ['id' => 1, 'id2' => 116, 'id3' => 1116],
			2 => ['id' => 2, 'id2' => 226, 'id3' => 2226],
		];
		$this->assertNotEmpty( self::db()->update_batch($this->table_name($table), $data) );
		$this->assertEquals( $data, self::db()->get_all('SELECT id, id2, id3 FROM '.$this->table_name($table)) );
	}
	public function test_fix_data_safe() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		self::db()->FIX_DATA_SAFE = true;
		self::db()->FIX_DATA_SAFE_SILENT = true;
		$table = self::db()->DB_PREFIX. __FUNCTION__;

		$this->assertNotEmpty( self::db()->query($this->create_table_sql($table)) );
		$data = ['id' => 1, 'id2' => 11, 'id3' => 111];
		$data_wrong = $data + ['not_existing_col' => 1];
		$this->assertEquals( $data, self::db()->_fix_data_safe($this->table_name($table), $data, ['no_cache' => 1]) );
		$this->assertEquals( $data, self::db()->_fix_data_safe($this->table_name($table), $data_wrong, ['no_cache' => 1]) );

		$data = [
			1 => ['id' => 1, 'id2' => 11, 'id3' => 111],
			2 => ['id' => 2, 'id2' => 22, 'id3' => 222],
		];
		$data_wrong = $data;
		$data_wrong[1]['not_existing_col'] = 1;
		$data_wrong[2]['not_existing_col'] = 2;
		$this->assertSame( $data, self::db()->_fix_data_safe($this->table_name($table), $data, ['no_cache' => 1]) );
		$this->assertSame( $data, self::db()->_fix_data_safe($this->table_name($table), $data_wrong, ['no_cache' => 1]) );

		$data = ['id' => 1, 'id2' => 11, 'id3' => NULL];
		$this->assertSame( $data, self::db()->_fix_data_safe($this->table_name($table), $data, ['no_cache' => 1]) );
		$data = ['id' => 1, 'id2' => 11, 'id3' => 'NULL'];
		$this->assertSame( $data, self::db()->_fix_data_safe($this->table_name($table), $data, ['no_cache' => 1]) );
		$data = [
			1 => ['id' => 1, 'id2' => 11, 'id3' => NULL],
			2 => ['id' => 2, 'id2' => 22, 'id3' => 222],
		];
		$this->assertSame( $data, self::db()->_fix_data_safe($this->table_name($table), $data, ['no_cache' => 1]) );
		$data = [
			1 => ['id' => 1, 'id2' => 11, 'id3' => 'NULL'],
			2 => ['id' => 2, 'id2' => 22, 'id3' => 222],
		];
		$this->assertSame( $data, self::db()->_fix_data_safe($this->table_name($table), $data, ['no_cache' => 1]) );
	}
	public function test_insert_safe() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		self::db()->FIX_DATA_SAFE = true;
		self::db()->FIX_DATA_SAFE_SILENT = true;
		$table = self::db()->DB_PREFIX. __FUNCTION__;

		$this->assertNotEmpty( self::db()->query($this->create_table_sql($table)) );
		$this->assertEmpty( self::db()->query_num_rows('SELECT id, id2, id3 FROM '.$this->table_name($table)) );
		$this->assertEmpty( self::db()->get('SELECT id, id2, id3 FROM '.$this->table_name($table)) );
		$data = ['id' => 1, 'id2' => 11, 'id3' => 111];
		$this->assertNotEmpty( self::db()->insert_safe($this->table_name($table), $data + ['not_existing_column' => '1']) );
		$this->assertEquals( $data, self::db()->get('SELECT id, id2, id3 FROM '.$this->table_name($table)) );

		$this->assertNotEmpty( self::db()->query('TRUNCATE TABLE '.$this->table_name($table)) );
		$this->assertEmpty( self::db()->get('SELECT id, id2, id3 FROM '.$this->table_name($table)) );
		$data = [
			1 => ['id' => 1, 'id2' => 11, 'id3' => 111],
			2 => ['id' => 2, 'id2' => 22, 'id3' => 222],
		];
		$data_wrong = $data;
		$data_wrong[1]['not_existing_col'] = 1;
		$data_wrong[2]['not_existing_col'] = 2;
		$this->assertNotEmpty( self::db()->insert_safe($this->table_name($table), $data_wrong) );
		$this->assertEquals( $data, self::db()->get_all('SELECT id, id2, id3 FROM '.$this->table_name($table)) );
	}
	public function test_replace_safe() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		self::db()->FIX_DATA_SAFE = true;
		self::db()->FIX_DATA_SAFE_SILENT = true;
		$table = self::db()->DB_PREFIX. __FUNCTION__;

		$this->assertNotEmpty( self::db()->query($this->create_table_sql($table)) );
		$this->assertEmpty( self::db()->get('SELECT id, id2, id3 FROM '.$this->table_name($table)) );
		$data = ['id' => 1, 'id2' => 11, 'id3' => 111];
		$this->assertNotEmpty( self::db()->replace_safe($this->table_name($table), $data) );
		$this->assertEquals( $data, self::db()->get('SELECT id, id2, id3 FROM '.$this->table_name($table)) );
		$data = ['id' => 1, 'id2' => 22, 'id3' => 333];
		$data_wrong = $data + ['not_existing_col' => 1];
		$this->assertNotEmpty( self::db()->replace_safe($this->table_name($table), $data_wrong) );
		$this->assertEquals( $data, self::db()->get('SELECT id, id2, id3 FROM '.$this->table_name($table)) );

		$this->assertNotEmpty( self::db()->query('TRUNCATE TABLE '.$this->table_name($table)) );
		$this->assertEmpty( self::db()->get('SELECT id, id2, id3 FROM '.$this->table_name($table)) );
		$data = [
			1 => ['id' => 1, 'id2' => 11, 'id3' => 111],
			2 => ['id' => 2, 'id2' => 22, 'id3' => 222],
		];
		$data_wrong = $data;
		$data_wrong[1]['not_existing_col'] = 1;
		$data_wrong[2]['not_existing_col'] = 2;
		$this->assertNotEmpty( self::db()->replace_safe($this->table_name($table), $data_wrong) );
		$this->assertEquals( $data, self::db()->get_all('SELECT id, id2, id3 FROM '.$this->table_name($table)) );
		$data = [
			1 => ['id' => 1, 'id2' => 115, 'id3' => 1115],
			2 => ['id' => 2, 'id2' => 225, 'id3' => 2225],
		];
		$this->assertNotEmpty( self::db()->replace_safe($this->table_name($table), $data) );
		$this->assertEquals( $data, self::db()->get_all('SELECT id, id2, id3 FROM '.$this->table_name($table)) );
	}
	public function test_update_safe() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		self::db()->FIX_DATA_SAFE = true;
		self::db()->FIX_DATA_SAFE_SILENT = true;
		$table = self::db()->DB_PREFIX. __FUNCTION__;

		$this->assertNotEmpty( self::db()->query($this->create_table_sql($table)) );
		$this->assertEmpty( self::db()->get('SELECT id, id2, id3 FROM '.$this->table_name($table)) );
		$data = ['id' => 1, 'id2' => 11, 'id3' => 111];
		$this->assertNotEmpty( self::db()->insert_safe($this->table_name($table), $data) );
		$this->assertEquals( $data, self::db()->get('SELECT id, id2, id3 FROM '.$this->table_name($table)) );
		$data = ['id' => 2, 'id2' => 22, 'id3' => 222];
		$data_wrong = $data + ['not_existing_col' => 1];
		$this->assertNotEmpty( self::db()->update_safe($this->table_name($table), $data_wrong, 1) );
		$this->assertEquals( $data, self::db()->get('SELECT id, id2, id3 FROM '.$this->table_name($table)) );
	}
	public function test_update_batch_safe() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		self::db()->FIX_DATA_SAFE = true;
		self::db()->FIX_DATA_SAFE_SILENT = true;
		$table = self::db()->DB_PREFIX. __FUNCTION__;

		$this->assertNotEmpty( self::db()->query($this->create_table_sql($table)) );
		$this->assertEmpty( self::db()->get('SELECT id, id2, id3 FROM '.$this->table_name($table)) );
		$data = [
			1 => ['id' => 1, 'id2' => 11, 'id3' => 111],
			2 => ['id' => 2, 'id2' => 22, 'id3' => 222],
		];
		$this->assertNotEmpty( self::db()->insert_safe($this->table_name($table), $data) );
		$this->assertEquals( $data, self::db()->get_all('SELECT id, id2, id3 FROM '.$this->table_name($table)) );
		$data = [
			1 => ['id' => 1, 'id2' => 116, 'id3' => 1116],
			2 => ['id' => 2, 'id2' => 226, 'id3' => 2226],
		];
		$data_wrong = $data;
		$data_wrong[1]['not_existing_col'] = 1;
		$data_wrong[2]['not_existing_col'] = 2;
		$this->assertNotEmpty( self::db()->update_batch_safe($this->table_name($table), $data_wrong) );
		$this->assertEquals( $data, self::db()->get_all('SELECT id, id2, id3 FROM '.$this->table_name($table)) );
	}
	public function test_split_sql() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$expected = ['SELECT 1', 'SELECT 2', 'SELECT 3'];
		$this->assertEquals( $expected, self::db()->split_sql('SELECT 1; SELECT 2; SELECT 3') );
		$this->assertEquals( $expected, self::db()->split_sql('SELECT 1;'.PHP_EOL.' SELECT 2;'.PHP_EOL.' SELECT 3') );
		$this->assertEquals( $expected, self::db()->split_sql(';;SELECT 1;;'.PHP_EOL.PHP_EOL.PHP_EOL.'; SELECT 2;;'.PHP_EOL.PHP_EOL.PHP_EOL.'; SELECT 3;;;') );
	}
	public function test_limit() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertEquals( 'LIMIT 1', self::db()->limit(1) );
		$this->assertEquals( 'LIMIT 1, 1', self::db()->limit(1,1) );
		$this->assertEquals( 'LIMIT 2, 1', self::db()->limit(1,2) );
		$this->assertEquals( 'LIMIT 10, 5', self::db()->limit(5,10) );
	}
	public function test_utils() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertTrue( is_object(self::db()->utils()) );
		$this->assertTrue( is_object(self::db()->utils()->db) );
	}
	public function test_query_builder() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertTrue( is_object(self::db()->query_builder()) );
		$this->assertTrue( is_object(self::db()->query_builder()->db) );
	}
	public function test_from_and_select() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::db()->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::db()->query($this->create_table_sql($table)) );
		$data = ['id' => 1, 'id2' => 11, 'id3' => 111];
		$this->assertNotEmpty( self::db()->insert($this->table_name($table), $data) );
		$this->assertEquals( $data, self::db()->get('SELECT id, id2, id3 FROM '.$this->table_name($table)) );
		$this->assertEquals( $data, self::db()->get('SELECT * FROM '.$this->table_name($table)) );
		$this->assertEquals( $data, self::db()->from($this->table_name($table))->get() );
		$this->assertEquals( $data, self::db()->select()->from($this->table_name($table))->get() );
		$this->assertEquals( $data, self::db()->select('*')->from($this->table_name($table))->get() );
	}
	public function test_delete() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::db()->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::db()->query($this->create_table_sql($table)) );
		$data = [
			1 => ['id' => 1, 'id2' => 11, 'id3' => 111],
			2 => ['id' => 2, 'id2' => 22, 'id3' => 222],
		];
		$this->assertNotEmpty( self::db()->insert($this->table_name($table), $data) );
		$this->assertEquals( $data, self::db()->from($this->table_name($table))->get_all() );
		$this->assertNotEmpty( self::db()->delete($this->table_name($table), 1));
		$new_data = [2 => $data[2]];
		$this->assertEquals( $new_data, self::db()->from($this->table_name($table))->get_all() );
		$this->assertNotEmpty( self::db()->replace($this->table_name($table), $data) );
		$new_data = [1 => $data[1]];
		$this->assertNotEmpty( self::db()->delete($this->table_name($table), 'id=2'));
		$this->assertEquals( $new_data, self::db()->from($this->table_name($table))->get_all() );
	}
	public function test_get_server_version() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertNotEmpty( self::db()->get_server_version() );
	}
	public function test_get_host_info() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertNotEmpty( self::db()->get_host_info() );
	}
	public function test_get_deep_array() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::db()->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::db()->query($this->create_table_sql($table)) );
		$data = [
			1 => ['id' => '1', 'id2' => '11', 'id3' => '111'],
			2 => ['id' => '2', 'id2' => '22', 'id3' => '222'],
			3 => ['id' => '3', 'id2' => '33', 'id3' => '333'],
		];
		$this->assertNotEmpty( self::db()->insert_safe($this->table_name($table), $data) );

		$expected = [];
		$expected['1']['11']['111'] = $data[1];
		$expected['2']['22']['222'] = $data[2];
		$expected['3']['33']['333'] = $data[3];
		$received = self::db()->get_deep_array('SELECT id, id2, id3 FROM '.$this->table_name($table));
		$this->assertEquals( $expected, $received );

		$expected = [];
		$expected['1']['11']['111'] = $data[1];
		$expected['2']['22']['222'] = $data[2];
		$expected['3']['33']['333'] = $data[3];
		$received = self::db()->get_deep_array('SELECT id, id2, id3 FROM '.$this->table_name($table), $max_levels = 3);
		$this->assertEquals( $expected, $received );

		$expected = [];
		$expected['1']['11'] = $data[1];
		$expected['2']['22'] = $data[2];
		$expected['3']['33'] = $data[3];
		$received = self::db()->get_deep_array('SELECT id, id2, id3 FROM '.$this->table_name($table), $max_levels = 2);
		$this->assertEquals( $expected, $received );

		$expected = [];
		$expected['1'] = $data[1];
		$expected['2'] = $data[2];
		$expected['3'] = $data[3];
		$received = self::db()->get_deep_array('SELECT id, id2, id3 FROM '.$this->table_name($table), $max_levels = 1);
		$this->assertEquals( $expected, $received );
	}
	public function test_get_and_limit() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::db()->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::db()->query($this->create_table_sql($table)) );
		$data = [
			1 => ['id' => '1', 'id2' => '11', 'id3' => '111'],
			2 => ['id' => '2', 'id2' => '22', 'id3' => '222'],
			3 => ['id' => '3', 'id2' => '33', 'id3' => '333'],
		];
		$this->assertNotEmpty( self::db()->insert_safe($this->table_name($table), $data) );

		$expected = $data[1];
		$sql = 'SELECT id, id2, id3 FROM '.$this->table_name($table);
		$sql_before = $sql;
		$received = self::db()->get($sql, $use_cache = false, $assoc = true, $return_sql = true);
		$sql_after = $received;
		$this->assertEquals( $sql_before.' LIMIT 1', $sql_after );
		$received = self::db()->get($sql, $use_cache = false, $assoc = true, $return_sql = false);
		$this->assertEquals( $expected, $received );

		$expected = $data[1];
		$sql = 'SELECT id, id2, id3 FROM '.$this->table_name($table).' limit 1';
		$sql_before = $sql;
		$received = self::db()->get($sql, $use_cache = false, $assoc = true, $return_sql = true);
		$sql_after = $received;
		$this->assertEquals( $sql_before, $sql_after );
		$received = self::db()->get($sql, $use_cache = false, $assoc = true, $return_sql = false);
		$this->assertEquals( $expected, $received );

		$expected = $data[1];
		$sql = 'SELECT id, id2, id3 FROM '.$this->table_name($table).' limit 0,1';
		$sql_before = $sql;
		$received = self::db()->get($sql, $use_cache = false, $assoc = true, $return_sql = true);
		$sql_after = $received;
		$this->assertEquals( $sql_before, $sql_after );
		$received = self::db()->get($sql, $use_cache = false, $assoc = true, $return_sql = false);
		$this->assertEquals( $expected, $received );

		$expected = $data[1];
		$sql = 'SELECT id, id2, id3 FROM '.$this->table_name($table).' limit 1;';
		$sql_before = $sql;
		$received = self::db()->get($sql, $use_cache = false, $assoc = true, $return_sql = true);
		$sql_after = $received;
		$this->assertEquals( rtrim($sql_before,';'), $sql_after );
		$received = self::db()->get($sql, $use_cache = false, $assoc = true, $return_sql = false);
		$this->assertEquals( $expected, $received );

		$expected = $data[1];
		$sql = 'SELECT id, id2, id3 FROM '.$this->table_name($table).'	  LiMit	 1	  ;	  ';
		$sql_before = $sql;
		$received = self::db()->get($sql, $use_cache = false, $assoc = true, $return_sql = true);
		$sql_after = $received;
		$this->assertEquals( rtrim(rtrim(rtrim($sql_before),';')), $sql_after );
		$received = self::db()->get($sql, $use_cache = false, $assoc = true, $return_sql = false);
		$this->assertEquals( $expected, $received );

		$expected = $data[1];
		$sql = 'SELECT * FROM (SELECT id, id2, id3 FROM '.$this->table_name($table).' limit 1) AS tmp';
		$sql_before = $sql;
		$received = self::db()->get($sql, $use_cache = false, $assoc = true, $return_sql = true);
		$sql_after = $received;
		$this->assertEquals( $sql_before.' LIMIT 1', $sql_after );
		$received = self::db()->get($sql, $use_cache = false, $assoc = true, $return_sql = false);
		$this->assertEquals( $expected, $received );
	}
	public function test_model() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
#		$this->assertTrue( is_object(self::db()->model()) );
	}
	public function test_count() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
#		$this->assertEquals( $expected, self::db()-> );
	}
	public function test_multi_query() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
#		$this->assertEquals( $expected, self::db()-> );
	}
	public function test_prepare_and_exec() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
#		$this->assertEquals( $expected, self::db()-> );
	}
	public function test_get_cached() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
#		$this->assertEquals( $expected, self::db()-> );
	}
	public function test_get_all_cached() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
#		$this->assertEquals( $expected, self::db()-> );
	}
	public function test_shutdown_queries() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
// TODO: add, check
#		$this->assertEquals( $expected, self::db()-> );
	}
	public function test_repair_table() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
#		$this->assertEquals( $expected, self::db()-> );
	}
	public function test_meta_columns() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
// TODO: maybe use utils() methods?
#		$this->assertEquals( $expected, self::db()-> );
	}
	public function test_meta_tables() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
// TODO: maybe use utils() methods?
#		$this->assertEquals( $expected, self::db()-> );
	}
	public function test_transactions() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
// TODO: begin
// TODO: commit
// TODO: rollback
#		$this->assertEquals( $expected, self::db()-> );
	}
}