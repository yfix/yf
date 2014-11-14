<?php

require_once __DIR__.'/db_real_abstract.php';

/**
 * @requires extension mysql
 */
class class_db_real_query_builder_mysql_test extends db_real_abstract {
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
	public static function _need_skip_test($name) {
		if (defined('HHVM_VERSION') && getenv('TRAVIS') && getenv('CONTINUOUS_INTEGRATION')) {
			self::markTestSkipped('Right now we skip this test, when running inside travis-ci HHVM.');
			return true;
		}
		return false;
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
	public function test_selects_basic() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$t = $this->table_name($table);
		$this->assertTrue( (bool)self::db()->query($this->create_table_sql($table)) );
		$data = array(
			'1' => array('id' => '1', 'id2' => '11', 'id3' => '111'),
			'2' => array('id' => '2', 'id2' => '22', 'id3' => '222'),
		);
		$this->assertTrue( (bool)self::db()->insert_safe($t, $data) );

		$this->assertSame( $data[1], self::db()->get('SELECT * FROM '.$t) );
		$this->assertSame( $data[1], self::db()->from($t)->get() );
		$this->assertSame( $data[1], self::db()->select()->from($t)->get() );
		$this->assertSame( $data[1], self::db()->select('*')->from($t)->get() );
		$this->assertSame( $data[1], self::db()->select(array())->from($t)->get() );
		$this->assertSame( $data[1], self::db()->select('id,id2,id3')->from($t)->get() );
		$this->assertSame( $data[1], self::db()->select('id, id2, id3')->from($t)->get() );
		$this->assertSame( $data[1], self::db()->select('id','id2','id3')->from($t)->get() );
		$this->assertSame( $data[1], self::db()->select(array('id' => 'id','id2' => 'id2','id3' => 'id3'))->from($t)->get() );

		$this->assertSame( $data, self::db()->get_all('SELECT * FROM '.$t) );
		$this->assertSame( $data, self::db()->from($t)->get_all() );
		$this->assertSame( $data, self::db()->select()->from($t)->get_all() );
		$this->assertSame( $data, self::db()->select('*')->from($t)->get_all() );
		$this->assertSame( $data, self::db()->select(array())->from($t)->get_all() );
		$this->assertSame( $data, self::db()->select('id,id2,id3')->from($t)->get_all() );
		$this->assertSame( $data, self::db()->select('id, id2, id3')->from($t)->get_all() );
		$this->assertSame( $data, self::db()->select('id','id2','id3')->from($t)->get_all() );
		$this->assertSame( $data, self::db()->select(array('id' => 'id','id2' => 'id2','id3' => 'id3'))->from($t)->get_all() );

		$this->assertSame( array('num' => '2'), self::db()->select('COUNT(id) AS num')->from($t)->get() );
		$this->assertSame( '2', self::db()->select('COUNT(id)')->from($t)->get_one() );
		$this->assertSame( '2', self::db()->select('COUNT(id) AS num')->from($t)->get_one() );
		$this->assertSame( '3', self::db()->select('SUM(id)')->from($t)->get_one() );
		$this->assertSame( '33', self::db()->select('SUM(id2)')->from($t)->get_one() );
		$this->assertSame( '333', self::db()->select('SUM(id3)')->from($t)->get_one() );
		$this->assertSame( '11', self::db()->select('MIN(id2)')->from($t)->get_one() );
		$this->assertSame( '22', self::db()->select('MAX(id2)')->from($t)->get_one() );
		$this->assertEquals( '1.5000', self::db()->select('AVG(id)')->from($t)->get_one() );

		$this->assertSame( '2', self::db()->from($t)->count() );
		$this->assertSame( '2', self::db()->from($t)->count('id') );
		$this->assertSame( '3', self::db()->from($t)->sum() );
		$this->assertSame( '3', self::db()->from($t)->sum('id') );
		$this->assertSame( '33', self::db()->from($t)->sum('id2') );
		$this->assertSame( '333', self::db()->from($t)->sum('id3') );
		$this->assertSame( '11', self::db()->from($t)->min('id2') );
		$this->assertSame( '22', self::db()->from($t)->max('id2') );
		$this->assertEquals( '1.5000', self::db()->from($t)->avg() );
		$this->assertEquals( '1.5000', self::db()->from($t)->avg('id') );

		$this->assertSame( $data[1], self::db()->from($t)->get() );
		$this->assertSame( $data[1], self::db()->from($t.' as t1')->get() );
		$this->assertSame( $data[1], self::db()->from(array($t => 't1'))->get() );
		$this->assertSame( $data[1], self::db()->select('t1.id, t1.id2, t1.id3')->from($t.' as t1')->get() );
		$this->assertSame( $data[1], self::db()->select('t1.id','t1.id2','t1.id3')->from($t.' as t1')->get() );
		$this->assertSame( $data[1], self::db()->select('t1.id as id','t1.id2 as id2','t1.id3 as id3')->from($t.' as t1')->get() );
		$this->assertSame( $data[1], self::db()->select(array('t1.id' => 'id','t1.id2' => 'id2','t1.id3' => 'id3'))->from($t.' as t1')->get() );
		$this->assertSame( array('fld1' => $data[1]['id']), self::db()->select('t1.id as fld1')->from($t.' as t1')->get() );
	}
	public function test_where() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$t = $this->table_name($table);

		$this->assertTrue( (bool)self::db()->query($this->create_table_sql($table)) );
		$data = array(
			'1' => array('id' => '1', 'id2' => '11', 'id3' => '111'),
			'2' => array('id' => '2', 'id2' => '22', 'id3' => '222'),
		);
		$this->assertTrue( (bool)self::db()->insert_safe($t, $data) );

		$this->assertSame( $data[1], self::qb()->from($t)->where('id','=','1')->get() );
		$this->assertSame( $data[2], self::qb()->from($t)->where('id','=','2')->get() );
		$this->assertSame( $data[2], self::qb()->from($t)->where('id','2')->get() );
		$this->assertEmpty( self::qb()->from($t)->where('id','=','3')->get() );
		$this->assertSame( $data[2], self::qb()->from($t)->where('id3','like','222')->get() );
		$this->assertSame( $data[2], self::qb()->from($t)->where('id3','like','22%')->get() );
		$this->assertSame( $data[2], self::qb()->from($t)->where('id3','like','22*')->get() );
		$this->assertSame( $data[2], self::qb()->from($t)->where('id3','rlike','(222|222222)')->get() );
		$this->assertSame( $data[1], self::qb()->from($t)->where('id3','not rlike','(222|222222)')->get() );

		$this->assertEmpty( self::qb()->from($t.' as t1')->where(array('t1.id2' => '1*', 't1.id3' => '2*'))->get() );
		$this->assertSame( $data[1], self::qb()->from($t.' as t1')->where(array('t1.id2' => '1*', 't1.id3' => '1*'))->get() );
		$this->assertSame( $data[2], self::qb()->from($t.' as t1')->where(array('t1.id2' => '2*', 't1.id3' => '2*'))->get() );
		$this->assertSame( $data[2], self::qb()->from($t.' as t1')->where(array('t1.id2' => '', 't1.id3' => '2*'))->get() );
		$this->assertSame( $data[2], self::qb()->from($t.' as t1')->where(array('t1.id2' => '2*', 't1.id3' => ''))->get() );

		$this->assertSame( $data[1], self::qb()->from($t.' as t1')->where('id = 1')->get() );
		$this->assertSame( $data[2], self::qb()->from($t.' as t1')->where('t1.id > 1')->get() );
		$this->assertSame( $data[1], self::qb()->from($t.' as t1')->where('id = 1')->where('id2 = 11')->where('id3 = 111')->get() );

		$this->assertEmpty( self::qb()->from($t.' as t1')->where('t1.id = 789')->where_or('t1.id2 = 798')->where_or('t1.id3 = 888')->get() );
		$this->assertSame( $data[1], self::qb()->from($t.' as t1')->where('t1.id = 789')->where_or('t1.id2 = 798')->where_or('t1.id3 = 111')->get() );

		$this->assertSame( $data[1], self::qb()->from($t.' as t1')->whereid(1)->get() );
		$this->assertSame( $data[1], self::qb()->from($t.' as t1')->whereid(1, 'id')->get() );
		$this->assertSame( $data[1], self::qb()->from($t.' as t1')->whereid(1, 't1.id')->get() );

		$this->assertSame( $data, self::qb()->from($t.' as t1')->whereid(array(1,2,3,4))->get_all() );
		$this->assertSame( $data, self::qb()->from($t.' as t1')->whereid(array(1,2,3,4), 'id')->get_all() );
		$this->assertSame( $data, self::qb()->from($t.' as t1')->whereid(array(1,2,3,4), 't1.id')->get_all() );
		$this->assertSame( $data, self::qb()->from($t.' as t1')->where('t1.id', 'in', array(1,2,3,4))->get_all() );
		$this->assertSame( $data, self::qb()->from($t.' as t1')->where('t1.id', 'not in', array(5,6,7))->get_all() );
		$this->assertSame( $data, self::qb()->from($t.' as t1')->whereid(1,2,3,4)->get_all() );
		$this->assertSame( $data, self::qb()->from($t.' as t1')->whereid(1,2,3,4, 'id')->get_all() );
		$this->assertSame( $data, self::qb()->from($t.' as t1')->whereid(1,2,3,4, 't1.id')->get_all() );

		$this->assertEmpty( self::qb()->from($t.' as t1')->whereid(array(4,5,6))->get_all() );
		$this->assertEmpty( self::qb()->from($t.' as t1')->whereid(array(4,5,6), 'id')->get_all() );
		$this->assertEmpty( self::qb()->from($t.' as t1')->whereid(array(4,5,6), 't1.id')->get_all() );
		$this->assertEmpty( self::qb()->from($t.' as t1')->whereid(4,5,6)->get_all() );
		$this->assertEmpty( self::qb()->from($t.' as t1')->whereid(4,5,6, 'id')->get_all() );
		$this->assertEmpty( self::qb()->from($t.' as t1')->whereid(4,5,6, 't1.id')->get_all() );

		$this->assertSame( $data[1], self::qb()->from($t)->first() );
		$this->assertSame( $data[2], self::qb()->from($t)->last() );

		$this->assertNull( self::qb()->from($t)->where_between('id2', 1000, 1001)->all() );
		$this->assertSame( $data, self::qb()->from($t)->where_between('id2', 1, 1001)->all() );
		$this->assertSame( array('1' => $data[1]), self::qb()->from($t)->where_between('id2', 10, 12)->all() );
		$this->assertSame( array('2' => $data[2]), self::qb()->from($t)->where_between('id2', 21, 22)->all() );
	}
	public function test_where_null() {
// TODO: where_null
// TODO: where_not_null
	}
	public function test_chunk() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$t = $this->table_name($table);

		$this->assertTrue( (bool)self::db()->query($this->create_table_sql($table)) );
		$data = array(
			'1' => array('id' => '1', 'id2' => '11', 'id3' => '111'),
			'2' => array('id' => '2', 'id2' => '22', 'id3' => '222'),
			'3' => array('id' => '3', 'id2' => '33', 'id3' => '333'),
		);
		$this->assertTrue( (bool)self::db()->insert_safe($t, $data) );

		$out = array();
		$this->assertTrue( (bool)self::qb()->from($t)->chunk(1, function($data) use (&$out) { $out[] = $data; }) );
		$this->assertSame( array(array($data[1]), array($data[2]), array($data[3])), $out );

		$out = array();
		$this->assertTrue( (bool)self::qb()->from($t)->chunk(2, function($data) use (&$out) { $out[] = $data; }) );
		$this->assertSame( array(array($data[1], $data[2]), array($data[3])), $out );

		$out = array();
		$this->assertTrue( (bool)self::qb()->from($t)->chunk(3, function($data) use (&$out) { $out[] = $data; }) );
		$this->assertSame( array(array($data[1], $data[2], $data[3])), $out );

		$out = array();
		$this->assertTrue( (bool)self::qb()->from($t)->chunk(100, function($data) use (&$out) { $out[] = $data; }) );
		$this->assertSame( array(array($data[1], $data[2], $data[3])), $out );
	}
	public function test_join() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table1 = self::utils()->db->DB_PREFIX. __FUNCTION__.'_1';
		$table2 = self::utils()->db->DB_PREFIX. __FUNCTION__.'_2';
		$t1 = $this->table_name($table1);
		$t2 = $this->table_name($table2);

		$this->assertTrue( (bool)self::db()->query($this->create_table_sql($table1)) );
		$this->assertTrue( (bool)self::db()->query($this->create_table_sql($table2)) );
		$data1 = array(
			'1' => array('id' => '1', 'id2' => '11', 'id3' => '111'),
			'2' => array('id' => '2', 'id2' => '11', 'id3' => '111'),
		);
		$data2 = array(
			'1' => array('id' => '1', 'id2' => '22', 'id3' => '444'),
			'2' => array('id' => '2', 'id2' => '22', 'id3' => '444'),
		);
		$this->assertTrue( (bool)self::db()->insert_safe($t1, $data1) );
		$this->assertTrue( (bool)self::db()->insert_safe($t2, $data2) );

		$expected = array(
			'1' => array('id' => '1', 'id2' => '22', 'id3' => '444'),
			'2' => array('id' => '2', 'id2' => '22', 'id3' => '444'),
		);
		$this->assertSame( $expected, self::qb()->from($t1.' as t1')->join($t2.' as t2', 't1.id = t2.id')->get_all() );
		$this->assertSame( $expected, self::qb()->from($t1.' as t1')->left_join($t2.' as t2', 't1.id = t2.id')->get_all() );
		$this->assertSame( $expected, self::qb()->from($t1.' as t1')->right_join($t2.' as t2', 't1.id = t2.id')->get_all() );
		$this->assertSame( $expected, self::qb()->from($t1.' as t1')->inner_join($t2.' as t2', 't1.id = t2.id')->get_all() );

		$expected = array(
			'1' => array('id' => '1', 'id2' => '11', 'id3' => '111'),
			'2' => array('id' => '2', 'id2' => '11', 'id3' => '111'),
		);
		$this->assertSame( $expected, self::qb()->from($t2.' as t2')->join($t1.' as t1', 't1.id = t2.id')->get_all() );
		$this->assertSame( $expected, self::qb()->from($t2.' as t2')->left_join($t1.' as t1', 't1.id = t2.id')->get_all() );
		$this->assertSame( $expected, self::qb()->from($t2.' as t2')->right_join($t1.' as t1', 't1.id = t2.id')->get_all() );
		$this->assertSame( $expected, self::qb()->from($t2.' as t2')->inner_join($t1.' as t1', 't1.id = t2.id')->get_all() );
	}
	public function test_group_by() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$t = $this->table_name($table);

		$this->assertTrue( (bool)self::db()->query($this->create_table_sql($table)) );
		$data = array(
			'1' => array('id' => '1', 'id2' => '11', 'id3' => '111'),
			'2' => array('id' => '2', 'id2' => '22', 'id3' => '222'),
			'3' => array('id' => '3', 'id2' => '11', 'id3' => '222'),
			'4' => array('id' => '4', 'id2' => '22', 'id3' => '333'),
		);
		$this->assertTrue( (bool)self::db()->insert_safe($t, $data) );

		$this->assertSame( $data, self::qb()->from($t.' as t1')->group_by('id')->get_all() );
		$this->assertSame( $data, self::qb()->from($t.' as t1')->group_by('t1.id')->get_all() );
		$expected = array(
			'1' => $data[1],
			'3' => $data[3],
			'2' => $data[2],
			'4' => $data[4],
		);
		$this->assertSame( $expected, self::qb()->from($t.' as t1')->group_by('t1.id2', 't1.id3')->get_all() );
		$expected = array(
			'1' => $data[1],
			'2' => $data[2],
		);
		$this->assertSame( $expected, self::qb()->from($t.' as t1')->group_by('t1.id2')->get_all() );
		$expected = array(
			'1' => $data[1] + array('num' => '2'),
			'2' => $data[2] + array('num' => '2'),
		);
		$this->assertSame( $expected, self::qb()->from($t.' as t1')->select('*','COUNT(id2) as num')->group_by('t1.id2')->get_all() );
	}
	public function test_having() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$t = $this->table_name($table);

		$this->assertTrue( (bool)self::db()->query($this->create_table_sql($table)) );
		$data = array(
			'1' => array('id' => '1', 'id2' => '11', 'id3' => '111'),
			'2' => array('id' => '2', 'id2' => '22', 'id3' => '222'),
			'3' => array('id' => '3', 'id2' => '11', 'id3' => '222'),
			'4' => array('id' => '4', 'id2' => '22', 'id3' => '333'),
		);
		$this->assertTrue( (bool)self::db()->insert_safe($t, $data) );

		$expected = array('1' => $data[1], '2' => $data[2]);
		$this->assertSame( $expected, self::qb()->from($t.' as t1')->group_by('t1.id2')->get_all() );
		$expected = array('2' => $data[2]);
		$this->assertSame( $expected, self::qb()->from($t.' as t1')->group_by('t1.id2')->having(array('id3','=','222'))->get_all() );
		$this->assertSame( $expected, self::qb()->from($t.' as t1')->group_by('t1.id2')->having(array('t1.id3','=','222'))->get_all() );
		$this->assertSame( $expected, self::qb()->from($t.' as t1')->group_by('t1.id2')->having('id3 = 222')->get_all() );
		$this->assertSame( $expected, self::qb()->from($t.' as t1')->group_by('t1.id2')->having('t1.id3 = 222')->get_all() );
		$this->assertSame( $expected, self::qb()->from($t.' as t1')->group_by('t1.id2')->having('t1.id3 > 111')->get_all() );
	}
	public function test_order_by() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$t = $this->table_name($table);

		$this->assertTrue( (bool)self::db()->query($this->create_table_sql($table)) );
		$data = array(
			'1' => array('id' => '1', 'id2' => '11', 'id3' => '111'),
			'2' => array('id' => '2', 'id2' => '22', 'id3' => '222'),
			'3' => array('id' => '3', 'id2' => '11', 'id3' => '222'),
			'4' => array('id' => '4', 'id2' => '22', 'id3' => '333'),
		);
		$this->assertTrue( (bool)self::db()->insert_safe($t, $data) );

		$this->assertSame( array_reverse($data, $preserve = true), self::qb()->from($t.' as t1')->order_by(array('id' => 'desc'))->get_all() );
		$this->assertSame( array_reverse($data, $preserve = true), self::qb()->from($t.' as t1')->order_by(array('t1.id' => 'desc'))->get_all() );
		$this->assertSame( array_reverse($data, $preserve = true), self::qb()->from($t.' as t1')->order_by('id desc')->get_all() );
		$this->assertSame( array_reverse($data, $preserve = true), self::qb()->from($t.' as t1')->order_by('t1.id desc')->get_all() );
	}
	public function test_limit() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$t = $this->table_name($table);

		$this->assertTrue( (bool)self::db()->query($this->create_table_sql($table)) );
		$data = array(
			'1' => array('id' => '1', 'id2' => '11', 'id3' => '111'),
			'2' => array('id' => '2', 'id2' => '22', 'id3' => '222'),
			'3' => array('id' => '3', 'id2' => '11', 'id3' => '222'),
			'4' => array('id' => '4', 'id2' => '22', 'id3' => '333'),
		);
		$this->assertTrue( (bool)self::db()->insert_safe($t, $data) );

		$this->assertSame( array('4' => $data[4]), self::qb()->from($t.' as t1')->order_by('t1.id desc')->limit(1)->get_all() );
		$this->assertSame( array('2' => $data[2]), self::qb()->from($t.' as t1')->order_by('t1.id desc')->limit(1,2)->get_all() );
	}
	public function test_delete() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$t = $this->table_name($table);

		$this->assertTrue( (bool)self::db()->query($this->create_table_sql($table)) );
		$data = array(
			'1' => array('id' => '1', 'id2' => '11', 'id3' => '111'),
			'2' => array('id' => '2', 'id2' => '22', 'id3' => '222'),
			'3' => array('id' => '3', 'id2' => '11', 'id3' => '222'),
			'4' => array('id' => '4', 'id2' => '22', 'id3' => '333'),
		);
		$this->assertTrue( (bool)self::db()->insert_safe($t, $data) );

		$this->assertTrue( (bool)self::qb()->from($t)->where('id > 1')->delete() );
		$this->assertSame( array('1' => $data[1]), self::qb()->from($t)->get_all() );
		$this->assertTrue( (bool)self::qb()->from($t)->whereid('1')->delete() );
		$this->assertFalse( (bool)self::qb()->from($t)->get_all() );

		$this->assertTrue( (bool)self::db()->insert_safe($t, $data) );

		$this->assertTrue( (bool)self::qb()->from($t.' as t1')->where('id > 1')->delete() );
		$this->assertSame( array('1' => $data[1]), self::qb()->from($t.' as t1')->get_all() );
		$this->assertTrue( (bool)self::qb()->from($t.' as t1')->whereid('1')->delete() );
		$this->assertFalse( (bool)self::qb()->from($t.' as t1')->get_all() );
	}
	public function test_insert() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$t = $this->table_name($table);

		$this->assertTrue( (bool)self::db()->query($this->create_table_sql($table)) );
		$data = array(
			'1' => array('id' => '1', 'id2' => '11', 'id3' => '111'),
			'2' => array('id' => '2', 'id2' => '22', 'id3' => '222'),
		);
		$this->assertSame( 2, (int)self::qb()->table($t)->insert($data) );
		$this->assertSame( 2, (int)self::db()->from($t)->count() );
	}
	public function test_insert_into() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table1 = self::utils()->db->DB_PREFIX. __FUNCTION__.'_1';
		$table2 = self::utils()->db->DB_PREFIX. __FUNCTION__.'_2';
		$t1 = $this->table_name($table1);
		$t2 = $this->table_name($table2);

		$this->assertTrue( (bool)self::db()->query($this->create_table_sql($table1)) );
		$this->assertTrue( (bool)self::db()->query($this->create_table_sql($table2)) );
		$data = array(
			'1' => array('id' => '1', 'id2' => '11', 'id3' => '111'),
			'2' => array('id' => '2', 'id2' => '22', 'id3' => '222'),
		);
		$this->assertTrue( (bool)self::db()->insert_safe($t1, $data) );
		$this->assertSame( count($data), (int)self::db()->from($t1)->count() );
		$this->assertSame( 0, (int)self::db()->from($t2)->count() );

		$this->assertTrue( (bool)self::qb()->from($t1)->insert_into($t2) );
		$this->assertSame( count($data), (int)self::db()->from($t1)->count() );
		$this->assertSame( count($data), (int)self::db()->from($t2)->count() );
		$this->assertSame( $data, self::db()->from($t2)->all() );

		$this->assertTrue( (bool)self::qb()->from($t2)->delete() );
		$this->assertSame( 0, (int)self::db()->from($t2)->count() );
		$this->assertTrue( (bool)self::qb()->from($t1)->where('id2 > 20')->insert_into($t2) );
		$this->assertSame( array('2' => $data[2]), self::db()->from($t2)->all() );
	}
	public function test_update() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$t = $this->table_name($table);

		$this->assertTrue( (bool)self::db()->query($this->create_table_sql($table)) );
		$data = array(
			'1' => array('id' => '1', 'id2' => '11', 'id3' => '111'),
			'2' => array('id' => '2', 'id2' => '22', 'id3' => '222'),
			'3' => array('id' => '3', 'id2' => '11', 'id3' => '222'),
			'4' => array('id' => '4', 'id2' => '22', 'id3' => '333'),
		);
		$this->assertTrue( (bool)self::db()->insert_safe($t, $data) );
		$this->assertSame( count($data), (int)self::db()->from($t)->count() );

		$this->assertTrue( (bool)self::qb()->table($t)->update(array('id2' => '1111')) );
		$expected = array(
			array('id2' => '1111'),
			array('id2' => '1111'),
			array('id2' => '1111'),
			array('id2' => '1111')
		);
		$this->assertSame( $expected, self::db()->select('id2')->from($t)->all() );

		$this->assertTrue( (bool)self::qb()->from($t)->delete() );
		$this->assertSame( 0, (int)self::db()->from($t)->count() );
		$this->assertTrue( (bool)self::db()->insert_safe($t, $data) );
		$this->assertSame( count($data), (int)self::db()->from($t)->count() );
		$this->assertTrue( (bool)self::qb()->table($t)->whereid(2)->update(array('id2' => '1111')) );
		$expected = array(
			array('id2' => $data[1]['id2']),
			array('id2' => '1111'),
			array('id2' => $data[3]['id2']),
			array('id2' => $data[4]['id2'])
		);
		$this->assertSame( $expected, self::db()->select('id2')->from($t)->all() );
		$this->assertTrue( (bool)self::qb()->table($t)->where('id > 2')->limit(1)->update(array('id2' => '1111')) );
		$expected = array(
			array('id2' => $data[1]['id2']),
			array('id2' => '1111'),
			array('id2' => '1111'),
			array('id2' => $data[4]['id2'])
		);
		$this->assertSame( $expected, self::db()->select('id2')->from($t)->all() );
		$this->assertTrue( (bool)self::qb()->table($t)->where('id >= 2')->limit(10)->update(array('id2' => '5555')) );
		$expected = array(
			array('id2' => $data[1]['id2']),
			array('id2' => '5555'),
			array('id2' => '5555'),
			array('id2' => '5555')
		);
		$this->assertSame( $expected, self::db()->select('id2')->from($t)->all() );
	}
	public function test_update_batch() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$t = $this->table_name($table);

		$this->assertTrue( (bool)self::db()->query($this->create_table_sql($table)) );
		$data = array(
			'1' => array('id' => '1', 'id2' => '11', 'id3' => '111'),
			'2' => array('id' => '2', 'id2' => '22', 'id3' => '222'),
			'3' => array('id' => '3', 'id2' => '11', 'id3' => '222'),
			'4' => array('id' => '4', 'id2' => '22', 'id3' => '333'),
		);
		$this->assertTrue( (bool)self::db()->insert_safe($t, $data) );
		$this->assertSame( $data, self::db()->from($t)->all() );
		$new_data = $data;
		$new_data['2']['id2'] = '555';
		$new_data['4']['id2'] = '555';
		$this->assertNotSame( $data, $new_data );
		$this->assertTrue( (bool)self::qb()->table($t)->update_batch($t, $new_data, null) );
		$this->assertSame( $new_data, self::db()->from($t)->all() );
		$this->assertTrue( (bool)self::qb()->table($t)->update($data) );
		$this->assertSame( $data, self::db()->from($t)->all() );
		$this->assertTrue( (bool)self::qb()->table($t)->update_batch($t, $new_data, 'id') );
		$this->assertSame( $new_data, self::db()->from($t)->all() );
		$this->assertTrue( (bool)self::qb()->table($t)->update_batch($t, $data, array('id','id3')) );
		$this->assertSame( $data, self::db()->from($t)->all() );
	}
/*
	public function test_increment() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }

		$this->assertEquals( 'UPDATE `'.DB_PREFIX.'user` SET `visits` = `visits` + 1', self::qb()->table('user')->increment('visits', null, $sql = true) );
		$this->assertEquals( 'UPDATE `'.DB_PREFIX.'user` SET `visits` = `visits` + 1', self::qb()->table('user')->increment('visits', 1, $sql = true) );
		$this->assertEquals( 'UPDATE `'.DB_PREFIX.'user` SET `visits` = `visits` + 5', self::qb()->table('user')->increment('visits', 5, $sql = true) );
		$this->assertEquals( 'UPDATE `'.DB_PREFIX.'user` SET `visits` = `visits` + 500', self::qb()->table('user')->increment('visits', 500, $sql = true) );
		$this->assertEquals( 'UPDATE `'.DB_PREFIX.'user` SET `visits` = `visits` - 5', self::qb()->table('user')->increment('visits', -5, $sql = true) );

		$this->assertEquals( 'UPDATE `'.DB_PREFIX.'user` SET `visits` = `visits` + 1 WHERE `id` = \'1\'', self::qb()->table('user')->whereid(1)->increment('visits', null, $sql = true) );
		$this->assertEquals( 'UPDATE `'.DB_PREFIX.'user` SET `visits` = `visits` + 1 WHERE `id` = \'1\'', self::qb()->table('user')->whereid(1)->increment('visits', 1, $sql = true) );
		$this->assertEquals( 'UPDATE `'.DB_PREFIX.'user` SET `visits` = `visits` + 5 WHERE `id` = \'1\'', self::qb()->table('user')->whereid(1)->increment('visits', 5, $sql = true) );
		$this->assertEquals( 'UPDATE `'.DB_PREFIX.'user` SET `visits` = `visits` - 5 WHERE `id` = \'1\'', self::qb()->table('user')->whereid(1)->increment('visits', -5, $sql = true) );

		$this->assertEquals( 'UPDATE `'.DB_PREFIX.'user` SET `visits` = `visits` + 5 LIMIT 1', self::qb()->table('user')->limit(1)->increment('visits', 5, $sql = true) );
		$this->assertEquals( 'UPDATE `'.DB_PREFIX.'user` SET `visits` = `visits` + 5 WHERE `id` = \'1\' LIMIT 1', self::qb()->table('user')->whereid(1)->limit(1)->increment('visits', 5, $sql = true) );

		$this->assertEquals( 'UPDATE `'.DB_PREFIX.'user` SET `u`.`visits` = `u`.`visits` + 5 WHERE `u`.`id` = \'1\' LIMIT 1', self::qb()->table('user as u')->where('u.id', 1)->limit(1)->increment('u.visits', 5, $sql = true) );
	}
	public function test_decrement() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }

		$this->assertEquals( 'UPDATE `'.DB_PREFIX.'user` SET `visits` = `visits` - 1', self::qb()->table('user')->decrement('visits', null, $sql = true) );
		$this->assertEquals( 'UPDATE `'.DB_PREFIX.'user` SET `visits` = `visits` - 1', self::qb()->table('user')->decrement('visits', 1, $sql = true) );
		$this->assertEquals( 'UPDATE `'.DB_PREFIX.'user` SET `visits` = `visits` - 5', self::qb()->table('user')->decrement('visits', 5, $sql = true) );
		$this->assertEquals( 'UPDATE `'.DB_PREFIX.'user` SET `visits` = `visits` - 500', self::qb()->table('user')->decrement('visits', 500, $sql = true) );
		$this->assertEquals( 'UPDATE `'.DB_PREFIX.'user` SET `visits` = `visits` + 5', self::qb()->table('user')->decrement('visits', -5, $sql = true) );

		$this->assertEquals( 'UPDATE `'.DB_PREFIX.'user` SET `visits` = `visits` - 1 WHERE `id` = \'1\'', self::qb()->table('user')->whereid(1)->decrement('visits', null, $sql = true) );
		$this->assertEquals( 'UPDATE `'.DB_PREFIX.'user` SET `visits` = `visits` - 1 WHERE `id` = \'1\'', self::qb()->table('user')->whereid(1)->decrement('visits', 1, $sql = true) );
		$this->assertEquals( 'UPDATE `'.DB_PREFIX.'user` SET `visits` = `visits` - 5 WHERE `id` = \'1\'', self::qb()->table('user')->whereid(1)->decrement('visits', 5, $sql = true) );
		$this->assertEquals( 'UPDATE `'.DB_PREFIX.'user` SET `visits` = `visits` + 5 WHERE `id` = \'1\'', self::qb()->table('user')->whereid(1)->decrement('visits', -5, $sql = true) );

		$this->assertEquals( 'UPDATE `'.DB_PREFIX.'user` SET `visits` = `visits` - 5 LIMIT 1', self::qb()->table('user')->limit(1)->decrement('visits', 5, $sql = true) );
		$this->assertEquals( 'UPDATE `'.DB_PREFIX.'user` SET `visits` = `visits` - 5 WHERE `id` = \'1\' LIMIT 1', self::qb()->table('user')->whereid(1)->limit(1)->decrement('visits', 5, $sql = true) );

		$this->assertEquals( 'UPDATE `'.DB_PREFIX.'user` SET `u`.`visits` = `u`.`visits` - 5 WHERE `u`.`id` = \'1\' LIMIT 1', self::qb()->table('user as u')->where('u.id', 1)->limit(1)->decrement('u.visits', 5, $sql = true) );
	}
	public function test_union() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$first = self::qb()->from('users')->where_null('first_name');
		$this->assertEquals('SELECT * FROM `'.DB_PREFIX.'users` WHERE `last_name` IS NULL UNION ('.PHP_EOL.'SELECT * FROM `'.DB_PREFIX.'users` WHERE `first_name` IS NULL'.PHP_EOL.')',
			self::qb()->from('users')->where_null('last_name')->union($first)->sql()
		);
	}
	public function test_union_all() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$first = self::qb()->from('users')->where_null('first_name');
		$this->assertEquals('SELECT * FROM `'.DB_PREFIX.'users` WHERE `last_name` IS NULL UNION ALL ('.PHP_EOL.'SELECT * FROM `'.DB_PREFIX.'users` WHERE `first_name` IS NULL'.PHP_EOL.')',
			self::qb()->from('users')->where_null('last_name')->union_all($first)->sql()
		);
	}
	public function test_where_any() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$first = self::qb()->select('gid')->from('groups')->where_not_null('active');
		$this->assertEquals('SELECT * FROM `'.DB_PREFIX.'users` WHERE `group_id` = ANY ('.PHP_EOL.'SELECT `gid` FROM `'.DB_PREFIX.'groups` WHERE `active` IS NOT NULL'.PHP_EOL.')',
			self::qb()->from('users')->where_any('group_id', '=', $first)->sql()
		);
		$this->assertEquals('SELECT * FROM `'.DB_PREFIX.'users` WHERE `group_id` > ANY ('.PHP_EOL.'SELECT `gid` FROM `'.DB_PREFIX.'groups` WHERE `active` IS NOT NULL'.PHP_EOL.')',
			self::qb()->from('users')->where_any('group_id', '>', $first)->sql()
		);
	}
	public function test_where_all() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$first = self::qb()->select('gid')->from('groups')->where_not_null('active');
		$this->assertEquals('SELECT * FROM `'.DB_PREFIX.'users` WHERE `group_id` > ALL ('.PHP_EOL.'SELECT `gid` FROM `'.DB_PREFIX.'groups` WHERE `active` IS NOT NULL'.PHP_EOL.')',
			self::qb()->from('users')->where_all('group_id', '>', $first)->sql()
		);
	}
	public function test_where_exists() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$first = self::qb()->select('gid')->from('groups')->where_not_null('active');
		$this->assertEquals('SELECT * FROM `'.DB_PREFIX.'users` WHERE EXISTS ('.PHP_EOL.'SELECT `gid` FROM `'.DB_PREFIX.'groups` WHERE `active` IS NOT NULL'.PHP_EOL.')',
			self::qb()->from('users')->where_exists($first)->sql()
		);
	}
	public function test_where_not_exists() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$first = self::qb()->select('gid')->from('groups')->where_not_null('active');
		$this->assertEquals('SELECT * FROM `'.DB_PREFIX.'users` WHERE NOT EXISTS ('.PHP_EOL.'SELECT `gid` FROM `'.DB_PREFIX.'groups` WHERE `active` IS NOT NULL'.PHP_EOL.')',
			self::qb()->from('users')->where_not_exists($first)->sql()
		);
	}
*/
}