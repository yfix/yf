<?php

require_once __DIR__.'/db_real__setup.php';

/**
 * @requires extension mysql
 */
class class_db_query_builder_mysql_real_test extends db_real_abstract {
	public function test_selects_basic() {
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::db()->query('CREATE TABLE '.self::$DB_NAME.'.'.$table.'(id INT(10) AUTO_INCREMENT, id2 INT(10), id3 INT(10), PRIMARY KEY(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8') );
		$data = array(
			1 => array('id' => 1, 'id2' => 11, 'id3' => 111),
			2 => array('id' => 2, 'id2' => 22, 'id3' => 222),
		);
		$this->assertNotEmpty( self::db()->insert_safe(self::$DB_NAME.'.'.$table, $data) );

		$this->assertEquals( $data[1], self::db()->get('SELECT * FROM '.self::$DB_NAME.'.'.$table) );
		$this->assertEquals( $data[1], self::db()->from(self::$DB_NAME.'.'.$table)->get() );
		$this->assertEquals( $data[1], self::db()->select()->from(self::$DB_NAME.'.'.$table)->get() );
		$this->assertEquals( $data[1], self::db()->select('*')->from(self::$DB_NAME.'.'.$table)->get() );
		$this->assertEquals( $data[1], self::db()->select(array())->from(self::$DB_NAME.'.'.$table)->get() );
		$this->assertEquals( $data[1], self::db()->select('id,id2,id3')->from(self::$DB_NAME.'.'.$table)->get() );
		$this->assertEquals( $data[1], self::db()->select('id, id2, id3')->from(self::$DB_NAME.'.'.$table)->get() );
		$this->assertEquals( $data[1], self::db()->select('id','id2','id3')->from(self::$DB_NAME.'.'.$table)->get() );
		$this->assertEquals( $data[1], self::db()->select(array('id' => 'id','id2' => 'id2','id3' => 'id3'))->from(self::$DB_NAME.'.'.$table)->get() );

		$this->assertEquals( $data, self::db()->get_all('SELECT * FROM '.self::$DB_NAME.'.'.$table) );
		$this->assertEquals( $data, self::db()->from(self::$DB_NAME.'.'.$table)->get_all() );
		$this->assertEquals( $data, self::db()->select()->from(self::$DB_NAME.'.'.$table)->get_all() );
		$this->assertEquals( $data, self::db()->select('*')->from(self::$DB_NAME.'.'.$table)->get_all() );
		$this->assertEquals( $data, self::db()->select(array())->from(self::$DB_NAME.'.'.$table)->get_all() );
		$this->assertEquals( $data, self::db()->select('id,id2,id3')->from(self::$DB_NAME.'.'.$table)->get_all() );
		$this->assertEquals( $data, self::db()->select('id, id2, id3')->from(self::$DB_NAME.'.'.$table)->get_all() );
		$this->assertEquals( $data, self::db()->select('id','id2','id3')->from(self::$DB_NAME.'.'.$table)->get_all() );
		$this->assertEquals( $data, self::db()->select(array('id' => 'id','id2' => 'id2','id3' => 'id3'))->from(self::$DB_NAME.'.'.$table)->get_all() );

		$this->assertEquals( array('num' => '2'), self::db()->select('COUNT(id) AS num')->from(self::$DB_NAME.'.'.$table)->get() );
		$this->assertEquals( '2', self::db()->select('COUNT(id)')->from(self::$DB_NAME.'.'.$table)->get_one() );
		$this->assertEquals( '2', self::db()->select('COUNT(id) AS num')->from(self::$DB_NAME.'.'.$table)->get_one() );
		$this->assertEquals( '3', self::db()->select('SUM(id)')->from(self::$DB_NAME.'.'.$table)->get_one() );
		$this->assertEquals( '33', self::db()->select('SUM(id2)')->from(self::$DB_NAME.'.'.$table)->get_one() );
		$this->assertEquals( '333', self::db()->select('SUM(id3)')->from(self::$DB_NAME.'.'.$table)->get_one() );
		$this->assertEquals( '11', self::db()->select('MIN(id2)')->from(self::$DB_NAME.'.'.$table)->get_one() );
		$this->assertEquals( '22', self::db()->select('MAX(id2)')->from(self::$DB_NAME.'.'.$table)->get_one() );
		$this->assertEquals( '1.500', self::db()->select('AVG(id)')->from(self::$DB_NAME.'.'.$table)->get_one() );

		$this->assertEquals( $data[1], self::db()->from(self::$DB_NAME.'.'.$table)->get() );
		$this->assertEquals( $data[1], self::db()->from(self::$DB_NAME.'.'.$table.' as t1')->get() );
		$this->assertEquals( $data[1], self::db()->from(array(self::$DB_NAME.'.'.$table => 't1'))->get() );
		$this->assertEquals( $data[1], self::db()->select('t1.id, t1.id2, t1.id3')->from(self::$DB_NAME.'.'.$table.' as t1')->get() );
		$this->assertEquals( $data[1], self::db()->select('t1.id','t1.id2','t1.id3')->from(self::$DB_NAME.'.'.$table.' as t1')->get() );
		$this->assertEquals( $data[1], self::db()->select('t1.id as id','t1.id2 as id2','t1.id3 as id3')->from(self::$DB_NAME.'.'.$table.' as t1')->get() );
		$this->assertEquals( $data[1], self::db()->select(array('t1.id' => 'id','t1.id2' => 'id2','t1.id3' => 'id3'))->from(self::$DB_NAME.'.'.$table.' as t1')->get() );
		$this->assertEquals( array('fld1' => $data[1]['id']), self::db()->select('t1.id as fld1')->from(self::$DB_NAME.'.'.$table.' as t1')->get() );
	}
	public function test_where() {
		$table = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::db()->query('CREATE TABLE '.self::$DB_NAME.'.'.$table.'(id INT(10) AUTO_INCREMENT, id2 INT(10), id3 INT(10), PRIMARY KEY(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8') );
		$data = array(
			1 => array('id' => 1, 'id2' => 11, 'id3' => 111),
			2 => array('id' => 2, 'id2' => 22, 'id3' => 222),
		);
		$this->assertNotEmpty( self::db()->insert_safe(self::$DB_NAME.'.'.$table, $data) );

		$this->assertEquals( $data[1], self::qb()->from(self::$DB_NAME.'.'.$table)->where('id','=','1')->get() );
		$this->assertEquals( $data[2], self::qb()->from(self::$DB_NAME.'.'.$table)->where('id','=','2')->get() );
		$this->assertEmpty( self::qb()->from(self::$DB_NAME.'.'.$table)->where('id','=','3')->get() );
		$this->assertEquals( $data[2], self::qb()->from(self::$DB_NAME.'.'.$table)->where('id3','like','222')->get() );
		$this->assertEquals( $data[2], self::qb()->from(self::$DB_NAME.'.'.$table)->where('id3','like','22%')->get() );
		$this->assertEquals( $data[2], self::qb()->from(self::$DB_NAME.'.'.$table)->where('id3','like','22*')->get() );
		$this->assertEquals( $data[2], self::qb()->from(self::$DB_NAME.'.'.$table)->where('id3','rlike','(222|222222)')->get() );
		$this->assertEquals( $data[1], self::qb()->from(self::$DB_NAME.'.'.$table)->where('id3','not rlike','(222|222222)')->get() );

		$this->assertEmpty( self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->where(array('t1.id2' => '1*', 't1.id3' => '2*'))->get() );
		$this->assertEquals( $data[1], self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->where(array('t1.id2' => '1*', 't1.id3' => '1*'))->get() );
		$this->assertEquals( $data[2], self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->where(array('t1.id2' => '2*', 't1.id3' => '2*'))->get() );

		$this->assertEquals( $data[1], self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->where('id = 1')->get() );
		$this->assertEquals( $data[2], self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->where('t1.id > 1')->get() );
		$this->assertEquals( $data[1], self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->where('id = 1')->where('id2 = 11')->where('id3 = 111')->get() );

		$this->assertEmpty( self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->where('t1.id = 789')->where_or('t1.id2 = 798')->where_or('t1.id3 = 888')->get() );
		$this->assertEquals( $data[1], self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->where('t1.id = 789')->where_or('t1.id2 = 798')->where_or('t1.id3 = 111')->get() );

		$this->assertEquals( $data[1], self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->whereid(1)->get() );
		$this->assertEquals( $data[1], self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->whereid(1, 'id')->get() );
		$this->assertEquals( $data[1], self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->whereid(1, 't1.id')->get() );

		$this->assertEquals( $data, self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->whereid(array(1,2,3,4))->get_all() );
		$this->assertEquals( $data, self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->whereid(array(1,2,3,4), 'id')->get_all() );
		$this->assertEquals( $data, self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->whereid(array(1,2,3,4), 't1.id')->get_all() );

		$this->assertEmpty( self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->whereid(array(4,5,6))->get_all() );
		$this->assertEmpty( self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->whereid(array(4,5,6), 'id')->get_all() );
		$this->assertEmpty( self::qb()->from(self::$DB_NAME.'.'.$table.' as t1')->whereid(array(4,5,6), 't1.id')->get_all() );
	}
	public function test_join() {
		$table1 = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$table2 = self::utils()->db->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::db()->query('CREATE TABLE '.self::$DB_NAME.'.'.$table1.'(id INT(10) AUTO_INCREMENT, id2 INT(10), id3 INT(10), PRIMARY KEY(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8') );
		$this->assertNotEmpty( self::db()->query('CREATE TABLE '.self::$DB_NAME.'.'.$table2.'(id INT(10) AUTO_INCREMENT, id2 INT(10), id3 INT(10), PRIMARY KEY(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8') );
		$data1 = array(
			1 => array('id' => 1, 'id2' => 11, 'id3' => 111),
			2 => array('id' => 2, 'id2' => 22, 'id3' => 222),
		);
		$data2 = array(
			3 => array('id' => 3, 'id2' => 33, 'id3' => 333),
			4 => array('id' => 4, 'id2' => 44, 'id3' => 444),
		);
		$this->assertNotEmpty( self::db()->insert_safe(self::$DB_NAME.'.'.$table1, $data1) );
		$this->assertNotEmpty( self::db()->insert_safe(self::$DB_NAME.'.'.$table2, $data2) );

		$this->assertEquals( $data1 + $data2, self::qb()->select()->from(self::$DB_NAME.'.'.$table1.' as t1')->join(self::$DB_NAME.'.'.$table2.' as t2', 't1.id = t2.id')->get_all() );
#		$this->assertEquals( self::qb()->select()->from('user as u')->join('articles as a', 'u.id = a.id')->sql() );
#		$this->assertEquals( self::qb()->select()->from('user as u')->left_join('articles as a', 'u.id = a.id')->sql() );
#		$this->assertEquals( self::qb()->select()->from('user as u')->right_join('articles as a', 'u.id = a.id')->sql() );
#		$this->assertEquals( self::qb()->select()->from('user as u')->inner_join('articles as a', 'u.id = a.id')->sql() );
#		$this->assertEquals( self::qb()->select()->from('user as u')->join('articles as a', 'u.id = a.id', 'inner')->sql() );
#		$this->assertEquals( self::qb()->select()->from('user as u')->inner_join('articles as a', 'u.id = a.id')->inner_join('blogs as b', 'u.id = b.id')->sql() );
	}
/*
	public function test_group_by() {
		$this->assertFalse( self::qb()->group_by()->sql() );
		$this->assertFalse( self::qb()->from()->where()->group_by()->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `id` = \'1\' GROUP BY `gid`', self::qb()->from('user')->where(array('id','=',1))->group_by('gid')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `id` = \'1\' GROUP BY `u`.`id`', self::qb()->from('user as u')->whereid(1)->group_by('u.id')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `id` = \'1\' GROUP BY `u`.`id`, `u`.`gid`', self::qb()->from('user as u')->whereid(1)->group_by('u.id', 'u.gid')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `id` = \'1\' GROUP BY `u`.`id` , `u`.`gid`', self::qb()->from('user as u')->whereid(1)->group_by('u.id')->group_by('u.gid')->sql() );
	}
	public function test_having() {
		$this->assertFalse( self::qb()->having()->sql() );
		$this->assertFalse( self::qb()->from()->where()->group_by()->having()->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `id` = \'1\' GROUP BY `gid` HAVING `gid` = \'4\'', 
			self::qb()->from('user')->where(array('id','=',1))->group_by('gid')->having(array('gid','=',4))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` = \'1\' GROUP BY `u`.`gid` HAVING `u`.`gid` = \'4\'', 
			self::qb()->from(array('user' => 'u'))->where(array('u.id','=',1))->group_by('u.gid')->having(array('u.gid','=',4))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` = \'1\' GROUP BY `u`.`gid` HAVING `u`.`gid` = \'4\', `u`.`visits` < \'4\'', 
			self::qb()->from(array('user' => 'u'))->where(array('u.id','=',1))->group_by('u.gid')->having(array('u.gid','=',4),array('u.visits','<',4))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` = \'1\' GROUP BY `u`.`gid` HAVING `u`.`gid` = \'4\' , `u`.`visits` < \'4\'', 
			self::qb()->from('user as u')->where('u.id = 1')->group_by('u.gid')->having('u.gid = 4')->having('u.visits < 4')->sql() );
	}
	public function test_order_by() {
		$this->assertFalse( self::qb()->order_by()->sql() );
		$this->assertFalse( self::qb()->from()->where()->having()->group_by()->order_by()->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `id` = \'1\' GROUP BY `gid` HAVING `gid` = \'4\' ORDER BY `id` DESC', 
			self::qb()->from('user')->where(array('id','=',1))->group_by('gid')->having(array('gid','=',4))->order_by(array('id' => 'desc'))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` = \'1\' GROUP BY `u`.`gid` HAVING `u`.`gid` = \'4\' ORDER BY `u`.`id` ASC', 
			self::qb()->from(array('user' => 'u'))->where(array('u.id','=',1))->group_by('u.gid')->having(array('u.gid','=',4))->order_by('u.id')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` = \'1\' GROUP BY `u`.`gid` HAVING `u`.`gid` = \'4\' ORDER BY `u`.`id` ASC, `u`.`gid` DESC', 
			self::qb()->from('user as u')->where('u.id = 1')->group_by('u.gid')->having('u.gid = 4')->order_by('u.id','u.gid desc')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` = \'1\' GROUP BY `u`.`gid` HAVING `u`.`gid` = \'4\' ORDER BY `u`.`id` ASC , `u`.`gid` DESC', 
			self::qb()->from('user as u')->where('u.id = 1')->group_by('u.gid')->having('u.gid = 4')->order_by('u.id')->order_by('u.gid desc')->sql() );
	}
	public function test_limit() {
		$this->assertFalse( self::qb()->limit()->sql() );
		$this->assertFalse( self::qb()->from()->limit()->sql() );
		$this->assertFalse( self::qb()->from()->where()->having()->group_by()->order_by()->limit()->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `id` = \'1\' GROUP BY `gid` HAVING `gid` = \'4\' ORDER BY `id` DESC LIMIT 10', 
			self::qb()->from('user')->where(array('id','=',1))->group_by('gid')->having(array('gid','=',4))->order_by(array('id' => 'desc'))->limit(10)->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` = \'1\' GROUP BY `u`.`gid` HAVING `u`.`gid` = \'4\' ORDER BY `u`.`id` ASC LIMIT 20, 5', 
			self::qb()->from(array('user' => 'u'))->where(array('u.id','=',1))->group_by('u.gid')->having(array('u.gid','=',4))->order_by('u.id')->limit(5, 20)->sql() );
	}
	// Testing that changing order of method calls not changing result SQL
	public function test_calls_ordering() {
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` = \'1\' GROUP BY `u`.`gid` HAVING `u`.`gid` = \'4\' ORDER BY `u`.`id` ASC LIMIT 20, 5', 
			self::qb()->from(array('user' => 'u'))->where(array('u.id','=',1))->group_by('u.gid')->having(array('u.gid','=',4))->order_by('u.id')->limit(5, 20)->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` = \'1\' GROUP BY `u`.`gid` HAVING `u`.`gid` = \'4\' ORDER BY `u`.`id` ASC LIMIT 20, 5', 
			self::qb()->from(array('user' => 'u'))->where(array('u.id','=',1))->group_by('u.gid')->having(array('u.gid','=',4))->order_by('u.id')->limit(5, 20)->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` = \'1\' GROUP BY `u`.`gid` HAVING `u`.`gid` = \'4\' ORDER BY `u`.`id` ASC LIMIT 20, 5', 
			self::qb()->group_by('u.gid')->where(array('u.id','=',1))->order_by('u.id')->limit(5, 20)->from(array('user' => 'u'))->having(array('u.gid','=',4))->sql() );
	}
	public function test_delete() {
		$this->assertFalse( self::qb()->delete() );
		$this->assertEquals( 'DELETE FROM `'.DB_PREFIX.'user`', self::qb()->from('user')->delete($as_sql = true) );
		$this->assertEquals( 'DELETE FROM `'.DB_PREFIX.'user` WHERE `id` = \'1\'', self::qb()->from('user')->whereid(1)->delete($as_sql = true) );
		$this->assertEquals( 'DELETE FROM `'.DB_PREFIX.'user` WHERE `id` IN(1,2,3)', self::qb()->from('user')->whereid(array(1,2,3))->delete($as_sql = true) );
		$this->assertEquals( 'DELETE FROM `'.DB_PREFIX.'user` WHERE `uid` IN(1,2,3)', self::qb()->from('user')->whereid(array(1,2,3), 'uid')->delete($as_sql = true) );
		$this->assertEquals( 'DELETE FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` IN(1,2,3)', self::qb()->from('user as u')->whereid(array(1,2,3), 'u.id')->delete($as_sql = true) );
	}
	public function test_where_in() {
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `product_id` IN(1,2,3)', self::qb()->from('user')->where('product_id', 'in', array(1,2,3))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `product_id` IN(1,2,3)', self::qb()->from('user')->where('product_id', 'IN', array(1,2,3))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `product_id` NOT IN(1,2,3)', self::qb()->from('user')->where('product_id', 'NOT IN', array(1,2,3))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `product_id` IN(1)', self::qb()->from('user')->where('product_id', 'IN', array(1))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `product_id` IN(1)', self::qb()->from('user')->where('product_id', 'IN', 1)->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `product_id` IN(0)', self::qb()->from('user')->where('product_id', 'IN', 0)->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `product_id` IN(\'0\')', self::qb()->from('user')->where('product_id', 'IN', '0')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `product_id` IN(\'`\')', self::qb()->from('user')->where('product_id', 'IN', '`')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user`', self::qb()->from('user')->where('product_id', 'in', '')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user`', self::qb()->from('user')->where('product_id', 'in', array('','',''))->sql() );
	}
	public function test_update() {
		$this->assertFalse( self::qb()->update(array()) );
#		$data = array(
#			1 => array('name' => 'name1'),
#			2 => array('name' => 'name2'),
#		);
#		$this->assertEquals( '', self::qb()->from('user')->whereid(array(1,2,3))->update($data)->sql() );
	}
*/
}