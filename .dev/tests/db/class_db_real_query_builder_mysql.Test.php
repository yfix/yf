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
	public function _need_skip_test($name) {
		if (defined('HHVM_VERSION') && getenv('TRAVIS') && getenv('CONTINUOUS_INTEGRATION')) {
			$this->markTestSkipped('Right now we skip this test, when running inside travis-ci HHVM.');
			return true;
		}
		return false;
	}
	public function db_name() {
		return self::$DB_NAME;
	}
	public function table_name($name) {
		return self::db_name().'.'.$name;
	}
	public function test_selects_basic() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::db()->query('CREATE TABLE '.$this->table_name($table).'(id INT(10) AUTO_INCREMENT, id2 INT(10), id3 INT(10), PRIMARY KEY(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8') );
		$data = array(
			'1' => array('id' => '1', 'id2' => '11', 'id3' => '111'),
			'2' => array('id' => '2', 'id2' => '22', 'id3' => '222'),
		);
		$this->assertNotEmpty( self::db()->insert_safe($this->table_name($table), $data) );

		$this->assertSame( $data[1], self::db()->get('SELECT * FROM '.$this->table_name($table)) );
		$this->assertSame( $data[1], self::db()->from($this->table_name($table))->get() );
		$this->assertSame( $data[1], self::db()->select()->from($this->table_name($table))->get() );
		$this->assertSame( $data[1], self::db()->select('*')->from($this->table_name($table))->get() );
		$this->assertSame( $data[1], self::db()->select(array())->from($this->table_name($table))->get() );
		$this->assertSame( $data[1], self::db()->select('id,id2,id3')->from($this->table_name($table))->get() );
		$this->assertSame( $data[1], self::db()->select('id, id2, id3')->from($this->table_name($table))->get() );
		$this->assertSame( $data[1], self::db()->select('id','id2','id3')->from($this->table_name($table))->get() );
		$this->assertSame( $data[1], self::db()->select(array('id' => 'id','id2' => 'id2','id3' => 'id3'))->from($this->table_name($table))->get() );

		$this->assertSame( $data, self::db()->get_all('SELECT * FROM '.$this->table_name($table)) );
		$this->assertSame( $data, self::db()->from($this->table_name($table))->get_all() );
		$this->assertSame( $data, self::db()->select()->from($this->table_name($table))->get_all() );
		$this->assertSame( $data, self::db()->select('*')->from($this->table_name($table))->get_all() );
		$this->assertSame( $data, self::db()->select(array())->from($this->table_name($table))->get_all() );
		$this->assertSame( $data, self::db()->select('id,id2,id3')->from($this->table_name($table))->get_all() );
		$this->assertSame( $data, self::db()->select('id, id2, id3')->from($this->table_name($table))->get_all() );
		$this->assertSame( $data, self::db()->select('id','id2','id3')->from($this->table_name($table))->get_all() );
		$this->assertSame( $data, self::db()->select(array('id' => 'id','id2' => 'id2','id3' => 'id3'))->from($this->table_name($table))->get_all() );

		$this->assertSame( array('num' => '2'), self::db()->select('COUNT(id) AS num')->from($this->table_name($table))->get() );
		$this->assertSame( '2', self::db()->select('COUNT(id)')->from($this->table_name($table))->get_one() );
		$this->assertSame( '2', self::db()->select('COUNT(id) AS num')->from($this->table_name($table))->get_one() );
		$this->assertSame( '3', self::db()->select('SUM(id)')->from($this->table_name($table))->get_one() );
		$this->assertSame( '33', self::db()->select('SUM(id2)')->from($this->table_name($table))->get_one() );
		$this->assertSame( '333', self::db()->select('SUM(id3)')->from($this->table_name($table))->get_one() );
		$this->assertSame( '11', self::db()->select('MIN(id2)')->from($this->table_name($table))->get_one() );
		$this->assertSame( '22', self::db()->select('MAX(id2)')->from($this->table_name($table))->get_one() );
		$this->assertEquals( '1.5000', self::db()->select('AVG(id)')->from($this->table_name($table))->get_one() );

		$this->assertSame( $data[1], self::db()->from($this->table_name($table))->get() );
		$this->assertSame( $data[1], self::db()->from($this->table_name($table).' as t1')->get() );
		$this->assertSame( $data[1], self::db()->from(array($this->table_name($table) => 't1'))->get() );
		$this->assertSame( $data[1], self::db()->select('t1.id, t1.id2, t1.id3')->from($this->table_name($table).' as t1')->get() );
		$this->assertSame( $data[1], self::db()->select('t1.id','t1.id2','t1.id3')->from($this->table_name($table).' as t1')->get() );
		$this->assertSame( $data[1], self::db()->select('t1.id as id','t1.id2 as id2','t1.id3 as id3')->from($this->table_name($table).' as t1')->get() );
		$this->assertSame( $data[1], self::db()->select(array('t1.id' => 'id','t1.id2' => 'id2','t1.id3' => 'id3'))->from($this->table_name($table).' as t1')->get() );
		$this->assertSame( array('fld1' => $data[1]['id']), self::db()->select('t1.id as fld1')->from($this->table_name($table).' as t1')->get() );
	}
	public function test_where() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::db()->query('CREATE TABLE '.$this->table_name($table).'(id INT(10) AUTO_INCREMENT, id2 INT(10), id3 INT(10), PRIMARY KEY(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8') );
		$data = array(
			'1' => array('id' => '1', 'id2' => '11', 'id3' => '111'),
			'2' => array('id' => '2', 'id2' => '22', 'id3' => '222'),
		);
		$this->assertNotEmpty( self::db()->insert_safe($this->table_name($table), $data) );

		$this->assertSame( $data[1], self::qb()->from($this->table_name($table))->where('id','=','1')->get() );
		$this->assertSame( $data[2], self::qb()->from($this->table_name($table))->where('id','=','2')->get() );
		$this->assertEmpty( self::qb()->from($this->table_name($table))->where('id','=','3')->get() );
		$this->assertSame( $data[2], self::qb()->from($this->table_name($table))->where('id3','like','222')->get() );
		$this->assertSame( $data[2], self::qb()->from($this->table_name($table))->where('id3','like','22%')->get() );
		$this->assertSame( $data[2], self::qb()->from($this->table_name($table))->where('id3','like','22*')->get() );
		$this->assertSame( $data[2], self::qb()->from($this->table_name($table))->where('id3','rlike','(222|222222)')->get() );
		$this->assertSame( $data[1], self::qb()->from($this->table_name($table))->where('id3','not rlike','(222|222222)')->get() );

		$this->assertEmpty( self::qb()->from($this->table_name($table).' as t1')->where(array('t1.id2' => '1*', 't1.id3' => '2*'))->get() );
		$this->assertSame( $data[1], self::qb()->from($this->table_name($table).' as t1')->where(array('t1.id2' => '1*', 't1.id3' => '1*'))->get() );
		$this->assertSame( $data[2], self::qb()->from($this->table_name($table).' as t1')->where(array('t1.id2' => '2*', 't1.id3' => '2*'))->get() );

		$this->assertSame( $data[1], self::qb()->from($this->table_name($table).' as t1')->where('id = 1')->get() );
		$this->assertSame( $data[2], self::qb()->from($this->table_name($table).' as t1')->where('t1.id > 1')->get() );
		$this->assertSame( $data[1], self::qb()->from($this->table_name($table).' as t1')->where('id = 1')->where('id2 = 11')->where('id3 = 111')->get() );

		$this->assertEmpty( self::qb()->from($this->table_name($table).' as t1')->where('t1.id = 789')->where_or('t1.id2 = 798')->where_or('t1.id3 = 888')->get() );
		$this->assertSame( $data[1], self::qb()->from($this->table_name($table).' as t1')->where('t1.id = 789')->where_or('t1.id2 = 798')->where_or('t1.id3 = 111')->get() );

		$this->assertSame( $data[1], self::qb()->from($this->table_name($table).' as t1')->whereid(1)->get() );
		$this->assertSame( $data[1], self::qb()->from($this->table_name($table).' as t1')->whereid(1, 'id')->get() );
		$this->assertSame( $data[1], self::qb()->from($this->table_name($table).' as t1')->whereid(1, 't1.id')->get() );

		$this->assertSame( $data, self::qb()->from($this->table_name($table).' as t1')->whereid(array(1,2,3,4))->get_all() );
		$this->assertSame( $data, self::qb()->from($this->table_name($table).' as t1')->whereid(array(1,2,3,4), 'id')->get_all() );
		$this->assertSame( $data, self::qb()->from($this->table_name($table).' as t1')->whereid(array(1,2,3,4), 't1.id')->get_all() );
		$this->assertSame( $data, self::qb()->from($this->table_name($table).' as t1')->where('t1.id', 'in', array(1,2,3,4))->get_all() );
		$this->assertSame( $data, self::qb()->from($this->table_name($table).' as t1')->where('t1.id', 'not in', array(5,6,7))->get_all() );

		$this->assertEmpty( self::qb()->from($this->table_name($table).' as t1')->whereid(array(4,5,6))->get_all() );
		$this->assertEmpty( self::qb()->from($this->table_name($table).' as t1')->whereid(array(4,5,6), 'id')->get_all() );
		$this->assertEmpty( self::qb()->from($this->table_name($table).' as t1')->whereid(array(4,5,6), 't1.id')->get_all() );
	}
	public function test_join() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table1 = self::utils()->db->DB_PREFIX. __FUNCTION__.'_1';
		$table2 = self::utils()->db->DB_PREFIX. __FUNCTION__.'_2';
		$this->assertNotEmpty( self::db()->query('CREATE TABLE '.$this->table_name($table1).'(id INT(10) AUTO_INCREMENT, id2 INT(10), id3 INT(10), PRIMARY KEY(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8') );
		$this->assertNotEmpty( self::db()->query('CREATE TABLE '.$this->table_name($table2).'(id INT(10) AUTO_INCREMENT, id2 INT(10), id4 INT(10), PRIMARY KEY(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8') );
		$data1 = array(
			'1' => array('id' => '1', 'id2' => '11', 'id3' => '111'),
			'2' => array('id' => '2', 'id2' => '11', 'id3' => '111'),
		);
		$data2 = array(
			'1' => array('id' => '1', 'id2' => '22', 'id4' => '444'),
			'2' => array('id' => '2', 'id2' => '22', 'id4' => '444'),
		);
		$this->assertNotEmpty( self::db()->insert_safe($this->table_name($table1), $data1) );
		$this->assertNotEmpty( self::db()->insert_safe($this->table_name($table2), $data2) );

		$expected = array(
			'1' => array('id' => '1', 'id2' => '22', 'id3' => '111', 'id4' => '444'),
			'2' => array('id' => '2', 'id2' => '22', 'id3' => '111', 'id4' => '444'),
		);
		$this->assertSame( $expected, self::qb()->from($this->table_name($table1).' as t1')->join($this->table_name($table2).' as t2', 't1.id = t2.id')->get_all() );
		$this->assertSame( $expected, self::qb()->from($this->table_name($table1).' as t1')->left_join($this->table_name($table2).' as t2', 't1.id = t2.id')->get_all() );
		$this->assertSame( $expected, self::qb()->from($this->table_name($table1).' as t1')->right_join($this->table_name($table2).' as t2', 't1.id = t2.id')->get_all() );
		$this->assertSame( $expected, self::qb()->from($this->table_name($table1).' as t1')->inner_join($this->table_name($table2).' as t2', 't1.id = t2.id')->get_all() );

		$expected = array(
			'1' => array('id' => '1', 'id2' => '11', 'id4' => '444', 'id3' => '111'),
			'2' => array('id' => '2', 'id2' => '11', 'id4' => '444', 'id3' => '111'),
		);
		$this->assertSame( $expected, self::qb()->from($this->table_name($table2).' as t2')->join($this->table_name($table1).' as t1', 't1.id = t2.id')->get_all() );
		$this->assertSame( $expected, self::qb()->from($this->table_name($table2).' as t2')->left_join($this->table_name($table1).' as t1', 't1.id = t2.id')->get_all() );
		$this->assertSame( $expected, self::qb()->from($this->table_name($table2).' as t2')->right_join($this->table_name($table1).' as t1', 't1.id = t2.id')->get_all() );
		$this->assertSame( $expected, self::qb()->from($this->table_name($table2).' as t2')->inner_join($this->table_name($table1).' as t1', 't1.id = t2.id')->get_all() );
	}
	public function test_group_by() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::db()->query('CREATE TABLE '.$this->table_name($table).'(id INT(10) AUTO_INCREMENT, id2 INT(10), id3 INT(10), PRIMARY KEY(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8') );
		$data = array(
			'1' => array('id' => '1', 'id2' => '11', 'id3' => '111'),
			'2' => array('id' => '2', 'id2' => '22', 'id3' => '222'),
			'3' => array('id' => '3', 'id2' => '11', 'id3' => '222'),
			'4' => array('id' => '4', 'id2' => '22', 'id3' => '333'),
		);
		$this->assertNotEmpty( self::db()->insert_safe($this->table_name($table), $data) );

		$this->assertSame( $data, self::qb()->from($this->table_name($table).' as t1')->group_by('id')->get_all() );
		$this->assertSame( $data, self::qb()->from($this->table_name($table).' as t1')->group_by('t1.id')->get_all() );
		$expected = array(
			'1' => $data[1],
			'3' => $data[3],
			'2' => $data[2],
			'4' => $data[4],
		);
		$this->assertSame( $expected, self::qb()->from($this->table_name($table).' as t1')->group_by('t1.id2', 't1.id3')->get_all() );
		$expected = array(
			'1' => $data[1],
			'2' => $data[2],
		);
		$this->assertSame( $expected, self::qb()->from($this->table_name($table).' as t1')->group_by('t1.id2')->get_all() );
		$expected = array(
			'1' => $data[1] + array('num' => '2'),
			'2' => $data[2] + array('num' => '2'),
		);
		$this->assertSame( $expected, self::qb()->from($this->table_name($table).' as t1')->select('*','COUNT(id2) as num')->group_by('t1.id2')->get_all() );
	}
	public function test_having() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::db()->query('CREATE TABLE '.$this->table_name($table).'(id INT(10) AUTO_INCREMENT, id2 INT(10), id3 INT(10), PRIMARY KEY(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8') );
		$data = array(
			'1' => array('id' => '1', 'id2' => '11', 'id3' => '111'),
			'2' => array('id' => '2', 'id2' => '22', 'id3' => '222'),
			'3' => array('id' => '3', 'id2' => '11', 'id3' => '222'),
			'4' => array('id' => '4', 'id2' => '22', 'id3' => '333'),
		);
		$this->assertNotEmpty( self::db()->insert_safe($this->table_name($table), $data) );

		$expected = array('1' => $data[1], '2' => $data[2]);
		$this->assertSame( $expected, self::qb()->from($this->table_name($table).' as t1')->group_by('t1.id2')->get_all() );
		$expected = array('2' => $data[2]);
		$this->assertSame( $expected, self::qb()->from($this->table_name($table).' as t1')->group_by('t1.id2')->having(array('id3','=','222'))->get_all() );
		$this->assertSame( $expected, self::qb()->from($this->table_name($table).' as t1')->group_by('t1.id2')->having(array('t1.id3','=','222'))->get_all() );
		$this->assertSame( $expected, self::qb()->from($this->table_name($table).' as t1')->group_by('t1.id2')->having('id3 = 222')->get_all() );
		$this->assertSame( $expected, self::qb()->from($this->table_name($table).' as t1')->group_by('t1.id2')->having('t1.id3 = 222')->get_all() );
		$this->assertSame( $expected, self::qb()->from($this->table_name($table).' as t1')->group_by('t1.id2')->having('t1.id3 > 111')->get_all() );
	}
	public function test_order_by() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::db()->query('CREATE TABLE '.$this->table_name($table).'(id INT(10) AUTO_INCREMENT, id2 INT(10), id3 INT(10), PRIMARY KEY(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8') );
		$data = array(
			'1' => array('id' => '1', 'id2' => '11', 'id3' => '111'),
			'2' => array('id' => '2', 'id2' => '22', 'id3' => '222'),
			'3' => array('id' => '3', 'id2' => '11', 'id3' => '222'),
			'4' => array('id' => '4', 'id2' => '22', 'id3' => '333'),
		);
		$this->assertNotEmpty( self::db()->insert_safe($this->table_name($table), $data) );

		$this->assertSame( array_reverse($data, $preserve = true), self::qb()->from($this->table_name($table).' as t1')->order_by(array('id' => 'desc'))->get_all() );
		$this->assertSame( array_reverse($data, $preserve = true), self::qb()->from($this->table_name($table).' as t1')->order_by(array('t1.id' => 'desc'))->get_all() );
		$this->assertSame( array_reverse($data, $preserve = true), self::qb()->from($this->table_name($table).' as t1')->order_by('id desc')->get_all() );
		$this->assertSame( array_reverse($data, $preserve = true), self::qb()->from($this->table_name($table).' as t1')->order_by('t1.id desc')->get_all() );
	}
	public function test_limit() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::db()->query('CREATE TABLE '.$this->table_name($table).'(id INT(10) AUTO_INCREMENT, id2 INT(10), id3 INT(10), PRIMARY KEY(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8') );
		$data = array(
			'1' => array('id' => '1', 'id2' => '11', 'id3' => '111'),
			'2' => array('id' => '2', 'id2' => '22', 'id3' => '222'),
			'3' => array('id' => '3', 'id2' => '11', 'id3' => '222'),
			'4' => array('id' => '4', 'id2' => '22', 'id3' => '333'),
		);
		$this->assertNotEmpty( self::db()->insert_safe($this->table_name($table), $data) );

		$this->assertSame( array('4' => $data[4]), self::qb()->from($this->table_name($table).' as t1')->order_by('t1.id desc')->limit(1)->get_all() );
		$this->assertSame( array('2' => $data[2]), self::qb()->from($this->table_name($table).' as t1')->order_by('t1.id desc')->limit(1,2)->get_all() );
	}
	public function test_delete() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::db()->query('CREATE TABLE '.$this->table_name($table).'(id INT(10) AUTO_INCREMENT, id2 INT(10), id3 INT(10), PRIMARY KEY(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8') );
		$data = array(
			'1' => array('id' => '1', 'id2' => '11', 'id3' => '111'),
			'2' => array('id' => '2', 'id2' => '22', 'id3' => '222'),
			'3' => array('id' => '3', 'id2' => '11', 'id3' => '222'),
			'4' => array('id' => '4', 'id2' => '22', 'id3' => '333'),
		);
		$this->assertNotEmpty( self::db()->insert_safe($this->table_name($table), $data) );

		$this->assertNotEmpty( self::qb()->from($this->table_name($table))->where('id > 1')->delete() );
		$this->assertSame( array('1' => $data[1]), self::qb()->from($this->table_name($table))->get_all() );
		$this->assertNotEmpty( self::qb()->from($this->table_name($table))->whereid('1')->delete() );
		$this->assertEmpty( self::qb()->from($this->table_name($table))->get_all() );
// TODO: fix DELETE with AS ... == not allowed
#		$this->assertTrue( self::qb()->from($this->table_name($table).' as t1')->where('t1.id > 1')->delete() );
#		$this->assertEmpty( self::qb()->from($this->table_name($table).' as t1')->where('t1.id > 1')->delete() );
#		$this->assertSame( array('1' => $data[1]), self::qb()->from($this->table_name($table).' as t1')->get_all() );
#		$this->assertTrue( self::qb()->from($this->table_name($table).' as t1')->whereid('1')->delete() );
#		$this->assertEmpty( self::qb()->from($this->table_name($table).' as t1')->get_all() );
	}
	public function test_update() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::db()->query('CREATE TABLE '.$this->table_name($table).'(id INT(10) AUTO_INCREMENT, id2 INT(10), id3 INT(10), PRIMARY KEY(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8') );
		$data = array(
			'1' => array('id' => '1', 'id2' => '11', 'id3' => '111'),
			'2' => array('id' => '2', 'id2' => '22', 'id3' => '222'),
			'3' => array('id' => '3', 'id2' => '11', 'id3' => '222'),
			'4' => array('id' => '4', 'id2' => '22', 'id3' => '333'),
		);
		$this->assertNotEmpty( self::db()->insert_safe($this->table_name($table), $data) );

#		$this->assertFalse( self::qb()->update(array()) );
#		$data = array(
#			1 => array('name' => 'name1'),
#			2 => array('name' => 'name2'),
#		);
#		$this->assertEquals( '', self::qb()->from('user')->whereid(array(1,2,3))->update($data)->sql() );
	}
}