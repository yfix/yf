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
		// These actions needed to ensure database is empty
		self::$db->query('DROP DATABASE IF EXISTS '.self::$DB_NAME);
		self::$db->query('CREATE DATABASE IF NOT EXISTS '.self::$DB_NAME);
	}
	public static function tearDownAfterClass() {
		self::$DB_DRIVER = self::$_bak['DB_DRIVER'];
	}
	public function _need_skip_test($name) {
		return false;
	}
	public function test_selects_basic() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::db()->query('CREATE TABLE '.self::$DB_NAME.'.'.$table.'(id INT(10) AUTO_INCREMENT, id2 INT(10), id3 INT(10), PRIMARY KEY(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8') );
		$data = array(
			'1' => array('id' => '1', 'id2' => '11', 'id3' => '111'),
			'2' => array('id' => '2', 'id2' => '22', 'id3' => '222'),
		);
		$this->assertNotEmpty( self::db()->insert_safe(self::$DB_NAME.'.'.$table, $data) );

		$this->assertSame( $data[1], self::db()->get('SELECT * FROM '.self::$DB_NAME.'.'.$table) );
		$this->assertSame( $data[1], self::db()->from(self::$DB_NAME.'.'.$table)->get() );
		$this->assertSame( $data[1], self::db()->select()->from(self::$DB_NAME.'.'.$table)->get() );
		$this->assertSame( $data[1], self::db()->select('*')->from(self::$DB_NAME.'.'.$table)->get() );
		$this->assertSame( $data[1], self::db()->select(array())->from(self::$DB_NAME.'.'.$table)->get() );
		$this->assertSame( $data[1], self::db()->select('id,id2,id3')->from(self::$DB_NAME.'.'.$table)->get() );
		$this->assertSame( $data[1], self::db()->select('id, id2, id3')->from(self::$DB_NAME.'.'.$table)->get() );
		$this->assertSame( $data[1], self::db()->select('id','id2','id3')->from(self::$DB_NAME.'.'.$table)->get() );
		$this->assertSame( $data[1], self::db()->select(array('id' => 'id','id2' => 'id2','id3' => 'id3'))->from(self::$DB_NAME.'.'.$table)->get() );

		$this->assertSame( $data, self::db()->get_all('SELECT * FROM '.self::$DB_NAME.'.'.$table) );
		$this->assertSame( $data, self::db()->from(self::$DB_NAME.'.'.$table)->get_all() );
		$this->assertSame( $data, self::db()->select()->from(self::$DB_NAME.'.'.$table)->get_all() );
		$this->assertSame( $data, self::db()->select('*')->from(self::$DB_NAME.'.'.$table)->get_all() );
		$this->assertSame( $data, self::db()->select(array())->from(self::$DB_NAME.'.'.$table)->get_all() );
		$this->assertSame( $data, self::db()->select('id,id2,id3')->from(self::$DB_NAME.'.'.$table)->get_all() );
		$this->assertSame( $data, self::db()->select('id, id2, id3')->from(self::$DB_NAME.'.'.$table)->get_all() );
		$this->assertSame( $data, self::db()->select('id','id2','id3')->from(self::$DB_NAME.'.'.$table)->get_all() );
		$this->assertSame( $data, self::db()->select(array('id' => 'id','id2' => 'id2','id3' => 'id3'))->from(self::$DB_NAME.'.'.$table)->get_all() );

		$this->assertSame( array('num' => '2'), self::db()->select('COUNT(id) AS num')->from(self::$DB_NAME.'.'.$table)->get() );
		$this->assertSame( '2', self::db()->select('COUNT(id)')->from(self::$DB_NAME.'.'.$table)->get_one() );
		$this->assertSame( '2', self::db()->select('COUNT(id) AS num')->from(self::$DB_NAME.'.'.$table)->get_one() );
		$this->assertSame( '3', self::db()->select('SUM(id)')->from(self::$DB_NAME.'.'.$table)->get_one() );
		$this->assertSame( '33', self::db()->select('SUM(id2)')->from(self::$DB_NAME.'.'.$table)->get_one() );
		$this->assertSame( '333', self::db()->select('SUM(id3)')->from(self::$DB_NAME.'.'.$table)->get_one() );
		$this->assertSame( '11', self::db()->select('MIN(id2)')->from(self::$DB_NAME.'.'.$table)->get_one() );
		$this->assertSame( '22', self::db()->select('MAX(id2)')->from(self::$DB_NAME.'.'.$table)->get_one() );
		$this->assertEquals( '1.5000', self::db()->select('AVG(id)')->from(self::$DB_NAME.'.'.$table)->get_one() );

		$this->assertSame( $data[1], self::db()->from(self::$DB_NAME.'.'.$table)->get() );
		$this->assertSame( $data[1], self::db()->from(self::$DB_NAME.'.'.$table.' as t1')->get() );
		$this->assertSame( $data[1], self::db()->from(array(self::$DB_NAME.'.'.$table => 't1'))->get() );
		$this->assertSame( $data[1], self::db()->select('t1.id, t1.id2, t1.id3')->from(self::$DB_NAME.'.'.$table.' as t1')->get() );
		$this->assertSame( $data[1], self::db()->select('t1.id','t1.id2','t1.id3')->from(self::$DB_NAME.'.'.$table.' as t1')->get() );
		$this->assertSame( $data[1], self::db()->select('t1.id as id','t1.id2 as id2','t1.id3 as id3')->from(self::$DB_NAME.'.'.$table.' as t1')->get() );
		$this->assertSame( $data[1], self::db()->select(array('t1.id' => 'id','t1.id2' => 'id2','t1.id3' => 'id3'))->from(self::$DB_NAME.'.'.$table.' as t1')->get() );
		$this->assertSame( array('fld1' => $data[1]['id']), self::db()->select('t1.id as fld1')->from(self::$DB_NAME.'.'.$table.' as t1')->get() );
	}
/*
	public function test_where() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::db()->query('CREATE TABLE '.self::$DB_NAME.'.'.$table.'(id INT(10) AUTO_INCREMENT, id2 INT(10), id3 INT(10), PRIMARY KEY(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8') );
		$data = array(
			'1' => array('id' => '1', 'id2' => '11', 'id3' => '111'),
			'2' => array('id' => '2', 'id2' => '22', 'id3' => '222'),
		);
		$this->assertNotEmpty( self::db()->insert_safe(self::$DB_NAME.'.'.$table, $data) );

		$this->assertSame( $data[1], self::qb()->from(self::$DB_NAME.'.'.$table)->where('id','=','1')->get() );
		$this->assertSame( $data[2], self::qb()->from(self::$DB_NAME.'.'.$table)->where('id','=','2')->get() );
		$this->assertEmpty( self::qb()->from(self::$DB_NAME.'.'.$table)->where('id','=','3')->get() );
		$this->assertSame( $data[2], self::qb()->from(self::$DB_NAME.'.'.$table)->where('id3','like','222')->get() );
		$this->assertSame( $data[2], self::qb()->from(self::$DB_NAME.'.'.$table)->where('id3','like','22%')->get() );
		$this->assertSame( $data[2], self::qb()->from(self::$DB_NAME.'.'.$table)->where('id3','like','22*')->get() );
		$this->assertSame( $data[2], self::qb()->from(self::$DB_NAME.'.'.$table)->where('id3','rlike','(222|222222)')->get() );
		$this->assertSame( $data[1], self::qb()->from(self::$DB_NAME.'.'.$table)->where('id3','not rlike','(222|222222)')->get() );

		$this->assertEmpty( self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->where(array('t1.id2' => '1*', 't1.id3' => '2*'))->get() );
		$this->assertSame( $data[1], self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->where(array('t1.id2' => '1*', 't1.id3' => '1*'))->get() );
		$this->assertSame( $data[2], self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->where(array('t1.id2' => '2*', 't1.id3' => '2*'))->get() );

		$this->assertSame( $data[1], self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->where('id = 1')->get() );
		$this->assertSame( $data[2], self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->where('t1.id > 1')->get() );
		$this->assertSame( $data[1], self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->where('id = 1')->where('id2 = 11')->where('id3 = 111')->get() );

		$this->assertEmpty( self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->where('t1.id = 789')->where_or('t1.id2 = 798')->where_or('t1.id3 = 888')->get() );
		$this->assertSame( $data[1], self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->where('t1.id = 789')->where_or('t1.id2 = 798')->where_or('t1.id3 = 111')->get() );

		$this->assertSame( $data[1], self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->whereid(1)->get() );
		$this->assertSame( $data[1], self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->whereid(1, 'id')->get() );
		$this->assertSame( $data[1], self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->whereid(1, 't1.id')->get() );

		$this->assertSame( $data, self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->whereid(array(1,2,3,4))->get_all() );
		$this->assertSame( $data, self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->whereid(array(1,2,3,4), 'id')->get_all() );
		$this->assertSame( $data, self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->whereid(array(1,2,3,4), 't1.id')->get_all() );
		$this->assertSame( $data, self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->where('t1.id', 'in', array(1,2,3,4))->get_all() );
		$this->assertSame( $data, self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->where('t1.id', 'not in', array(5,6,7))->get_all() );

		$this->assertEmpty( self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->whereid(array(4,5,6))->get_all() );
		$this->assertEmpty( self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->whereid(array(4,5,6), 'id')->get_all() );
		$this->assertEmpty( self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->whereid(array(4,5,6), 't1.id')->get_all() );
	}
	public function test_join() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table1 = self::utils()->db->DB_PREFIX. __FUNCTION__.'_1';
		$table2 = self::utils()->db->DB_PREFIX. __FUNCTION__.'_2';
		$this->assertNotEmpty( self::db()->query('CREATE TABLE '.self::$DB_NAME.'.'.$table1.'(id INT(10) AUTO_INCREMENT, id2 INT(10), id3 INT(10), PRIMARY KEY(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8') );
		$this->assertNotEmpty( self::db()->query('CREATE TABLE '.self::$DB_NAME.'.'.$table2.'(id INT(10) AUTO_INCREMENT, id2 INT(10), id4 INT(10), PRIMARY KEY(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8') );
		$data1 = array(
			'1' => array('id' => '1', 'id2' => '11', 'id3' => '111'),
			'2' => array('id' => '2', 'id2' => '11', 'id3' => '111'),
		);
		$data2 = array(
			'1' => array('id' => '1', 'id2' => '22', 'id4' => '444'),
			'2' => array('id' => '2', 'id2' => '22', 'id4' => '444'),
		);
		$this->assertNotEmpty( self::db()->insert_safe(self::$DB_NAME.'.'.$table1, $data1) );
		$this->assertNotEmpty( self::db()->insert_safe(self::$DB_NAME.'.'.$table2, $data2) );

		$expected = array(
			'1' => array('id' => '1', 'id2' => '22', 'id3' => '111', 'id4' => '444'),
			'2' => array('id' => '2', 'id2' => '22', 'id3' => '111', 'id4' => '444'),
		);
		$this->assertSame( $expected, self::qb()->from(self::$DB_NAME.'.'.$table1.' as t1')->join(self::$DB_NAME.'.'.$table2.' as t2', 't1.id = t2.id')->get_all() );
		$this->assertSame( $expected, self::qb()->from(self::$DB_NAME.'.'.$table1.' as t1')->left_join(self::$DB_NAME.'.'.$table2.' as t2', 't1.id = t2.id')->get_all() );
		$this->assertSame( $expected, self::qb()->from(self::$DB_NAME.'.'.$table1.' as t1')->right_join(self::$DB_NAME.'.'.$table2.' as t2', 't1.id = t2.id')->get_all() );
		$this->assertSame( $expected, self::qb()->from(self::$DB_NAME.'.'.$table1.' as t1')->inner_join(self::$DB_NAME.'.'.$table2.' as t2', 't1.id = t2.id')->get_all() );

		$expected = array(
			'1' => array('id' => '1', 'id2' => '11', 'id4' => '444', 'id3' => '111'),
			'2' => array('id' => '2', 'id2' => '11', 'id4' => '444', 'id3' => '111'),
		);
		$this->assertSame( $expected, self::qb()->from(self::$DB_NAME.'.'.$table2.' as t2')->join(self::$DB_NAME.'.'.$table1.' as t1', 't1.id = t2.id')->get_all() );
		$this->assertSame( $expected, self::qb()->from(self::$DB_NAME.'.'.$table2.' as t2')->left_join(self::$DB_NAME.'.'.$table1.' as t1', 't1.id = t2.id')->get_all() );
		$this->assertSame( $expected, self::qb()->from(self::$DB_NAME.'.'.$table2.' as t2')->right_join(self::$DB_NAME.'.'.$table1.' as t1', 't1.id = t2.id')->get_all() );
		$this->assertSame( $expected, self::qb()->from(self::$DB_NAME.'.'.$table2.' as t2')->inner_join(self::$DB_NAME.'.'.$table1.' as t1', 't1.id = t2.id')->get_all() );
	}
	public function test_group_by() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::db()->query('CREATE TABLE '.self::$DB_NAME.'.'.$table.'(id INT(10) AUTO_INCREMENT, id2 INT(10), id3 INT(10), PRIMARY KEY(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8') );
		$data = array(
			'1' => array('id' => '1', 'id2' => '11', 'id3' => '111'),
			'2' => array('id' => '2', 'id2' => '22', 'id3' => '222'),
			'3' => array('id' => '3', 'id2' => '11', 'id3' => '222'),
			'4' => array('id' => '4', 'id2' => '22', 'id3' => '333'),
		);
		$this->assertNotEmpty( self::db()->insert_safe(self::$DB_NAME.'.'.$table, $data) );

		$this->assertSame( $data, self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->group_by('id')->get_all() );
		$this->assertSame( $data, self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->group_by('t1.id')->get_all() );
		$expected = array(
			'1' => $data[1],
			'3' => $data[3],
			'2' => $data[2],
			'4' => $data[4],
		);
		$this->assertSame( $expected, self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->group_by('t1.id2', 't1.id3')->get_all() );
		$expected = array(
			'1' => $data[1],
			'2' => $data[2],
		);
		$this->assertSame( $expected, self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->group_by('t1.id2')->get_all() );
		$expected = array(
			'1' => $data[1] + array('num' => '2'),
			'2' => $data[2] + array('num' => '2'),
		);
		$this->assertSame( $expected, self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->select('*','COUNT(id2) as num')->group_by('t1.id2')->get_all() );
	}
	public function test_having() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::db()->query('CREATE TABLE '.self::$DB_NAME.'.'.$table.'(id INT(10) AUTO_INCREMENT, id2 INT(10), id3 INT(10), PRIMARY KEY(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8') );
		$data = array(
			'1' => array('id' => '1', 'id2' => '11', 'id3' => '111'),
			'2' => array('id' => '2', 'id2' => '22', 'id3' => '222'),
			'3' => array('id' => '3', 'id2' => '11', 'id3' => '222'),
			'4' => array('id' => '4', 'id2' => '22', 'id3' => '333'),
		);
		$this->assertNotEmpty( self::db()->insert_safe(self::$DB_NAME.'.'.$table, $data) );

		$expected = array('1' => $data[1], '2' => $data[2]);
		$this->assertSame( $expected, self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->group_by('t1.id2')->get_all() );
		$expected = array('2' => $data[2]);
		$this->assertSame( $expected, self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->group_by('t1.id2')->having(array('id3','=','222'))->get_all() );
		$this->assertSame( $expected, self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->group_by('t1.id2')->having(array('t1.id3','=','222'))->get_all() );
		$this->assertSame( $expected, self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->group_by('t1.id2')->having('id3 = 222')->get_all() );
		$this->assertSame( $expected, self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->group_by('t1.id2')->having('t1.id3 = 222')->get_all() );
		$this->assertSame( $expected, self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->group_by('t1.id2')->having('t1.id3 > 111')->get_all() );
	}
	public function test_order_by() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::db()->query('CREATE TABLE '.self::$DB_NAME.'.'.$table.'(id INT(10) AUTO_INCREMENT, id2 INT(10), id3 INT(10), PRIMARY KEY(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8') );
		$data = array(
			'1' => array('id' => '1', 'id2' => '11', 'id3' => '111'),
			'2' => array('id' => '2', 'id2' => '22', 'id3' => '222'),
			'3' => array('id' => '3', 'id2' => '11', 'id3' => '222'),
			'4' => array('id' => '4', 'id2' => '22', 'id3' => '333'),
		);
		$this->assertNotEmpty( self::db()->insert_safe(self::$DB_NAME.'.'.$table, $data) );

		$this->assertSame( array_reverse($data, $preserve = true), self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->order_by(array('id' => 'desc'))->get_all() );
		$this->assertSame( array_reverse($data, $preserve = true), self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->order_by(array('t1.id' => 'desc'))->get_all() );
		$this->assertSame( array_reverse($data, $preserve = true), self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->order_by('id desc')->get_all() );
		$this->assertSame( array_reverse($data, $preserve = true), self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->order_by('t1.id desc')->get_all() );
	}
	public function test_limit() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::db()->query('CREATE TABLE '.self::$DB_NAME.'.'.$table.'(id INT(10) AUTO_INCREMENT, id2 INT(10), id3 INT(10), PRIMARY KEY(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8') );
		$data = array(
			'1' => array('id' => '1', 'id2' => '11', 'id3' => '111'),
			'2' => array('id' => '2', 'id2' => '22', 'id3' => '222'),
			'3' => array('id' => '3', 'id2' => '11', 'id3' => '222'),
			'4' => array('id' => '4', 'id2' => '22', 'id3' => '333'),
		);
		$this->assertNotEmpty( self::db()->insert_safe(self::$DB_NAME.'.'.$table, $data) );

		$this->assertSame( array('4' => $data[4]), self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->order_by('t1.id desc')->limit(1)->get_all() );
		$this->assertSame( array('2' => $data[2]), self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->order_by('t1.id desc')->limit(1,2)->get_all() );
	}
	public function test_delete() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::db()->query('CREATE TABLE '.self::$DB_NAME.'.'.$table.'(id INT(10) AUTO_INCREMENT, id2 INT(10), id3 INT(10), PRIMARY KEY(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8') );
		$data = array(
			'1' => array('id' => '1', 'id2' => '11', 'id3' => '111'),
			'2' => array('id' => '2', 'id2' => '22', 'id3' => '222'),
			'3' => array('id' => '3', 'id2' => '11', 'id3' => '222'),
			'4' => array('id' => '4', 'id2' => '22', 'id3' => '333'),
		);
		$this->assertNotEmpty( self::db()->insert_safe(self::$DB_NAME.'.'.$table, $data) );

		$this->assertNotEmpty( self::qb()->from(self::$DB_NAME.'.'.$table)->where('id > 1')->delete() );
		$this->assertSame( array('1' => $data[1]), self::qb()->from(self::$DB_NAME.'.'.$table)->get_all() );
		$this->assertNotEmpty( self::qb()->from(self::$DB_NAME.'.'.$table)->whereid('1')->delete() );
		$this->assertEmpty( self::qb()->from(self::$DB_NAME.'.'.$table)->get_all() );
// TODO: fix DELETE with AS ... == not allowed
#		$this->assertTrue( self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->where('t1.id > 1')->delete() );
#		$this->assertEmpty( self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->where('t1.id > 1')->delete() );
#		$this->assertSame( array('1' => $data[1]), self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->get_all() );
#		$this->assertTrue( self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->whereid('1')->delete() );
#		$this->assertEmpty( self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->get_all() );
	}
	public function test_update() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::db()->query('CREATE TABLE '.self::$DB_NAME.'.'.$table.'(id INT(10) AUTO_INCREMENT, id2 INT(10), id3 INT(10), PRIMARY KEY(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8') );
		$data = array(
			'1' => array('id' => '1', 'id2' => '11', 'id3' => '111'),
			'2' => array('id' => '2', 'id2' => '22', 'id3' => '222'),
			'3' => array('id' => '3', 'id2' => '11', 'id3' => '222'),
			'4' => array('id' => '4', 'id2' => '22', 'id3' => '333'),
		);
		$this->assertNotEmpty( self::db()->insert_safe(self::$DB_NAME.'.'.$table, $data) );

#		$this->assertFalse( self::qb()->update(array()) );
#		$data = array(
#			1 => array('name' => 'name1'),
#			2 => array('name' => 'name2'),
#		);
#		$this->assertEquals( '', self::qb()->from('user')->whereid(array(1,2,3))->update($data)->sql() );
	}
*/
}