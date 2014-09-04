<?php

require_once __DIR__.'/db_real_abstract.php';

/**
 * @requires extension sqlite3
 */
class class_db_real_query_builder_sqlite_test extends db_real_abstract {
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
	public function db_name() {
		return '';
	}
	public function table_name($name) {
		return $name;
	}
	public function create_table_sql($table) {
		return 'CREATE TABLE '.$this->table_name($table).'(id INTEGER PRIMARY KEY, id2 INTEGER, id3 INTEGER)';
	}
	public function insert_safe($table, $data) {
		$is_data_3d = false;
		// Try to check if array is two-dimensional
		foreach ((array)$data as $cur_row) {
			$is_data_3d = is_array($cur_row) ? 1 : 0;
			break;
		}
		if ($is_data_3d && $this->_need_single_inserts()) {
			foreach ((array)$data as $cur_row) {
				$this->assertNotEmpty( self::db()->insert_safe($this->table_name($table), $cur_row) );
			}
		} else {
			$this->assertNotEmpty( self::db()->insert_safe($this->table_name($table), $data) );
		}
	}
	public function test_selects_basic() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::db()->query($this->create_table_sql($table)) );
		$data = array(
			'1' => array('id' => '1', 'id2' => '11', 'id3' => '111'),
			'2' => array('id' => '2', 'id2' => '22', 'id3' => '222'),
		);
		$this->insert_safe($table, $data);

		$this->assertEquals( $data[1], self::db()->get('SELECT * FROM '.$this->table_name($table)) );
		$this->assertEquals( $data[1], self::db()->from($this->table_name($table))->get() );
		$this->assertEquals( $data[1], self::db()->select()->from($this->table_name($table))->get() );
		$this->assertEquals( $data[1], self::db()->select('*')->from($this->table_name($table))->get() );
		$this->assertEquals( $data[1], self::db()->select(array())->from($this->table_name($table))->get() );
		$this->assertEquals( $data[1], self::db()->select('id,id2,id3')->from($this->table_name($table))->get() );
		$this->assertEquals( $data[1], self::db()->select('id, id2, id3')->from($this->table_name($table))->get() );
		$this->assertEquals( $data[1], self::db()->select('id','id2','id3')->from($this->table_name($table))->get() );
		$this->assertEquals( $data[1], self::db()->select(array('id' => 'id','id2' => 'id2','id3' => 'id3'))->from($this->table_name($table))->get() );

		$this->assertEquals( $data, self::db()->get_all('SELECT * FROM '.$this->table_name($table)) );
		$this->assertEquals( $data, self::db()->from($this->table_name($table))->get_all() );
		$this->assertEquals( $data, self::db()->select()->from($this->table_name($table))->get_all() );
		$this->assertEquals( $data, self::db()->select('*')->from($this->table_name($table))->get_all() );
		$this->assertEquals( $data, self::db()->select(array())->from($this->table_name($table))->get_all() );
		$this->assertEquals( $data, self::db()->select('id,id2,id3')->from($this->table_name($table))->get_all() );
		$this->assertEquals( $data, self::db()->select('id, id2, id3')->from($this->table_name($table))->get_all() );
		$this->assertEquals( $data, self::db()->select('id','id2','id3')->from($this->table_name($table))->get_all() );
		$this->assertEquals( $data, self::db()->select(array('id' => 'id','id2' => 'id2','id3' => 'id3'))->from($this->table_name($table))->get_all() );

		$this->assertEquals( array('num' => '2'), self::db()->select('COUNT(id) AS num')->from($this->table_name($table))->get() );
		$this->assertEquals( '2', self::db()->select('COUNT(id)')->from($this->table_name($table))->get_one() );
		$this->assertEquals( '2', self::db()->select('COUNT(id) AS num')->from($this->table_name($table))->get_one() );
		$this->assertEquals( '3', self::db()->select('SUM(id)')->from($this->table_name($table))->get_one() );
		$this->assertEquals( '33', self::db()->select('SUM(id2)')->from($this->table_name($table))->get_one() );
		$this->assertEquals( '333', self::db()->select('SUM(id3)')->from($this->table_name($table))->get_one() );
		$this->assertEquals( '11', self::db()->select('MIN(id2)')->from($this->table_name($table))->get_one() );
		$this->assertEquals( '22', self::db()->select('MAX(id2)')->from($this->table_name($table))->get_one() );
		$this->assertEquals( '1.5000', self::db()->select('AVG(id)')->from($this->table_name($table))->get_one() );

		$this->assertEquals( $data[1], self::db()->from($this->table_name($table))->get() );
		$this->assertEquals( $data[1], self::db()->from($this->table_name($table).' as t1')->get() );
		$this->assertEquals( $data[1], self::db()->from(array($this->table_name($table) => 't1'))->get() );
		$this->assertEquals( $data[1], self::db()->select('t1.id, t1.id2, t1.id3')->from($this->table_name($table).' as t1')->get() );
		$this->assertEquals( $data[1], self::db()->select('t1.id','t1.id2','t1.id3')->from($this->table_name($table).' as t1')->get() );
		$this->assertEquals( $data[1], self::db()->select('t1.id as id','t1.id2 as id2','t1.id3 as id3')->from($this->table_name($table).' as t1')->get() );
		$this->assertEquals( $data[1], self::db()->select(array('t1.id' => 'id','t1.id2' => 'id2','t1.id3' => 'id3'))->from($this->table_name($table).' as t1')->get() );
		$this->assertEquals( array('fld1' => $data[1]['id']), self::db()->select('t1.id as fld1')->from($this->table_name($table).' as t1')->get() );
	}
	public function test_where() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::db()->query($this->create_table_sql($table)) );
		$data = array(
			'1' => array('id' => '1', 'id2' => '11', 'id3' => '111'),
			'2' => array('id' => '2', 'id2' => '22', 'id3' => '222'),
		);
		$this->insert_safe($table, $data);

		$this->assertEquals( $data[1], self::qb()->from($this->table_name($table))->where('id','=','1')->get() );
		$this->assertEquals( $data[2], self::qb()->from($this->table_name($table))->where('id','=','2')->get() );
		$this->assertEmpty( self::qb()->from($this->table_name($table))->where('id','=','3')->get() );
		$this->assertEquals( $data[2], self::qb()->from($this->table_name($table))->where('id3','like','222')->get() );
		$this->assertEquals( $data[2], self::qb()->from($this->table_name($table))->where('id3','like','22%')->get() );
		$this->assertEquals( $data[2], self::qb()->from($this->table_name($table))->where('id3','like','22*')->get() );
#		$this->assertEquals( $data[2], self::qb()->from($this->table_name($table))->where('id3','rlike','(222|222222)')->get() );
#		$this->assertEquals( $data[1], self::qb()->from($this->table_name($table))->where('id3','not rlike','(222|222222)')->get() );

		$this->assertEmpty( self::qb()->from($this->table_name($table).' as t1')->where(array('t1.id2' => '1*', 't1.id3' => '2*'))->get() );
		$this->assertEquals( $data[1], self::qb()->from($this->table_name($table).' as t1')->where(array('t1.id2' => '1*', 't1.id3' => '1*'))->get() );
		$this->assertEquals( $data[2], self::qb()->from($this->table_name($table).' as t1')->where(array('t1.id2' => '2*', 't1.id3' => '2*'))->get() );

		$this->assertEquals( $data[1], self::qb()->from($this->table_name($table).' as t1')->where('id = 1')->get() );
		$this->assertEquals( $data[2], self::qb()->from($this->table_name($table).' as t1')->where('t1.id > 1')->get() );
		$this->assertEquals( $data[1], self::qb()->from($this->table_name($table).' as t1')->where('id = 1')->where('id2 = 11')->where('id3 = 111')->get() );

		$this->assertEmpty( self::qb()->from($this->table_name($table).' as t1')->where('t1.id = 789')->where_or('t1.id2 = 798')->where_or('t1.id3 = 888')->get() );
		$this->assertEquals( $data[1], self::qb()->from($this->table_name($table).' as t1')->where('t1.id = 789')->where_or('t1.id2 = 798')->where_or('t1.id3 = 111')->get() );

		$this->assertEquals( $data[1], self::qb()->from($this->table_name($table).' as t1')->whereid(1)->get() );
		$this->assertEquals( $data[1], self::qb()->from($this->table_name($table).' as t1')->whereid(1, 'id')->get() );
		$this->assertEquals( $data[1], self::qb()->from($this->table_name($table).' as t1')->whereid(1, 't1.id')->get() );

		$this->assertEquals( $data, self::qb()->from($this->table_name($table).' as t1')->whereid(array(1,2,3,4))->get_all() );
		$this->assertEquals( $data, self::qb()->from($this->table_name($table).' as t1')->whereid(array(1,2,3,4), 'id')->get_all() );
		$this->assertEquals( $data, self::qb()->from($this->table_name($table).' as t1')->whereid(array(1,2,3,4), 't1.id')->get_all() );
		$this->assertEquals( $data, self::qb()->from($this->table_name($table).' as t1')->where('t1.id', 'in', array(1,2,3,4))->get_all() );
		$this->assertEquals( $data, self::qb()->from($this->table_name($table).' as t1')->where('t1.id', 'not in', array(5,6,7))->get_all() );

		$this->assertEmpty( self::qb()->from($this->table_name($table).' as t1')->whereid(array(4,5,6))->get_all() );
		$this->assertEmpty( self::qb()->from($this->table_name($table).' as t1')->whereid(array(4,5,6), 'id')->get_all() );
		$this->assertEmpty( self::qb()->from($this->table_name($table).' as t1')->whereid(array(4,5,6), 't1.id')->get_all() );
	}
	public function test_join() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table1 = self::utils()->db->DB_PREFIX. __FUNCTION__.'_1';
		$table2 = self::utils()->db->DB_PREFIX. __FUNCTION__.'_2';
		$this->assertNotEmpty( self::db()->query($this->create_table_sql($table1)) );
		$this->assertNotEmpty( self::db()->query($this->create_table_sql($table2)) );
		$data1 = array(
			'1' => array('id' => '1', 'id2' => '11', 'id3' => '111'),
			'2' => array('id' => '2', 'id2' => '11', 'id3' => '111'),
		);
		$data2 = array(
			'1' => array('id' => '1', 'id2' => '22', 'id3' => '444'),
			'2' => array('id' => '2', 'id2' => '22', 'id3' => '444'),
		);
		$this->insert_safe($table1, $data1);
		$this->insert_safe($table2, $data2);

		$expected = array(
			'1' => array('id' => '1', 'id2' => '22', 'id3' => '444'),
			'2' => array('id' => '2', 'id2' => '22', 'id3' => '444'),
		);
		$this->assertEquals( $expected, self::qb()->from($this->table_name($table1).' as t1')->join($this->table_name($table2).' as t2', 't1.id = t2.id')->get_all() );
		$this->assertEquals( $expected, self::qb()->from($this->table_name($table1).' as t1')->left_join($this->table_name($table2).' as t2', 't1.id = t2.id')->get_all() );
		$this->assertEquals( $expected, self::qb()->from($this->table_name($table1).' as t1')->right_join($this->table_name($table2).' as t2', 't1.id = t2.id')->get_all() );
		$this->assertEquals( $expected, self::qb()->from($this->table_name($table1).' as t1')->inner_join($this->table_name($table2).' as t2', 't1.id = t2.id')->get_all() );

		$expected = array(
			'1' => array('id' => '1', 'id2' => '11', 'id3' => '111'),
			'2' => array('id' => '2', 'id2' => '11', 'id3' => '111'),
		);
		$this->assertEquals( $expected, self::qb()->from($this->table_name($table2).' as t2')->join($this->table_name($table1).' as t1', 't1.id = t2.id')->get_all() );
		$this->assertEquals( $expected, self::qb()->from($this->table_name($table2).' as t2')->left_join($this->table_name($table1).' as t1', 't1.id = t2.id')->get_all() );
		$this->assertEquals( $expected, self::qb()->from($this->table_name($table2).' as t2')->right_join($this->table_name($table1).' as t1', 't1.id = t2.id')->get_all() );
		$this->assertEquals( $expected, self::qb()->from($this->table_name($table2).' as t2')->inner_join($this->table_name($table1).' as t1', 't1.id = t2.id')->get_all() );
	}
	public function test_group_by() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::db()->query($this->create_table_sql($table)) );
		$data = array(
			'1' => array('id' => '1', 'id2' => '11', 'id3' => '111'),
			'2' => array('id' => '2', 'id2' => '22', 'id3' => '222'),
			'3' => array('id' => '3', 'id2' => '11', 'id3' => '222'),
			'4' => array('id' => '4', 'id2' => '22', 'id3' => '333'),
		);
		$this->insert_safe($table, $data);

		$this->assertEquals( $data, self::qb()->from($this->table_name($table).' as t1')->group_by('id')->get_all() );
		$this->assertEquals( $data, self::qb()->from($this->table_name($table).' as t1')->group_by('t1.id')->get_all() );
		$expected = array(
			'1' => $data[1],
			'3' => $data[3],
			'2' => $data[2],
			'4' => $data[4],
		);
		$this->assertEquals( $expected, self::qb()->from($this->table_name($table).' as t1')->group_by('t1.id2', 't1.id3')->get_all() );
		$expected = array(
#			'1' => $data[1],
#			'2' => $data[2],
			'3' => $data[3],
			'4' => $data[4],
		);
		$this->assertEquals( $expected, self::qb()->from($this->table_name($table).' as t1')->group_by('t1.id2')->get_all() );
		$expected = array(
#			'1' => $data[1] + array('num' => '2'),
#			'2' => $data[2] + array('num' => '2'),
			'3' => $data[3] + array('num' => '2'),
			'4' => $data[4] + array('num' => '2'),
		);
		$this->assertEquals( $expected, self::qb()->from($this->table_name($table).' as t1')->select('*','COUNT(id2) as num')->group_by('t1.id2')->get_all() );
	}
	public function test_having() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::db()->query($this->create_table_sql($table)) );
		$data = array(
			'1' => array('id' => '1', 'id2' => '11', 'id3' => '111'),
			'2' => array('id' => '2', 'id2' => '22', 'id3' => '222'),
			'3' => array('id' => '3', 'id2' => '11', 'id3' => '222'),
			'4' => array('id' => '4', 'id2' => '22', 'id3' => '333'),
		);
		$this->insert_safe($table, $data);

#		$expected = array('1' => $data[1], '2' => $data[2]);
		$expected = array('3' => $data[3], '4' => $data[4]);
		$this->assertEquals( $expected, self::qb()->from($this->table_name($table).' as t1')->group_by('t1.id2')->get_all() );
#		$expected = array('2' => $data[2]);
		$expected = array('3' => $data[3]);
		$this->assertEquals( $expected, self::qb()->from($this->table_name($table).' as t1')->group_by('t1.id2')->having(array('id3','=','222'))->get_all() );
		$this->assertEquals( $expected, self::qb()->from($this->table_name($table).' as t1')->group_by('t1.id2')->having(array('t1.id3','=','222'))->get_all() );
		$this->assertEquals( $expected, self::qb()->from($this->table_name($table).' as t1')->group_by('t1.id2')->having('id3 = 222')->get_all() );
		$this->assertEquals( $expected, self::qb()->from($this->table_name($table).' as t1')->group_by('t1.id2')->having('t1.id3 = 222')->get_all() );
#		$this->assertEquals( $expected, self::qb()->from($this->table_name($table).' as t1')->group_by('t1.id2')->having('t1.id3 > 111')->get_all() );
		$this->assertEquals( array('3' => $data[3], '4' => $data[4]), self::qb()->from($this->table_name($table).' as t1')->group_by('t1.id2')->having('t1.id3 > 111')->get_all() );
	}
	public function test_order_by() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::db()->query($this->create_table_sql($table)) );
		$data = array(
			'1' => array('id' => '1', 'id2' => '11', 'id3' => '111'),
			'2' => array('id' => '2', 'id2' => '22', 'id3' => '222'),
			'3' => array('id' => '3', 'id2' => '11', 'id3' => '222'),
			'4' => array('id' => '4', 'id2' => '22', 'id3' => '333'),
		);
		$this->insert_safe($table, $data);

		$this->assertEquals( array_reverse($data, $preserve = true), self::qb()->from($this->table_name($table).' as t1')->order_by(array('id' => 'desc'))->get_all() );
		$this->assertEquals( array_reverse($data, $preserve = true), self::qb()->from($this->table_name($table).' as t1')->order_by(array('t1.id' => 'desc'))->get_all() );
		$this->assertEquals( array_reverse($data, $preserve = true), self::qb()->from($this->table_name($table).' as t1')->order_by('id desc')->get_all() );
		$this->assertEquals( array_reverse($data, $preserve = true), self::qb()->from($this->table_name($table).' as t1')->order_by('t1.id desc')->get_all() );
	}
	public function test_limit() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::db()->query($this->create_table_sql($table)) );
		$data = array(
			'1' => array('id' => '1', 'id2' => '11', 'id3' => '111'),
			'2' => array('id' => '2', 'id2' => '22', 'id3' => '222'),
			'3' => array('id' => '3', 'id2' => '11', 'id3' => '222'),
			'4' => array('id' => '4', 'id2' => '22', 'id3' => '333'),
		);
		$this->insert_safe($table, $data);

		$this->assertEquals( array('4' => $data[4]), self::qb()->from($this->table_name($table).' as t1')->order_by('t1.id desc')->limit(1)->get_all() );
		$this->assertEquals( array('2' => $data[2]), self::qb()->from($this->table_name($table).' as t1')->order_by('t1.id desc')->limit(1,2)->get_all() );
	}
	public function test_delete() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::db()->query($this->create_table_sql($table)) );
		$data = array(
			'1' => array('id' => '1', 'id2' => '11', 'id3' => '111'),
			'2' => array('id' => '2', 'id2' => '22', 'id3' => '222'),
			'3' => array('id' => '3', 'id2' => '11', 'id3' => '222'),
			'4' => array('id' => '4', 'id2' => '22', 'id3' => '333'),
		);
		$this->insert_safe($table, $data);

		$this->assertNotEmpty( self::qb()->from($this->table_name($table))->where('id > 1')->delete() );
		$this->assertEquals( array('1' => $data[1]), self::qb()->from($this->table_name($table))->get_all() );
		$this->assertNotEmpty( self::qb()->from($this->table_name($table))->whereid('1')->delete() );
		$this->assertEmpty( self::qb()->from($this->table_name($table))->get_all() );
// TODO: fix DELETE with AS ... == not allowed
#		$this->assertTrue( self::qb()->from($this->table_name($table).' as t1')->where('t1.id > 1')->delete() );
#		$this->assertEmpty( self::qb()->from($this->table_name($table).' as t1')->where('t1.id > 1')->delete() );
#		$this->assertEquals( array('1' => $data[1]), self::qb()->from($this->table_name($table).' as t1')->get_all() );
#		$this->assertTrue( self::qb()->from($this->table_name($table).' as t1')->whereid('1')->delete() );
#		$this->assertEmpty( self::qb()->from($this->table_name($table).' as t1')->get_all() );
	}
	public function test_update() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::db()->query($this->create_table_sql($table)) );
		$data = array(
			'1' => array('id' => '1', 'id2' => '11', 'id3' => '111'),
			'2' => array('id' => '2', 'id2' => '22', 'id3' => '222'),
			'3' => array('id' => '3', 'id2' => '11', 'id3' => '222'),
			'4' => array('id' => '4', 'id2' => '22', 'id3' => '333'),
		);
		$this->insert_safe($table, $data);

#		$this->assertFalse( self::qb()->update(array()) );
#		$data = array(
#			1 => array('name' => 'name1'),
#			2 => array('name' => 'name2'),
#		);
#		$this->assertEquals( '', self::qb()->from('user')->whereid(array(1,2,3))->update($data)->sql() );
	}
}
