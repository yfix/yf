<?php

require_once __DIR__.'/db_real_abstract.php';

/**
 * @requires extension sqlite3
 */
class class_db_real_sqlite_test extends db_real_abstract {
	public static function setUpBeforeClass() {
		self::$_bak['DB_DRIVER'] = self::$DB_DRIVER;
		self::$DB_DRIVER = 'sqlite';
		self::_connect(['name' => STORAGE_PATH. DB_NAME.'.db']);
		self::$db->ERROR_AUTO_REPAIR = false;
	}
	public static function tearDownAfterClass() {
		$db_file = STORAGE_PATH. DB_NAME.'.db';
		if (file_exists($db_file)) {
			unlink($db_file);
		}
		self::$DB_DRIVER = self::$_bak['DB_DRIVER'];
	}
	public static function _need_skip_test($name) {
		return false;
	}
	public function _need_single_inserts() {
		$sqlite_version = self::db()->get_server_version();
		if (isset($sqlite_version['versionString'])) {
			$sqlite_version = $sqlite_version['versionString'];
		} else {
			$sqlite_version = '3.7.7.1';
		}
#		$this->assertTrue( true, 'SQLite version less than 3.7.11 detected. It does not support multiple rows in one INSERT stmt' );
		return (bool)version_compare($sqlite_version, '3.7.11', '<');
	}
	public function test_disconnect_connect() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertTrue( self::db()->close() );
		$this->assertFalse( self::$db->_connected );
		$this->assertFalse( self::$db->_tried_to_connect );
		$this->assertNull( self::$db->db );
		$this->assertTrue( self::_connect(['name' => STORAGE_PATH. DB_NAME.'.db']) );
		$this->assertTrue( self::$db->_connected );
		$this->assertTrue( self::$db->_tried_to_connect );
		$this->assertTrue( is_object(self::$db->db) );
		$this->assertTrue( !empty(self::$db->db->db_connect_id) );
	}
	public function test_basic_queries_and_fetching() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::db()->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::db()->query('CREATE TABLE IF NOT EXISTS '.$table.'(id INTEGER PRIMARY KEY)') );
		$expected = [
			'cid' => 0,
			'name' => 'id',
			'type' => 'INTEGER',
			'notnull' => 0,
			'dflt_value' => NULL,
			'pk' => 1,
		];
		$sql = 'PRAGMA table_info('.$table.')';
		$this->assertEquals( $expected, self::db()->fetch_assoc(self::db()->query($sql)) );
		$this->assertEquals( $expected, self::db()->fetch_assoc(self::db()->unbuffered_query($sql)) );
		$this->assertEquals( $expected, self::db()->query_fetch($sql) );
		$this->assertEquals( $expected, self::db()->get($sql) );

		if ($this->_need_single_inserts()) {
			$this->assertNotEmpty( self::db()->query('INSERT INTO '.$table.' VALUES (1)') );
			$this->assertEquals( 1, self::db()->affected_rows() );
			$this->assertNotEmpty( self::db()->query('INSERT INTO '.$table.' VALUES (2)') );
			$this->assertEquals( 1, self::db()->affected_rows() );
			$this->assertNotEmpty( self::db()->query('INSERT INTO '.$table.' VALUES (3)') );
			$this->assertEquals( 1, self::db()->affected_rows() );
		} else {
			$this->assertNotEmpty( self::db()->query('INSERT INTO '.$table.' VALUES (1),(2),(3)') );
			$this->assertEquals( 3, self::db()->affected_rows() );
		}
		$this->assertEquals( 3, self::db()->insert_id() );
		$this->assertEquals( ['id' => 1], self::db()->get('SELECT * FROM '.$table) );
		$this->assertEquals( [1 => ['id' => 1], 2 => ['id' => 2], 3 => ['id' => 3]], self::db()->get_all('SELECT * FROM '.$table) );
		$this->assertEquals( [3 => ['id' => 3], 2 => ['id' => 2], 1 => ['id' => 1]], self::db()->get_all('SELECT * FROM '.$table.' ORDER BY id DESC') );
		$this->assertEmpty( self::db()->get('SELECT * FROM '.$table.' WHERE id > 9999') );
		$this->assertEmpty( self::db()->get_all('SELECT * FROM '.$table.' WHERE id > 9999') );

		$this->assertEquals( 3, self::db()->num_rows(self::db()->query('SELECT * FROM '.$table)) );
		$this->assertEquals( 3, self::db()->query_num_rows('SELECT * FROM '.$table) );

		$q = self::db()->query('SELECT * FROM '.$table);
		$this->assertEquals( 3, self::db()->num_rows($q) );
		$this->assertEquals( ['id' => 1], self::db()->fetch_assoc($q) );
		$this->assertTrue( self::db()->free_result($q) );

		$this->assertEquals( ['message' => null, 'code' => null], self::db()->error() );

		$this->assertEquals( [1], self::db()->fetch_row(self::db()->query('SELECT * FROM '.$table)) );
		$obj = new stdClass();
		$obj->id = 1;
		$this->assertEquals( $obj, self::db()->fetch_object(self::db()->query('SELECT * FROM '.$table)) );
	}
	public function test_escape_key() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertEquals( '`mykey`', self::db()->escape_key('mykey') );
	}
	public function test_escape_val() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertEquals( '\'myval\'', self::db()->escape_val('myval') );
	}
	public function test_real_name() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::db()->DB_PREFIX. __FUNCTION__;
		$table_wo_prefix = 'tbl_'.__FUNCTION__;
		$prefixed_table = 'prfx_'.$table;
		$this->assertEquals( $table, self::db()->_real_name($table) );
		self::db()->_found_tables = [$table => $prefixed_table];
		$this->assertEquals( [$table => $prefixed_table], self::db()->_found_tables );
		$this->assertEquals( $prefixed_table, self::db()->_real_name($table) );
		$table_wo_prefix = 'tbl_'.__FUNCTION__;
		$this->assertEquals( self::db()->DB_PREFIX.$table_wo_prefix, self::db()->_real_name($table_wo_prefix) );
		$this->assertEquals( self::db()->DB_PREFIX.$table_wo_prefix, self::db()->_real_name(self::db()->DB_PREFIX.$table_wo_prefix) );
	}
	public function test_fix_table_name() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table_wo_prefix = 'tbl_'.__FUNCTION__;
		$table_sys = self::db()->DB_PREFIX. 'sys_'. $table_wo_prefix;
		$table = self::db()->DB_PREFIX. $table_wo_prefix;
		$this->assertEquals( $table, self::db()->_fix_table_name($table) );
		$this->assertEquals( $table, self::db()->_fix_table_name('dbt_'.$table) );
		$this->assertEquals( $table, self::db()->_fix_table_name($table_wo_prefix) );
		$this->assertEquals( $table, self::db()->_fix_table_name('dbt_'.$table_wo_prefix) );
		self::db()->_need_sys_prefix = [$table_wo_prefix];
		$this->assertEquals( [$table_wo_prefix], self::db()->_need_sys_prefix );
		$this->assertEquals( $table_sys, self::db()->_fix_table_name($table_wo_prefix) );
		$this->assertEquals( $table_sys, self::db()->_fix_table_name('dbt_'.$table_wo_prefix) );
		$this->assertEquals( $table_sys, self::db()->_fix_table_name($table_sys) );
		$this->assertEquals( $table_sys, self::db()->_fix_table_name('dbt_'.$table_sys) );
		$this->assertEquals( $table_sys, self::db()->_fix_table_name($table) );
		$this->assertEquals( $table_sys, self::db()->_fix_table_name('dbt_'.$table) );
	}
	public function test_real_escape_string() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$input = 'He`llo\'_" wor`ld(){}*&^%#';
		$expected = 'He`llo\'\'_" wor`ld(){}*&^%#';
		$this->assertSame( $expected, self::db()->real_escape_string($input) );
		$this->assertSame( $expected, self::db()->escape_string($input) );
		$this->assertSame( $expected, self::db()->escape($input) );
		$this->assertSame( $expected, self::db()->es($input) );
	}
	public function test_get_one() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::db()->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::db()->query('CREATE TABLE '.$table.'(id INTEGER PRIMARY KEY)') );
		if ($this->_need_single_inserts()) {
			$this->assertNotEmpty( self::db()->query('INSERT INTO '.$table.' VALUES (1)') );
			$this->assertNotEmpty( self::db()->query('INSERT INTO '.$table.' VALUES (2)') );
			$this->assertNotEmpty( self::db()->query('INSERT INTO '.$table.' VALUES (3)') );
		} else {
			$this->assertNotEmpty( self::db()->query('INSERT INTO '.$table.' VALUES (1),(2),(3)') );
		}
		$this->assertEquals( 1, self::db()->get_one('SELECT id FROM '.$table) );
		$this->assertEquals( 1, self::db()->get_one('SELECT id FROM '.$table.' LIMIT 1') );
		$this->assertEquals( 3, self::db()->get_one('SELECT id FROM '.$table.' ORDER BY id DESC') );
	}
	public function test_get_2d() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::db()->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::db()->query('CREATE TABLE '.$table.'(id INTEGER PRIMARY KEY, id2 INTEGER)') );
		if ($this->_need_single_inserts()) {
			$this->assertNotEmpty( self::db()->query('INSERT INTO '.$table.' VALUES (1,11)') );
			$this->assertNotEmpty( self::db()->query('INSERT INTO '.$table.' VALUES (2,22)') );
			$this->assertNotEmpty( self::db()->query('INSERT INTO '.$table.' VALUES (3,33)') );
		} else {
			$this->assertNotEmpty( self::db()->query('INSERT INTO '.$table.' VALUES (1,11),(2,22),(3,33)') );
		}
		$this->assertEquals( [1 => '11', 2 => '22', '3' => 33], self::db()->get_2d('SELECT id, id2 FROM '.$table) );
		$this->assertEquals( [11 => '1', 22 => '2', '33' => 3], self::db()->get_2d('SELECT id2, id FROM '.$table) );
		$this->assertEquals( ['33' => 3, 22 => '2', 11 => '1'], self::db()->get_2d('SELECT id2, id FROM '.$table.' ORDER BY id DESC') );
	}
	public function test_insert() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::db()->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::db()->query('CREATE TABLE '.$table.'(id INTEGER PRIMARY KEY, id2 INTEGER, id3 INTEGER)') );
		$this->assertEmpty( self::db()->query_num_rows('SELECT id, id2, id3 FROM '.$table) );
		$this->assertEmpty( self::db()->get('SELECT id, id2, id3 FROM '.$table) );
		$data = ['id' => 1, 'id2' => 11, 'id3' => 111];
		$this->assertNotEmpty( self::db()->insert($table, $data) );
		$this->assertEquals( $data, self::db()->get('SELECT id, id2, id3 FROM '.$table) );

		$this->assertNotEmpty( self::db()->query('DELETE FROM '.$table) );
		$this->assertEmpty( self::db()->get('SELECT id, id2, id3 FROM '.$table) );
		$data = [
			1 => ['id' => 1, 'id2' => 11, 'id3' => 111],
			2 => ['id' => 2, 'id2' => 22, 'id3' => 222],
		];
		if ($this->_need_single_inserts()) {
			foreach ($data as $v) {
				$this->assertNotEmpty( self::db()->insert($table, $v) );
			}
		} else {
			$this->assertNotEmpty( self::db()->insert($table, $data) );
		}
		$this->assertEquals( $data, self::db()->get_all('SELECT id, id2, id3 FROM '.$table) );
	}
	public function test_replace() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::db()->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::db()->query('CREATE TABLE '.$table.'(id INTEGER PRIMARY KEY, id2 INTEGER, id3 INTEGER)') );
		$this->assertEmpty( self::db()->get('SELECT id, id2, id3 FROM '.$table) );
		$data = ['id' => 1, 'id2' => 11, 'id3' => 111];
		$this->assertNotEmpty( self::db()->replace($table, $data) );
		$this->assertEquals( $data, self::db()->get('SELECT id, id2, id3 FROM '.$table) );
		$data = ['id' => 1, 'id2' => 22, 'id3' => 333];
		$this->assertNotEmpty( self::db()->replace($table, $data) );
		$this->assertEquals( $data, self::db()->get('SELECT id, id2, id3 FROM '.$table) );

		$this->assertNotEmpty( self::db()->query('DELETE FROM '.$table) );
		$this->assertEmpty( self::db()->get('SELECT id, id2, id3 FROM '.$table) );
		$data = [
			1 => ['id' => 1, 'id2' => 11, 'id3' => 111],
			2 => ['id' => 2, 'id2' => 22, 'id3' => 222],
		];
		if ($this->_need_single_inserts()) {
			foreach ($data as $v) {
				$this->assertNotEmpty( self::db()->replace($table, $v) );
			}
		} else {
			$this->assertNotEmpty( self::db()->replace($table, $data) );
		}
		$this->assertEquals( $data, self::db()->get_all('SELECT id, id2, id3 FROM '.$table) );
		$data = [
			1 => ['id' => 1, 'id2' => 115, 'id3' => 1115],
			2 => ['id' => 2, 'id2' => 225, 'id3' => 2225],
		];
		if ($this->_need_single_inserts()) {
			foreach ($data as $v) {
				$this->assertNotEmpty( self::db()->replace($table, $v) );
			}
		} else {
			$this->assertNotEmpty( self::db()->replace($table, $data) );
		}
		$this->assertEquals( $data, self::db()->get_all('SELECT id, id2, id3 FROM '.$table) );
	}
	public function test_update() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::db()->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::db()->query('CREATE TABLE '.$table.'(id INTEGER PRIMARY KEY, id2 INTEGER, id3 INTEGER)') );
		$this->assertEmpty( self::db()->get('SELECT id, id2, id3 FROM '.$table) );
		$data = ['id' => 1, 'id2' => 11, 'id3' => 111];
		$this->assertNotEmpty( self::db()->insert($table, $data) );
		$this->assertEquals( $data, self::db()->get('SELECT id, id2, id3 FROM '.$table) );
		$data = ['id' => 2, 'id2' => 22, 'id3' => 222];
		$this->assertNotEmpty( self::db()->update($table, $data, 1) );
		$this->assertEquals( $data, self::db()->get('SELECT id, id2, id3 FROM '.$table) );
	}
	public function test_update_batch() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::db()->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::db()->query('CREATE TABLE '.$table.'(id INTEGER PRIMARY KEY, id2 INTEGER, id3 INTEGER)') );
		$this->assertEmpty( self::db()->get('SELECT id, id2, id3 FROM '.$table) );
		$data = [
			1 => ['id' => 1, 'id2' => 11, 'id3' => 111],
			2 => ['id' => 2, 'id2' => 22, 'id3' => 222],
		];
		if ($this->_need_single_inserts()) {
			foreach ($data as $v) {
				$this->assertNotEmpty( self::db()->insert($table, $v) );
			}
		} else {
			$this->assertNotEmpty( self::db()->insert($table, $data) );
		}
		$this->assertEquals( $data, self::db()->get_all('SELECT id, id2, id3 FROM '.$table) );
		$data = [
			1 => ['id' => 1, 'id2' => 116, 'id3' => 1116],
			2 => ['id' => 2, 'id2' => 226, 'id3' => 2226],
		];
		$this->assertNotEmpty( self::db()->update_batch($table, $data) );
		$this->assertEquals( $data, self::db()->get_all('SELECT id, id2, id3 FROM '.$table) );
	}
	public function test_fix_data_safe() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		self::db()->FIX_DATA_SAFE = true;
		self::db()->FIX_DATA_SAFE_SILENT = true;
		$table = self::db()->DB_PREFIX. __FUNCTION__;

		$this->assertNotEmpty( self::db()->query('CREATE TABLE '.$table.'(id INTEGER PRIMARY KEY, id2 INTEGER, id3 INTEGER)') );
		$data = ['id' => 1, 'id2' => 11, 'id3' => 111];
		$data_wrong = $data + ['not_existing_col' => 1];
		$this->assertEquals( $data, self::db()->_fix_data_safe($table, $data) );
		$this->assertEquals( $data, self::db()->_fix_data_safe($table, $data_wrong) );

		$data = [
			1 => ['id' => 1, 'id2' => 11, 'id3' => 111],
			2 => ['id' => 2, 'id2' => 22, 'id3' => 222],
		];
		$data_wrong = $data;
		$data_wrong[1]['not_existing_col'] = 1;
		$data_wrong[2]['not_existing_col'] = 2;
		$this->assertEquals( $data, self::db()->_fix_data_safe($table, $data) );
		$this->assertEquals( $data, self::db()->_fix_data_safe($table, $data_wrong) );
	}
	public function test_insert_safe() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		self::db()->FIX_DATA_SAFE = true;
		self::db()->FIX_DATA_SAFE_SILENT = true;
		$table = self::db()->DB_PREFIX. __FUNCTION__;

		$this->assertNotEmpty( self::db()->query('CREATE TABLE '.$table.'(id INTEGER PRIMARY KEY, id2 INTEGER, id3 INTEGER)') );
		$this->assertEmpty( self::db()->query_num_rows('SELECT id, id2, id3 FROM '.$table) );
		$this->assertEmpty( self::db()->get('SELECT id, id2, id3 FROM '.$table) );
		$data = ['id' => 1, 'id2' => 11, 'id3' => 111];
		$this->assertNotEmpty( self::db()->insert_safe($table, $data + ['not_existing_column' => '1']) );
		$this->assertEquals( $data, self::db()->get('SELECT id, id2, id3 FROM '.$table) );

		$this->assertNotEmpty( self::db()->query('DELETE FROM '.$table) );
		$this->assertEmpty( self::db()->get('SELECT id, id2, id3 FROM '.$table) );
		$data = [
			1 => ['id' => 1, 'id2' => 11, 'id3' => 111],
			2 => ['id' => 2, 'id2' => 22, 'id3' => 222],
		];
		$data_wrong = $data;
		$data_wrong[1]['not_existing_col'] = 1;
		$data_wrong[2]['not_existing_col'] = 2;
		if ($this->_need_single_inserts()) {
			foreach ($data_wrong as $v) {
				$this->assertNotEmpty( self::db()->insert_safe($table, $v) );
			}
		} else {
			$this->assertNotEmpty( self::db()->insert_safe($table, $data_wrong) );
		}
		$this->assertEquals( $data, self::db()->get_all('SELECT id, id2, id3 FROM '.$table) );
	}
	public function test_replace_safe() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		self::db()->FIX_DATA_SAFE = true;
		self::db()->FIX_DATA_SAFE_SILENT = true;
		$table = self::db()->DB_PREFIX. __FUNCTION__;

		$this->assertNotEmpty( self::db()->query('CREATE TABLE '.$table.'(id INTEGER PRIMARY KEY, id2 INTEGER, id3 INTEGER)') );
		$this->assertEmpty( self::db()->get('SELECT id, id2, id3 FROM '.$table) );
		$data = ['id' => 1, 'id2' => 11, 'id3' => 111];
		$this->assertNotEmpty( self::db()->replace_safe($table, $data) );
		$this->assertEquals( $data, self::db()->get('SELECT id, id2, id3 FROM '.$table) );
		$data = ['id' => 1, 'id2' => 22, 'id3' => 333];
		$data_wrong = $data + ['not_existing_col' => 1];
		$this->assertNotEmpty( self::db()->replace_safe($table, $data_wrong) );
		$this->assertEquals( $data, self::db()->get('SELECT id, id2, id3 FROM '.$table) );

		$this->assertNotEmpty( self::db()->query('DELETE FROM '.$table) );
		$this->assertEmpty( self::db()->get('SELECT id, id2, id3 FROM '.$table) );
		$data = [
			1 => ['id' => 1, 'id2' => 11, 'id3' => 111],
			2 => ['id' => 2, 'id2' => 22, 'id3' => 222],
		];
		$data_wrong = $data;
		$data_wrong[1]['not_existing_col'] = 1;
		$data_wrong[2]['not_existing_col'] = 2;
		if ($this->_need_single_inserts()) {
			foreach ($data_wrong as $v) {
				$this->assertNotEmpty( self::db()->replace_safe($table, $v) );
			}
		} else {
			$this->assertNotEmpty( self::db()->replace_safe($table, $data_wrong) );
		}
		$this->assertEquals( $data, self::db()->get_all('SELECT id, id2, id3 FROM '.$table) );
		$data = [
			1 => ['id' => 1, 'id2' => 115, 'id3' => 1115],
			2 => ['id' => 2, 'id2' => 225, 'id3' => 2225],
		];
		if ($this->_need_single_inserts()) {
			foreach ($data as $v) {
				$this->assertNotEmpty( self::db()->replace_safe($table, $v) );
			}
		} else {
			$this->assertNotEmpty( self::db()->replace_safe($table, $data) );
		}
		$this->assertEquals( $data, self::db()->get_all('SELECT id, id2, id3 FROM '.$table) );
	}
	public function test_update_safe() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		self::db()->FIX_DATA_SAFE = true;
		self::db()->FIX_DATA_SAFE_SILENT = true;
		$table = self::db()->DB_PREFIX. __FUNCTION__;

		$this->assertNotEmpty( self::db()->query('CREATE TABLE '.$table.'(id INTEGER PRIMARY KEY, id2 INTEGER, id3 INTEGER)') );
		$this->assertEmpty( self::db()->get('SELECT id, id2, id3 FROM '.$table) );
		$data = ['id' => 1, 'id2' => 11, 'id3' => 111];
		$this->assertNotEmpty( self::db()->insert_safe($table, $data) );
		$this->assertEquals( $data, self::db()->get('SELECT id, id2, id3 FROM '.$table) );
		$data = ['id' => 2, 'id2' => 22, 'id3' => 222];
		$data_wrong = $data + ['not_existing_col' => 1];
		$this->assertNotEmpty( self::db()->update_safe($table, $data_wrong, 1) );
		$this->assertEquals( $data, self::db()->get('SELECT id, id2, id3 FROM '.$table) );
	}
	public function test_update_batch_safe() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		self::db()->FIX_DATA_SAFE = true;
		self::db()->FIX_DATA_SAFE_SILENT = true;
		$table = self::db()->DB_PREFIX. __FUNCTION__;

		$this->assertNotEmpty( self::db()->query('CREATE TABLE '.$table.'(id INTEGER PRIMARY KEY, id2 INTEGER, id3 INTEGER)') );
		$this->assertEmpty( self::db()->get('SELECT id, id2, id3 FROM '.$table) );
		$data = [
			1 => ['id' => 1, 'id2' => 11, 'id3' => 111],
			2 => ['id' => 2, 'id2' => 22, 'id3' => 222],
		];
		if ($this->_need_single_inserts()) {
			foreach ($data as $v) {
				$this->assertNotEmpty( self::db()->insert_safe($table, $v) );
			}
		} else {
			$this->assertNotEmpty( self::db()->insert_safe($table, $data) );
		}
		$this->assertEquals( $data, self::db()->get_all('SELECT id, id2, id3 FROM '.$table) );
		$data = [
			1 => ['id' => 1, 'id2' => 116, 'id3' => 1116],
			2 => ['id' => 2, 'id2' => 226, 'id3' => 2226],
		];
		$data_wrong = $data;
		$data_wrong[1]['not_existing_col'] = 1;
		$data_wrong[2]['not_existing_col'] = 2;
		$this->assertNotEmpty( self::db()->update_batch_safe($table, $data_wrong) );
		$this->assertEquals( $data, self::db()->get_all('SELECT id, id2, id3 FROM '.$table) );
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
		$this->assertNotEmpty( self::db()->query('CREATE TABLE '.$table.'(id INTEGER PRIMARY KEY, id2 INTEGER, id3 INTEGER)') );
		$data = ['id' => 1, 'id2' => 11, 'id3' => 111];
		$this->assertNotEmpty( self::db()->insert($table, $data) );
		$this->assertEquals( $data, self::db()->get('SELECT id, id2, id3 FROM '.$table) );
		$this->assertEquals( $data, self::db()->get('SELECT * FROM '.$table) );
		$this->assertEquals( $data, self::db()->from($table)->get() );
		$this->assertEquals( $data, self::db()->select()->from($table)->get() );
		$this->assertEquals( $data, self::db()->select('*')->from($table)->get() );
	}
	public function test_delete() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::db()->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::db()->query('CREATE TABLE '.$table.'(id INTEGER PRIMARY KEY, id2 INTEGER, id3 INTEGER)') );
		$data = [
			1 => ['id' => 1, 'id2' => 11, 'id3' => 111],
			2 => ['id' => 2, 'id2' => 22, 'id3' => 222],
		];
		if ($this->_need_single_inserts()) {
			foreach ($data as $v) {
				$this->assertNotEmpty( self::db()->insert_safe($table, $v) );
			}
		} else {
			$this->assertNotEmpty( self::db()->insert_safe($table, $data) );
		}
		$this->assertEquals( $data, self::db()->from($table)->get_all() );
		$this->assertNotEmpty( self::db()->delete($table, 1));
		$new_data = [2 => $data[2]];
		$this->assertEquals( $new_data, self::db()->from($table)->get_all() );
		if ($this->_need_single_inserts()) {
			foreach ($data as $v) {
				$this->assertNotEmpty( self::db()->replace_safe($table, $v) );
			}
		} else {
			$this->assertNotEmpty( self::db()->replace_safe($table, $data) );
		}
		$new_data = [1 => $data[1]];
		$this->assertNotEmpty( self::db()->delete($table, 'id=2'));
		$this->assertEquals( $new_data, self::db()->from($table)->get_all() );
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
	public function test_get_server_version() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
#		$this->assertEquals( $expected, self::db()-> );
	}
	public function test_get_host_info() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
#		$this->assertEquals( $expected, self::db()-> );
	}
	public function test_get_deep_array() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
#		$table = self::db()->DB_PREFIX. __FUNCTION__;
#		$this->assertNotEmpty( self::db()->query('CREATE TABLE '.$table.'(id INTEGER PRIMARY KEY, id2 INTEGER, id3 INTEGER)') );
#		$this->assertNotEmpty( self::db()->query('INSERT INTO '.$table.' VALUES (1,11,111),(2,22,222),(3,33,333)') );
#		$expected = array();
#		$expected['1']['11']['111'] = array('id' => '1', 'id2' => '11', 'id3' => '111');
#		$expected['2']['22']['111'] = array('id' => '2', 'id2' => '22', 'id3' => '222');
#		$expected['3']['33']['111'] = array('id' => '3', 'id2' => '33', 'id3' => '333');
#		$this->assertEquals( $expected, self::db()->get_deep_array('SELECT id, id2, id3 FROM '.$table, $levels = 3) );
##		$this->assertNotEmpty( self::db()->query('DELETE FROM '.$table) );
##		$this->assertNotEmpty( self::db()->query('INSERT INTO '.$table.' VALUES (1,11,111),(1,11,222),(1,11,333)') );
	}
	public function test_transactions() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
// TODO: begin
// TODO: commit
// TODO: rollback
#		$this->assertEquals( $expected, self::db()-> );
	}
}
