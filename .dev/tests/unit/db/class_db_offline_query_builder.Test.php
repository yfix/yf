<?php

require_once __DIR__.'/db_offline_abstract.php';

/**
 * @requires extension mysql
 */
class class_db_offline_query_builder_test extends db_offline_abstract {
	public function test_select_star() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertEquals( '*', self::qb()->select()->_sql['select'][0] );
		$this->assertEquals( '*', self::qb()->select('*')->_sql['select'][0] );
		$this->assertEquals( '*', self::qb()->select(' *')->_sql['select'][0] );
		$this->assertEquals( '*', self::qb()->select('* ')->_sql['select'][0] );
		$this->assertEquals( '*', self::qb()->select('   *   ')->_sql['select'][0] );
		$this->assertFalse( self::qb()->select()->sql() );
		$this->assertFalse( self::qb()->select()->__toString() );
	}
	public function test_select_aggregates() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertEquals( '`id`', self::qb()->select('id')->_sql['select'][0] );
		$this->assertFalse( self::qb()->select('id')->sql() );
		$this->assertEquals( 'COUNT(id)', self::qb()->select('COUNT(id)')->_sql['select'][0] );
		$this->assertEquals( 'SUM(id)', self::qb()->select('SUM(id)')->_sql['select'][0] );
		$this->assertEquals( 'MIN(id)', self::qb()->select('MIN(id)')->_sql['select'][0] );
		$this->assertEquals( 'MAX(id)', self::qb()->select('MAX(id)')->_sql['select'][0] );
		$this->assertEquals( 'AVG(id)', self::qb()->select('AVG(id)')->_sql['select'][0] );
		$this->assertEquals( 'COUNT(id) AS `num`', self::qb()->select('COUNT(id) as num')->_sql['select'][0] );
	}
	public function test_select_basic() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertEquals( '`id` , `name`', self::qb()->select('id','name')->_sql['select'][0] );
		$this->assertFalse( self::qb()->select('id','name')->sql() );
	}
	public function test_select_check_wrong_input() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertNull( self::qb()->select('')->_sql['select'][0] );
		$this->assertNull( self::qb()->select(false)->_sql['select'][0] );
		$this->assertNull( self::qb()->select(0)->_sql['select'][0] );
		$this->assertNull( self::qb()->select('0')->_sql['select'][0] );

		$this->assertFalse( self::qb()->select('')->sql() );
		$this->assertFalse( self::qb()->select(array())->sql() );
		$this->assertFalse( self::qb()->select(false)->sql() );
		$this->assertFalse( self::qb()->select(0)->sql() );
		$this->assertFalse( self::qb()->select('0')->sql() );
	}
	public function test_select_complex() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertEquals( '`u`.`id` , `s`.`id` , `t`.`pid`', self::qb()->select('u.id', 's.id', 't.pid')->_sql['select'][0] );
		$this->assertEquals( '`u`.`id` AS `user_id`', self::qb()->select(array('u.id' => 'user_id'))->_sql['select'][0] );
		$this->assertEquals( '`u`.`id` AS `user_id` , `a`.`id` AS `article_id` , `b`.`id` AS `blog_id`', self::qb()->select(array('u.id' => 'user_id', 'a.id' => 'article_id', 'b.id' => 'blog_id'))->_sql['select'][0] );
		$this->assertEquals( '`u`.`id` AS `user_id` , `a`.`id` AS `article_id` , `b`.`id` AS `blog_id`', self::qb()->select(array('u.id' => 'user_id'), array('a.id' => 'article_id'), array('b.id' => 'blog_id'))->_sql['select'][0] );
		$this->assertEquals( 'COUNT(*) AS `num`', self::qb()->select(array('COUNT(*)' => 'num'))->_sql['select'][0] );
		$this->assertEquals( 'COUNT(id) AS `num`', self::qb()->select(array('COUNT(id)' => 'num'))->_sql['select'][0] );
		$this->assertEquals( 'COUNT(u.id) AS `num`', self::qb()->select(array('COUNT(u.id)' => 'num'))->_sql['select'][0] );
		$this->assertEquals( 'DISTINCT u.id', self::qb()->select('DISTINCT u.id')->_sql['select'][0] );
		$this->assertEquals( 'DISTINCT u.id AS `num`', self::qb()->select(array('DISTINCT u.id' => 'num'))->_sql['select'][0] );
		$this->assertEquals( 'DISTINCT `u`.`id` AS `num` , `a`.`id` AS `article_id`', self::qb()->select( function(){return 'DISTINCT `u`.`id` AS `num`';}, function(){return '`a`.`id` AS `article_id`';} )->_sql['select'][0] );
	}
	public function test_select_string_as() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertEquals( '`s`.`id` AS `sid`', self::qb()->select('s.id as sid')->_sql['select'][0] );
		$this->assertEquals( '`s`.`id` AS `sid` , `u`.`id` AS `uid`', self::qb()->select('s.id as sid', 'u.id as uid')->_sql['select'][0] );
		$this->assertEquals( '`u`.`id` AS `uid`', self::qb()->select(array('u.id as uid'))->_sql['select'][0] );
	}
	public function test_select_multiple_calls() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertEquals( 'SELECT `s`.`id` , `u`.`id` FROM `'.DB_PREFIX.'user`', self::qb()->select('s.id')->select('u.id')->from('user')->sql() );
		$this->assertEquals( 'SELECT `s`.`id` AS `sid` , `u`.`id` AS `uid` FROM `'.DB_PREFIX.'user`', self::qb()->select('s.id as sid')->select('u.id as uid')->from('user')->sql() );
		$this->assertEquals( 'SELECT `s`.`id` AS `sid` , `u`.`id` AS `uid` FROM `'.DB_PREFIX.'user`', self::qb()->select('s.id as sid, u.id as uid')->from('user')->sql() );

		$this->assertEquals(
			'SELECT `s`.`id` AS `sid` , `u`.`id` AS `uid` , `u`.`name` AS `uname` , `u`.`group` AS `group_id` , `u`.`verified` FROM `'.DB_PREFIX.'user`',
			self::qb()->select('s.id as sid, u.id as uid', array('u.name as uname'), array('u.group' => 'group_id'), 'u.verified')->from('user')->sql()
		);
	}
	public function test_from() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertFalse( self::qb()->from()->sql() );
		$this->assertFalse( self::qb()->select()->from()->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user`', self::qb()->from('user')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user`', self::qb()->select()->from('user')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` , `'.DB_PREFIX.'articles`', self::qb()->select()->from('user','articles')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u`', self::qb()->select()->from(array('user' => 'u'))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` , `'.DB_PREFIX.'articles` AS `a`', self::qb()->select()->from(array('user' => 'u', 'articles' => 'a'))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` , `'.DB_PREFIX.'articles` AS `a`', self::qb()->select()->from(array('user' => 'u'), array('articles' => 'a'))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` , `'.DB_PREFIX.'articles` AS `a`', self::qb()->select()->from(array('user' => 'u'))->from(array('articles' => 'a'))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` , `'.DB_PREFIX.'articles` AS `a`', self::qb()->select()->from('user as u, articles as a')->sql() );

		$this->assertEquals(
			'SELECT * FROM `'.DB_PREFIX.'user` AS `u` , `'.DB_PREFIX.'articles` AS `a` , `'.DB_PREFIX.'products` AS `p` , `'.DB_PREFIX.'orders` AS `o` , `'.DB_PREFIX.'rating` AS `r`', 
			self::qb()->select()->from('user as u, articles as a', array('products' => 'p', 'orders' => 'o'), 'rating as r')->sql()
		);
	}
	public function test_table() {
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` , `'.DB_PREFIX.'articles` AS `a`', self::qb()->select()->table(array('user' => 'u'))->table(array('articles' => 'a'))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` , `'.DB_PREFIX.'articles` AS `a`', self::qb()->select()->table('user as u, articles as a')->sql() );
		$this->assertEquals(
			'SELECT * FROM `'.DB_PREFIX.'user` AS `u` , `'.DB_PREFIX.'articles` AS `a` , `'.DB_PREFIX.'products` AS `p` , `'.DB_PREFIX.'orders` AS `o` , `'.DB_PREFIX.'rating` AS `r`', 
			self::qb()->select()->table('user as u, articles as a', array('products' => 'p', 'orders' => 'o'), 'rating as r')->sql()
		);
	}
	public function test_from_string_as() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u`', self::qb()->select()->from('user as u')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` , `'.DB_PREFIX.'articles` AS `a`', self::qb()->select()->from('user as u', 'articles as a')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` , `'.DB_PREFIX.'articles` AS `a`', self::qb()->select()->from('user as u')->from('articles as a')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` , `'.DB_PREFIX.'articles` AS `a`', self::qb()->select()->from('user as u, articles as a')->sql() );
	}
	public function test_where_basic() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }

		$this->assertFalse( self::qb()->where()->sql() );
		$this->assertFalse( self::qb()->from()->where()->sql() );
		$this->assertFalse( self::qb()->select()->from()->where()->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `id` = \'1\'', self::qb()->from('user')->where('id',1)->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `id` = \'1\'', self::qb()->from('user')->where('id','1')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `id` = \'1\'', self::qb()->from('user')->where('id','=','1')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `id` = \'1\'', self::qb()->from('user')->where(array('id','=',1))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `id` = \'1\'', self::qb()->from('user')->where(array('id',1))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` , `'.DB_PREFIX.'articles` WHERE `u`.`id` = \'1\'', self::qb()->from('user','articles')->where(array('u.id','=',1))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` = \'1\'', self::qb()->from(array('user' => 'u'))->where(array('u.id','=',1))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` = \'1\' AND `u`.`gid` = \'4\'', self::qb()->from(array('user' => 'u'))->where(array('u.id','=','1'),'and',array('u.gid','=','4'))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` = \'1\' OR `u`.`gid` = \'4\'', self::qb()->from(array('user' => 'u'))->where(array('u.id','=','1'),'or',array('u.gid','=','4'))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` = \'1\' XOR `u`.`gid` = \'4\'', self::qb()->from(array('user' => 'u'))->where(array('u.id','=','1'),'xor',array('u.gid','=','4'))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `id` = \'1\' AND `gid` = \'4\'', self::qb()->from(array('user' => 'u'))->where(array('id' => '1', 'gid' => '4'))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` = \'1\' AND `u`.`gid` = \'4\'', self::qb()->from(array('user' => 'u'))->where(array('u.id' => '1', 'u.gid' => '4'))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`gid` = \'4\'', self::qb()->from(array('user' => 'u'))->where(array('u.id' => '', 'u.gid' => '4'))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` = \'1\'', self::qb()->from(array('user' => 'u'))->where(array('u.id' => '1', 'u.gid' => ''))->sql() );

		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `id` IS NULL', self::qb()->from('user')->where('id','IS NULL')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `id` IS NOT NULL', self::qb()->from('user')->where(array('id','IS NOT NULL'))->sql() );
	}
	public function test_where_like() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `name` LIKE \'test\'', self::qb()->from('user')->where('name','like','test')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `name` LIKE \'test\'', self::qb()->from('user')->where('name','LIKE','test')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `name` LIKE \'test%\'', self::qb()->from('user')->where('name','like','test%')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `name` LIKE \'test%\'', self::qb()->from('user')->where('name','like','test*')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `name` NOT LIKE \'test%\'', self::qb()->from('user')->where('name','not like','test*')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `name` RLIKE \'(test|other)\'', self::qb()->from('user')->where('name','rlike','(test|other)')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `name` NOT RLIKE \'(test|other)\'', self::qb()->from('user')->where('name','not rlike','(test|other)')->sql() );

		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` LIKE \'1%\' AND `u`.`gid` LIKE \'%4\'', self::qb()->from(array('user' => 'u'))->where(array('u.id' => '1*', 'u.gid' => '*4'))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` LIKE \'1%\' AND `u`.`gid` LIKE \'%4\'', self::qb()->from(array('user' => 'u'))->where(array('u.id', '1*'), array('u.gid', '*4'))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` LIKE \'1%\' AND `u`.`gid` LIKE \'%4\'', self::qb()->from(array('user' => 'u'))->where(array('u.id', '1*'), array('u.gid' => '*4'))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` LIKE \'%1%\' XOR `u`.`gid` NOT LIKE \'%4%\'',
			self::qb()->from(array('user' => 'u'))->where(array('u.id','like','%1%'),'xor',array('u.gid','not like','%4%'))->sql() );
	}
	public function test_where_simplified_syntax() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` = \'1\'', self::qb()->from('user as u')->where('u.id = 1')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` > \'1\'', self::qb()->from('user as u')->where('u.id > 1')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` > \'1\' AND `u`.`visits` < \'3\'', self::qb()->from('user as u')->where('u.id > 1', 'u.visits < 3')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` > \'1\' AND `u`.`visits` < \'3\'', self::qb()->from('user as u')->where('u.id > 1')->where('u.visits < 3')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` > \'1\' AND `u`.`visits` < \'3\'', self::qb()->from('user as u')->where('u.id > 1, u.visits < 3')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` > \'1\' AND `u`.`visits` < \'3\'', self::qb()->from('user as u')->where(array('u.id > 1', 'u.visits < 3'))->sql() );

		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` IS NULL', self::qb()->from('user as u')->where('u.id is null')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` IS NULL', self::qb()->from('user as u')->where('u.id IS NULL')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` IS NOT NULL', self::qb()->from('user as u')->where('u.id is not null')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` IS NOT NULL', self::qb()->from('user as u')->where('u.id IS NOT NULL')->sql() );
	}
	public function test_where_null() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` IS NULL', self::qb()->from('user as u')->where_null('u.id')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` IS NOT NULL', self::qb()->from('user as u')->where_not_null('u.id')->sql() );
	}
	public function test_where_or() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` = \'1\' OR `u`.`gid` = \'4\'', self::qb()->from('user as u')->where('u.id = 1')->where_or('u.gid = 4')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` = \'1\' OR `u`.`gid` = \'4\' OR `u`.`visits` < \'4\'', self::qb()->from('user as u')->where('u.id = 1')->where_or('u.gid = 4')->where_or('u.visits < 4')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` > \'1\' OR `u`.`visits` < \'3\'', self::qb()->from('user as u')->where_or('u.id > 1, u.visits < 3')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` > \'1\' OR `u`.`visits` < \'3\'', self::qb()->from('user as u')->where_or(array('u.id > 1, u.visits < 3'))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` > \'1\' OR `u`.`visits` < \'3\'', self::qb()->from('user as u')->where_or('u.id > 1', 'u.visits < 3')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` > \'1\' OR `u`.`visits` < \'3\'', self::qb()->from('user as u')->where_or('u.id > 1')->where_or('u.visits < 3')->sql() );
	}
	public function test_whereid() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }

		$this->assertFalse( self::qb()->whereid()->sql() );
		$this->assertFalse( self::qb()->from()->whereid()->sql() );

		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `id` = \'1\'', self::qb()->from('user')->whereid(1)->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `id` = \'1\'', self::qb()->from('user')->whereid(1, '')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `uid` = \'1\'', self::qb()->from('user')->whereid(1, 'uid')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `u`.`id` = \'1\'', self::qb()->from('user')->whereid(1, 'u.id')->sql() );

		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `id` IN(1,2,3)', self::qb()->from('user')->whereid(array(1,2,3))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `uid` IN(1,2,3)', self::qb()->from('user')->whereid(array(1,2,3), 'uid')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `u`.`id` IN(1,2,3)', self::qb()->from('user')->whereid(array(1,2,3), 'u.id')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `id` IN(1,2,3)', self::qb()->from('user')->whereid(1,2,3)->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `uid` IN(1,2,3)', self::qb()->from('user')->whereid(1,2,3,'uid')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `u`.`id` IN(1,2,3)', self::qb()->from('user')->whereid(1,2,3,'u.id')->sql() );

		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `id` = \'1\'', self::qb()->from('user')->where(1)->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `id` = \'1\'', self::qb()->from('user')->where(1, '')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `id` IN(1,2,3)', self::qb()->from('user')->where(array(1,2,3))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `uid` IN(1,2,3)', self::qb()->from('user')->where('uid', array(1,2,3))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `uid` IN(1,2,3)', self::qb()->from('user')->where(array('uid', array(1,2,3)))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `u`.`id` IN(1,2,3)', self::qb()->from('user')->where('u.id', array(1,2,3))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `u`.`id` IN(1,2,3)', self::qb()->from('user')->where(array('u.id', array(1,2,3)))->sql() );

		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `uid` IN(1,2,3)', self::qb()->from('user')->where(array('uid' => array(1,2,3)))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `u`.`id` IN(1,2,3)', self::qb()->from('user')->where(array('u.id' => array(1,2,3)))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `uid` IN(1,2,3) AND `pid` IN(4,5,6)', self::qb()->from('user')->where(array('uid' => array(1,2,3)))->where(array('pid' => array(4,5,6)))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `uid` IN(1,2,3) AND `uid` IN(4,5,6)', self::qb()->from('user')->where(array('uid' => array(1,2,3)))->where(array('uid' => array(4,5,6)))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `u`.`id` IN(1,2,3)', self::qb()->from('user')->where(array('u.id' => array(1,2,3)))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `u`.`id` IN(1,2,3) AND `u`.`pid` IN(4,5,6)', self::qb()->from('user')->where(array('u.id' => array(1,2,3)))->where(array('u.pid' => array(4,5,6)))->sql() );

#		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `uid` IN(1,2,3) AND `pid` IN(4,5,6)', self::qb()->from('user')->where(array('uid' => array(1,2,3), 'pid' => array(4,5,6)))->sql() );
#		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `u`.`id` IN(1,2,3) AND `u`.`pid` IN(1,2,3)', self::qb()->from('user')->where(array('u.id' => array(1,2,3), 'u.pid' => array(4,5,6)))->sql() );
	}
	public function test_where_in() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user`', self::qb()->from('user')->where('product_id', 'in', '')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user`', self::qb()->from('user')->where('product_id', 'in', array('','',''))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `product_id` IN(\'0\')', self::qb()->from('user')->where('product_id', 'IN', '0')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `product_id` IN(\'`\')', self::qb()->from('user')->where('product_id', 'IN', '`')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `product_id` IN(0)', self::qb()->from('user')->where('product_id', 'IN', 0)->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `product_id` IN(1)', self::qb()->from('user')->where('product_id', 'IN', 1)->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `product_id` IN(1)', self::qb()->from('user')->where('product_id', 'IN', array(1))->sql() );

		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `product_id` IN(1,2,3)', self::qb()->from('user')->where('product_id', 'in', array(1,2,3))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `product_id` IN(1,2,3)', self::qb()->from('user')->where('product_id', 'IN', array(1,2,3))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `product_id` NOT IN(1,2,3)', self::qb()->from('user')->where('product_id', 'NOT IN', array(1,2,3))->sql() );

		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `id` = \'1\'', self::qb()->from('user')->where(1)->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `id` IN(1,2,3)', self::qb()->from('user')->where(1,2,3)->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `id` IN(1,2,3)', self::qb()->from('user')->where(array(1,2,3))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `id` IN(1,2,3)', self::qb()->from('user')->where('id', array(1,2,3))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `id` IN(1,2,3)', self::qb()->from('user')->where(array('id' => array(1,2,3)))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `product_id` IN(1,2,3)', self::qb()->from('user')->where('product_id', array(1,2,3))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `product_id` IN(1,2,3)', self::qb()->from('user')->where(array('product_id' => array(1,2,3)))->sql() );
	}
	public function test_where_complex() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertEquals(
			'SELECT * FROM `'.DB_PREFIX.'user` WHERE `id` = \'2\' AND `gid` = \'1\' AND `sid` = \'3\' AND `pid` = \'4\' AND `hid` = \'5\' AND `mid` = \'6\' AND `rank` IS NULL',
			self::qb()->from('user')->where('id = 2', array('gid',1), array('sid','=','3'), array('pid' => 4, 'hid' => 5, 'mid' => 6), array('rank','IS NULL'))->sql()
		);
		$this->assertEquals(
			'SELECT * FROM `'.DB_PREFIX.'user` WHERE `id` = \'2\' OR `gid` = \'1\' OR `sid` = \'3\' OR `pid` = \'4\' OR `hid` = \'5\' OR `mid` = \'6\' OR `rank` IS NULL',
			self::qb()->from('user')->where_or('id = 2', array('gid',1), array('sid','=','3'), array('pid' => 4, 'hid' => 5, 'mid' => 6), array('rank','IS NULL'))->sql()
		);
		$this->assertEquals(
			'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` = \'1\' AND `u`.`gid` > \'2\' AND `u`.`name` LIKE \'%hello%\' AND `u`.`pid` = \'4\' AND `u`.`hid` = \'5\' AND `u`.`mid` = \'6\' AND `u`.`id` IS NULL',
			self::qb()->from('user as u')->where(array('u.id',1), 'u.gid > 2', array('u.name','like','*hello*'), array('u.pid' => 4, 'u.hid' => 5, 'u.mid' => 6), array('u.id','IS NULL'))->sql()
		);
		$this->assertEquals(
			'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` = \'1\' OR `u`.`gid` > \'2\' OR `u`.`name` LIKE \'%hello%\' OR `u`.`pid` = \'4\' OR `u`.`hid` = \'5\' OR `u`.`mid` = \'6\' OR `u`.`id` IS NULL',
			self::qb()->from('user as u')->where_or(array('u.id',1), 'u.gid > 2', array('u.name','like','*hello*'), array('u.pid' => 4, 'u.hid' => 5, 'u.mid' => 6), array('u.id','IS NULL'))->sql()
		);
	}
	public function test_where_raw() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE id BETWEEN 1 AND 5', self::qb()->from('user')->where_raw('id BETWEEN 1 AND 5') );
	}
	public function test_join() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertFalse( self::qb()->join()->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` JOIN `'.DB_PREFIX.'articles` AS `a` ON `u`.`id` = `a`.`id`', self::qb()->select()->from('user as u')->join(array('articles' => 'a'), array('u.id' => 'a.id'))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` JOIN `'.DB_PREFIX.'articles` AS `a` ON `u`.`id` = `a`.`id`', self::qb()->select()->from('user as u')->join('articles as a', 'u.id = a.id')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` LEFT JOIN `'.DB_PREFIX.'articles` AS `a` ON `u`.`id` = `a`.`id`', self::qb()->select()->from('user as u')->left_join('articles as a', 'u.id = a.id')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` RIGHT JOIN `'.DB_PREFIX.'articles` AS `a` ON `u`.`id` = `a`.`id`', self::qb()->select()->from('user as u')->right_join('articles as a', 'u.id = a.id')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` INNER JOIN `'.DB_PREFIX.'articles` AS `a` ON `u`.`id` = `a`.`id`', self::qb()->select()->from('user as u')->inner_join('articles as a', 'u.id = a.id')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` INNER JOIN `'.DB_PREFIX.'articles` AS `a` ON `u`.`id` = `a`.`id`', self::qb()->select()->from('user as u')->join('articles as a', 'u.id = a.id', 'inner')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` INNER JOIN `'.DB_PREFIX.'articles` AS `a` ON `u`.`id` = `a`.`id` INNER JOIN `'.DB_PREFIX.'blogs` AS `b` ON `u`.`id` = `b`.`id`',	self::qb()->select()->from('user as u')->inner_join('articles as a', 'u.id = a.id')->inner_join('blogs as b', 'u.id = b.id')->sql() );
	}
	public function test_group_by() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertFalse( self::qb()->group_by()->sql() );
		$this->assertFalse( self::qb()->from()->where()->group_by()->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `id` = \'1\' GROUP BY `gid`', self::qb()->from('user')->where(array('id','=',1))->group_by('gid')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `id` = \'1\' GROUP BY `u`.`id`', self::qb()->from('user as u')->whereid(1)->group_by('u.id')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `id` = \'1\' GROUP BY `u`.`id` , `u`.`gid`', self::qb()->from('user as u')->whereid(1)->group_by('u.id', 'u.gid')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `id` = \'1\' GROUP BY `u`.`id` , `u`.`gid`', self::qb()->from('user as u')->whereid(1)->group_by('u.id')->group_by('u.gid')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `id` = \'1\' GROUP BY `u`.`id` , `u`.`gid`', self::qb()->from('user as u')->whereid(1)->group_by('u.id, u.gid')->sql() );
	}
	public function test_having() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertFalse( self::qb()->having()->sql() );
		$this->assertFalse( self::qb()->from()->where()->group_by()->having()->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `id` = \'1\' GROUP BY `gid` HAVING `gid` = \'4\'', self::qb()->from('user')->where(array('id','=',1))->group_by('gid')->having(array('gid','=',4))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` = \'1\' GROUP BY `u`.`gid` HAVING `u`.`gid` = \'4\'', self::qb()->from(array('user' => 'u'))->where(array('u.id','=',1))->group_by('u.gid')->having(array('u.gid','=',4))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` = \'1\' GROUP BY `u`.`gid` HAVING `u`.`gid` = \'4\' AND `u`.`visits` < \'4\'', self::qb()->from(array('user' => 'u'))->where(array('u.id','=',1))->group_by('u.gid')->having(array('u.gid','=',4),array('u.visits','<',4))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` = \'1\' GROUP BY `u`.`gid` HAVING `u`.`gid` = \'4\' AND `u`.`visits` < \'4\'', self::qb()->from('user as u')->where('u.id = 1')->group_by('u.gid')->having('u.gid = 4')->having('u.visits < 4')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` = \'1\' GROUP BY `u`.`gid` HAVING `u`.`gid` = \'4\' AND `u`.`visits` < \'4\'', self::qb()->from('user as u')->where('u.id = 1')->group_by('u.gid')->having('u.gid = 4, u.visits < 4')->sql() );
	}
	public function test_order_by() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }

		$this->assertFalse( self::qb()->order_by()->sql() );
		$this->assertFalse( self::qb()->from()->where()->having()->group_by()->order_by()->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` ORDER BY `id` DESC', self::qb()->from('user')->order_by(array('id' => 'desc'))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` ORDER BY `u`.`id` ASC', self::qb()->from(array('user' => 'u'))->order_by('u.id')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` ORDER BY `u`.`id` ASC , `u`.`gid` DESC', self::qb()->from('user as u')->order_by('u.id','u.gid desc')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` ORDER BY `u`.`id` ASC', self::qb()->from('user as u')->order_by('u.id','asc')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` ORDER BY `u`.`id` ASC', self::qb()->from('user as u')->order_by(array('u.id','asc'))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` ORDER BY `u`.`id` ASC , `u`.`gid` DESC', self::qb()->from('user as u')->order_by(array('u.id','asc'), array('u.gid','desc'))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` ORDER BY `u`.`id` ASC , `u`.`gid` DESC', self::qb()->from('user as u')->order_by(array('u.id' => 'asc'), array('u.gid','desc'))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` ORDER BY `u`.`id` ASC , `u`.`gid` DESC', self::qb()->from('user as u')->order_by(array('u.id', 'asc'), array('u.gid desc'))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` ORDER BY `u`.`id` ASC , `u`.`gid` DESC', self::qb()->from('user as u')->order_by('u.id', 'asc')->order_by('u.gid desc')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` ORDER BY `u`.`id` ASC , `u`.`gid` DESC', self::qb()->from('user as u')->order_by(array('u.id', 'asc'))->order_by(array('u.gid desc'))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` ORDER BY `u`.`id` ASC , `u`.`gid` DESC', self::qb()->from('user as u')->order_by('u.id asc, u.gid desc')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` ORDER BY `u`.`id` ASC , `u`.`gid` DESC', self::qb()->from('user as u')->order_by(array('u.id asc, u.gid desc'))->sql() );

		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` = \'1\' GROUP BY `u`.`gid` HAVING `u`.`gid` = \'4\' ORDER BY `u`.`id` ASC , `u`.`gid` DESC',
			self::qb()->from('user as u')->where('u.id = 1')->group_by('u.gid')->having('u.gid = 4')->order_by('u.id', 'u.gid desc')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` = \'1\' GROUP BY `u`.`gid` HAVING `u`.`gid` = \'4\' ORDER BY `u`.`id` ASC , `u`.`gid` DESC',
			self::qb()->from('user as u')->where('u.id = 1')->group_by('u.gid')->having('u.gid = 4')->order_by('u.id')->order_by('u.gid desc')->sql() );
	}
	public function test_limit() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertFalse( self::qb()->limit()->sql() );
		$this->assertFalse( self::qb()->from()->limit()->sql() );
		$this->assertFalse( self::qb()->from()->where()->having()->group_by()->order_by()->limit()->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` LIMIT 10', self::qb()->from('user')->limit(10)->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` LIMIT 20, 5', self::qb()->from('user')->limit(5,20)->sql() );

		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `id` = \'1\' GROUP BY `gid` HAVING `gid` = \'4\' ORDER BY `id` DESC LIMIT 10', self::qb()->from('user')->where(array('id','=',1))->group_by('gid')->having(array('gid','=',4))->order_by(array('id' => 'desc'))->limit(10)->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` = \'1\' GROUP BY `u`.`gid` HAVING `u`.`gid` = \'4\' ORDER BY `u`.`id` ASC LIMIT 20, 5', self::qb()->from(array('user' => 'u'))->where(array('u.id','=',1))->group_by('u.gid')->having(array('u.gid','=',4))->order_by('u.id')->limit(5, 20)->sql() );
	}
	// Testing that changing order of method calls not changing result SQL
	public function test_calls_ordering() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` = \'1\' GROUP BY `u`.`gid` HAVING `u`.`gid` = \'4\' ORDER BY `u`.`id` ASC LIMIT 20, 5', self::qb()->from(array('user' => 'u'))->where(array('u.id','=',1))->group_by('u.gid')->having(array('u.gid','=',4))->order_by('u.id')->limit(5, 20)->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` = \'1\' GROUP BY `u`.`gid` HAVING `u`.`gid` = \'4\' ORDER BY `u`.`id` ASC LIMIT 20, 5', self::qb()->from(array('user' => 'u'))->where(array('u.id','=',1))->group_by('u.gid')->having(array('u.gid','=',4))->order_by('u.id')->limit(5, 20)->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` = \'1\' GROUP BY `u`.`gid` HAVING `u`.`gid` = \'4\' ORDER BY `u`.`id` ASC LIMIT 20, 5', self::qb()->group_by('u.gid')->where(array('u.id','=',1))->order_by('u.id')->limit(5, 20)->from(array('user' => 'u'))->having(array('u.gid','=',4))->sql() );
	}
	public function test_delete() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertFalse( self::qb()->delete() );
		$this->assertEquals( 'DELETE FROM `'.DB_PREFIX.'user`', self::qb()->from('user')->delete($as_sql = true) );
		$this->assertEquals( 'DELETE FROM `'.DB_PREFIX.'user` WHERE `id` = \'1\'', self::qb()->from('user')->whereid(1)->delete($as_sql = true) );
		$this->assertEquals( 'DELETE FROM `'.DB_PREFIX.'user` WHERE `id` IN(1,2,3)', self::qb()->from('user')->whereid(array(1,2,3))->delete($as_sql = true) );
		$this->assertEquals( 'DELETE FROM `'.DB_PREFIX.'user` WHERE `uid` IN(1,2,3)', self::qb()->from('user')->whereid(array(1,2,3), 'uid')->delete($as_sql = true) );
		$this->assertEquals( 'DELETE FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` IN(1,2,3)', self::qb()->from('user as u')->whereid(array(1,2,3), 'u.id')->delete($as_sql = true) );
	}
	public function test_increment() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertFalse( self::qb()->increment(null, null, true) );
		$this->assertFalse( self::qb()->increment('visits', null, true) );
		$this->assertFalse( self::qb()->increment('visits', 1, true) );
		$this->assertFalse( self::qb()->increment('visits', 5, true) );

		$this->assertEquals( 'UPDATE `'.DB_PREFIX.'user` SET `visits` = `visits` + 1', self::qb()->table('user')->increment('visits', null, true) );
		$this->assertEquals( 'UPDATE `'.DB_PREFIX.'user` SET `visits` = `visits` + 1', self::qb()->table('user')->increment('visits', 1, true) );
		$this->assertEquals( 'UPDATE `'.DB_PREFIX.'user` SET `visits` = `visits` + 5', self::qb()->table('user')->increment('visits', 5, true) );
		$this->assertEquals( 'UPDATE `'.DB_PREFIX.'user` SET `visits` = `visits` + 500', self::qb()->table('user')->increment('visits', 500, true) );
		$this->assertEquals( 'UPDATE `'.DB_PREFIX.'user` SET `visits` = `visits` - 5', self::qb()->table('user')->increment('visits', -5, true) );

		$this->assertEquals( 'UPDATE `'.DB_PREFIX.'user` SET `visits` = `visits` + 1 WHERE `id` = \'1\'', self::qb()->table('user')->whereid(1)->increment('visits', null, true) );
		$this->assertEquals( 'UPDATE `'.DB_PREFIX.'user` SET `visits` = `visits` + 1 WHERE `id` = \'1\'', self::qb()->table('user')->whereid(1)->increment('visits', 1, true) );
		$this->assertEquals( 'UPDATE `'.DB_PREFIX.'user` SET `visits` = `visits` + 5 WHERE `id` = \'1\'', self::qb()->table('user')->whereid(1)->increment('visits', 5, true) );
		$this->assertEquals( 'UPDATE `'.DB_PREFIX.'user` SET `visits` = `visits` - 5 WHERE `id` = \'1\'', self::qb()->table('user')->whereid(1)->increment('visits', -5, true) );

		$this->assertEquals( 'UPDATE `'.DB_PREFIX.'user` SET `visits` = `visits` + 5 LIMIT 1', self::qb()->table('user')->limit(1)->increment('visits', 5, true) );
		$this->assertEquals( 'UPDATE `'.DB_PREFIX.'user` SET `visits` = `visits` + 5 WHERE `id` = \'1\' LIMIT 1', self::qb()->table('user')->whereid(1)->limit(1)->increment('visits', 5, true) );

#		$this->assertEquals( 'UPDATE `'.DB_PREFIX.'user` SET `u`.`visits` = `u`.`visits` - 5 WHERE `u`.`id` = \'1\' LIMIT 1', self::qb()->table('user as u')->where('u.id', 1)->limit(1)->decrement('u.visits', 5, true) );
#		$this->assertEquals( 'UPDATE `'.DB_PREFIX.'user` SET `visits` = `visits` + 5 WHERE `id` = \'1\' LIMIT 1', self::qb()->table('user as u')->whereid(1)->limit(1)->increment('u.visits', 5, true) );
	}
	public function test_decrement() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertFalse( self::qb()->decrement(null, null, true) );
		$this->assertFalse( self::qb()->decrement('visits', null, true) );
		$this->assertFalse( self::qb()->decrement('visits', 1, true) );
		$this->assertFalse( self::qb()->decrement('visits', 5, true) );

		$this->assertEquals( 'UPDATE `'.DB_PREFIX.'user` SET `visits` = `visits` - 1', self::qb()->table('user')->decrement('visits', null, true) );
		$this->assertEquals( 'UPDATE `'.DB_PREFIX.'user` SET `visits` = `visits` - 1', self::qb()->table('user')->decrement('visits', 1, true) );
		$this->assertEquals( 'UPDATE `'.DB_PREFIX.'user` SET `visits` = `visits` - 5', self::qb()->table('user')->decrement('visits', 5, true) );
		$this->assertEquals( 'UPDATE `'.DB_PREFIX.'user` SET `visits` = `visits` - 500', self::qb()->table('user')->decrement('visits', 500, true) );
		$this->assertEquals( 'UPDATE `'.DB_PREFIX.'user` SET `visits` = `visits` + 5', self::qb()->table('user')->decrement('visits', -5, true) );

		$this->assertEquals( 'UPDATE `'.DB_PREFIX.'user` SET `visits` = `visits` - 1 WHERE `id` = \'1\'', self::qb()->table('user')->whereid(1)->decrement('visits', null, true) );
		$this->assertEquals( 'UPDATE `'.DB_PREFIX.'user` SET `visits` = `visits` - 1 WHERE `id` = \'1\'', self::qb()->table('user')->whereid(1)->decrement('visits', 1, true) );
		$this->assertEquals( 'UPDATE `'.DB_PREFIX.'user` SET `visits` = `visits` - 5 WHERE `id` = \'1\'', self::qb()->table('user')->whereid(1)->decrement('visits', 5, true) );
		$this->assertEquals( 'UPDATE `'.DB_PREFIX.'user` SET `visits` = `visits` + 5 WHERE `id` = \'1\'', self::qb()->table('user')->whereid(1)->decrement('visits', -5, true) );

		$this->assertEquals( 'UPDATE `'.DB_PREFIX.'user` SET `visits` = `visits` - 5 LIMIT 1', self::qb()->table('user')->limit(1)->decrement('visits', 5, true) );
		$this->assertEquals( 'UPDATE `'.DB_PREFIX.'user` SET `visits` = `visits` - 5 WHERE `id` = \'1\' LIMIT 1', self::qb()->table('user')->whereid(1)->limit(1)->decrement('visits', 5, true) );
	}
	public function test_avg() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertFalse( self::qb()->avg(null, $only_sql = true) );
		$this->assertFalse( self::qb()->avg('', $only_sql = true) );
		$this->assertFalse( self::qb()->avg(false, $only_sql = true) );
		$this->assertEquals( 'SELECT AVG(`id`) FROM `'.DB_PREFIX.'user`', self::qb()->table('user')->avg(null, $only_sql = true) );
		$this->assertEquals( 'SELECT AVG(`id`) FROM `'.DB_PREFIX.'user`', self::qb()->table('user')->avg('id', $only_sql = true) );
		$this->assertEquals( 'SELECT AVG(`visits`) FROM `'.DB_PREFIX.'user`', self::qb()->table('user')->avg('visits', $only_sql = true) );
		$this->assertEquals( 'SELECT AVG(`u`.`visits`) FROM `'.DB_PREFIX.'user` AS `u`', self::qb()->table('user as u')->avg('u.visits', $only_sql = true) );
		$this->assertEquals( 'SELECT AVG(`u`.`visits`) FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`group` = \'3\'', self::qb()->table('user as u')->where('u.group', 3)->avg('u.visits', $only_sql = true) );
	}
	public function test_min() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertFalse( self::qb()->min(null, $only_sql = true) );
		$this->assertFalse( self::qb()->min('', $only_sql = true) );
		$this->assertFalse( self::qb()->min(false, $only_sql = true) );
		$this->assertEquals( 'SELECT MIN(`id`) FROM `'.DB_PREFIX.'user`', self::qb()->table('user')->min(null, $only_sql = true) );
		$this->assertEquals( 'SELECT MIN(`id`) FROM `'.DB_PREFIX.'user`', self::qb()->table('user')->min('id', $only_sql = true) );
		$this->assertEquals( 'SELECT MIN(`visits`) FROM `'.DB_PREFIX.'user`', self::qb()->table('user')->min('visits', $only_sql = true) );
		$this->assertEquals( 'SELECT MIN(`u`.`visits`) FROM `'.DB_PREFIX.'user` AS `u`', self::qb()->table('user as u')->min('u.visits', $only_sql = true) );
		$this->assertEquals( 'SELECT MIN(`u`.`visits`) FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`group` = \'3\'', self::qb()->table('user as u')->where('u.group', 3)->min('u.visits', $only_sql = true) );
	}
	public function test_max() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertFalse( self::qb()->max(null, $only_sql = true) );
		$this->assertFalse( self::qb()->max('', $only_sql = true) );
		$this->assertFalse( self::qb()->max(false, $only_sql = true) );
		$this->assertEquals( 'SELECT MAX(`id`) FROM `'.DB_PREFIX.'user`', self::qb()->table('user')->max(null, $only_sql = true) );
		$this->assertEquals( 'SELECT MAX(`id`) FROM `'.DB_PREFIX.'user`', self::qb()->table('user')->max('id', $only_sql = true) );
		$this->assertEquals( 'SELECT MAX(`visits`) FROM `'.DB_PREFIX.'user`', self::qb()->table('user')->max('visits', $only_sql = true) );
		$this->assertEquals( 'SELECT MAX(`u`.`visits`) FROM `'.DB_PREFIX.'user` AS `u`', self::qb()->table('user as u')->max('u.visits', $only_sql = true) );
		$this->assertEquals( 'SELECT MAX(`u`.`visits`) FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`group` = \'3\'', self::qb()->table('user as u')->where('u.group', 3)->max('u.visits', $only_sql = true) );
	}
	public function test_sum() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertFalse( self::qb()->sum(null, $only_sql = true) );
		$this->assertFalse( self::qb()->sum('', $only_sql = true) );
		$this->assertFalse( self::qb()->sum(false, $only_sql = true) );
		$this->assertEquals( 'SELECT SUM(`id`) FROM `'.DB_PREFIX.'user`', self::qb()->table('user')->sum(null, $only_sql = true) );
		$this->assertEquals( 'SELECT SUM(`id`) FROM `'.DB_PREFIX.'user`', self::qb()->table('user')->sum('id', $only_sql = true) );
		$this->assertEquals( 'SELECT SUM(`visits`) FROM `'.DB_PREFIX.'user`', self::qb()->table('user')->sum('visits', $only_sql = true) );
		$this->assertEquals( 'SELECT SUM(`u`.`visits`) FROM `'.DB_PREFIX.'user` AS `u`', self::qb()->table('user as u')->sum('u.visits', $only_sql = true) );
		$this->assertEquals( 'SELECT SUM(`u`.`visits`) FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`group` = \'3\'', self::qb()->table('user as u')->where('u.group', 3)->sum('u.visits', $only_sql = true) );
	}
	public function test_count() {
		$this->assertFalse( self::qb()->count(null, true) );
		$this->assertFalse( self::qb()->count('', $only_sql = true) );
		$this->assertFalse( self::qb()->count(false, $only_sql = true) );
		$this->assertEquals( 'SELECT COUNT(*) FROM `'.DB_PREFIX.'user`', self::qb()->table('user')->count(null, $only_sql = true) );
		$this->assertEquals( 'SELECT COUNT(`id`) FROM `'.DB_PREFIX.'user`', self::qb()->table('user')->count('id', $only_sql = true) );
		$this->assertEquals( 'SELECT COUNT(`visits`) FROM `'.DB_PREFIX.'user`', self::qb()->table('user')->count('visits', $only_sql = true) );
		$this->assertEquals( 'SELECT COUNT(`u`.`visits`) FROM `'.DB_PREFIX.'user` AS `u`', self::qb()->table('user as u')->count('u.visits', $only_sql = true) );
		$this->assertEquals( 'SELECT COUNT(`u`.`visits`) FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`group` = \'3\'', self::qb()->table('user as u')->where('u.group', 3)->count('u.visits', $only_sql = true) );
	}
	public function test_compile_insert() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$data = array('user_id'	=> 1, 'date' => '1234567890', 'total_sum' => '19,12', 'name' => 'name');
		$this->assertEquals( 
			'INSERT INTO `'.DB_PREFIX.'shop_orders` (`user_id`, `date`, `total_sum`, `name`) VALUES (\'1\', \'1234567890\', \'19,12\', \'name\')', 
			str_replace(PHP_EOL, '', self::qb()->compile_insert('shop_orders', $data) )
		);
		$this->assertEquals( 
			'REPLACE INTO `'.DB_PREFIX.'shop_orders` (`user_id`, `date`, `total_sum`, `name`) VALUES (\'1\', \'1234567890\', \'19,12\', \'name\')', 
			str_replace(PHP_EOL, '', self::qb()->compile_insert('shop_orders', $data, array('replace' => true)) )
		);
		$this->assertEquals( 
			'INSERT IGNORE INTO `'.DB_PREFIX.'shop_orders` (`user_id`, `date`, `total_sum`, `name`) VALUES (\'1\', \'1234567890\', \'19,12\', \'name\')', 
			str_replace(PHP_EOL, '', self::qb()->compile_insert('shop_orders', $data, array('ignore' => true)) )
		);
		$this->assertEquals( 
			'INSERT INTO `'.DB_PREFIX.'shop_orders` (`user_id`, `date`, `total_sum`, `name`) VALUES (\'1\', \'1234567890\', \'19,12\', \'name\') ON DUPLICATE KEY UPDATE `user_id` = VALUES(`user_id`), `date` = VALUES(`date`), `total_sum` = VALUES(`total_sum`), `name` = VALUES(`name`)', 
			str_replace(PHP_EOL, '', self::qb()->compile_insert('shop_orders', $data, array('on_duplicate_key_update' => true)) )
		);
	}
	public function test_compile_update() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$data = array('user_id'	=> 1, 'date' => '1234567890', 'total_sum' => '19,12', 'name' => 'name');
		$this->assertEquals(
			'UPDATE `'.DB_PREFIX.'shop_orders` SET `user_id` = \'1\', `date` = \'1234567890\', `total_sum` = \'19,12\', `name` = \'name\' WHERE id=1',
			str_replace(PHP_EOL, '', self::qb()->compile_update('shop_orders', $data, 'id=1') )
		);
		$this->assertEquals(
			'UPDATE `'.DB_PREFIX.'shop_orders` SET `user_id` = \'1\', `date` = \'1234567890\', `total_sum` = \'19,12\', `name` = \'name\' WHERE id=1',
			str_replace(PHP_EOL, '', self::qb()->compile_update('shop_orders', $data, '1') )
		);
	}
	public function test_insert() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$data = array('user_id'	=> 1, 'date' => '1234567890', 'total_sum' => '19,12', 'name' => 'name');
		$this->assertEquals( 
			'INSERT INTO `'.DB_PREFIX.'shop_orders` (`user_id`, `date`, `total_sum`, `name`) VALUES (\'1\', \'1234567890\', \'19,12\', \'name\')', 
			str_replace(PHP_EOL, '', self::qb()->table('shop_orders')->insert($data, array('sql' => true)) )
		);
	}
	public function test_insert_into() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
#		$this->assertEquals( 
#			'INSERT INTO `'.DB_PREFIX.'stats_archive` (`user_id`,`visits`) SELECT `user_id`, `visits` FROM `'.DB_PREFIX.'stats` WHERE `id` > 1234',
#			str_replace(PHP_EOL, '', self::qb()->select('user_id','visits')->from('stats')->insert_into('stats_archive', array('sql' => true)) )
#		);
	}
	public function test_update() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$data = array('user_id'	=> 1, 'date' => '1234567890', 'total_sum' => '19,12', 'name' => 'name');
		$this->assertEquals(
			'UPDATE `'.DB_PREFIX.'shop_orders` SET `user_id` = \'1\', `date` = \'1234567890\', `total_sum` = \'19,12\', `name` = \'name\' WHERE `id` = \'1\'',
			str_replace(PHP_EOL, '', self::qb()->table('shop_orders')->whereid(1)->update($data, array('sql' => true)) )
		);
		$this->assertEquals(
			'UPDATE `'.DB_PREFIX.'shop_orders` SET `user_id` = \'1\', `date` = \'1234567890\', `total_sum` = \'19,12\', `name` = \'name\' WHERE `id` >= \'1\'',
			str_replace(PHP_EOL, '', self::qb()->table('shop_orders')->where('id >= 1')->update($data, array('sql' => true)) )
		);
	}
	public function test_update_batch() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$data = array(
			1 => array('id' => 1, 'name' => 'name1'),
			2 => array('id' => 2, 'name' => 'name2'),
		);
		$this->assertEquals(
			'UPDATE `'.DB_PREFIX.'users` SET `name` = CASE  WHEN `id` = \'1\' THEN \'name1\' WHEN `id` = \'2\' THEN \'name2\' ELSE `name` END WHERE `id` IN(\'1\',\'2\');',
			trim(str_replace(PHP_EOL, ' ', self::qb()->table('users')->update_batch('users', $data, 'id', $only_sql = true)) )
		);
	}
	public function test_render_select() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertEquals('SELECT `id` , `name`', self::qb()->select('id, name')->_render_select());
		$this->assertEquals('SELECT `id` AS `uid` , `name` AS `uname`', self::qb()->select('id as uid, name as uname')->_render_select());
	}
	public function test_render_from() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertEquals('FROM `'.DB_PREFIX.'user`', self::qb()->from('user')->_render_from());
		$this->assertEquals('FROM `'.DB_PREFIX.'user` AS `u`', self::qb()->from('user as u')->_render_from());
		$this->assertEquals('FROM `'.DB_PREFIX.'user` AS `u` , `'.DB_PREFIX.'stats` AS `s`', self::qb()->from('user as u, stats as s')->_render_from());
	}
	public function test_render_where() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertEquals('WHERE `u`.`id` > \'5\'', self::qb()->where('u.id > 5')->_render_where());
		$this->assertEquals('WHERE `u`.`id` > \'5\' AND `u`.`id` < \'3\'', self::qb()->where('u.id > 5', 'u.id < 3')->_render_where());
		$this->assertEquals('WHERE `u`.`id` > \'5\' OR `u`.`id` < \'3\'', self::qb()->where('u.id > 5')->where_or('u.id < 3')->_render_where());
	}
	public function test_render_joins() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertEquals('JOIN `'.DB_PREFIX.'articles` AS `a` ON `u`.`id` = `a`.`id`', self::qb()->join(array('articles' => 'a'), array('u.id' => 'a.id'))->_render_joins());
	}
	public function test_render_order_by() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertEquals('ORDER BY `add_date` ASC', self::qb()->order_by('add_date')->_render_order_by());
		$this->assertEquals('ORDER BY `add_date` DESC', self::qb()->order_by('add_date','desc')->_render_order_by());
		$this->assertEquals('ORDER BY `u`.`add_date` ASC', self::qb()->order_by('u.add_date')->_render_order_by());
		$this->assertEquals('ORDER BY `u`.`add_date` ASC , `u`.`visits` DESC', self::qb()->order_by('u.add_date asc', 'u.visits desc')->_render_order_by());
	}
	public function test_render_limit() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertEquals('LIMIT 10, 1', self::qb()->limit(1,10)->_render_limit());
	}
	public function test_split_by_comma() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertSame(array(array('1','2','3')), self::qb()->_split_by_comma(array('1,2,3')));
	}
	public function test_ids_sql_from_array() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertSame(array(1=>1,2=>2,3=>3), self::qb()->_ids_sql_from_array(array(1,2,3)));
	}
	public function test_is_where_all_numeric() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$data = array('id' => 1, 2, 3);
		$this->assertFalse(self::qb()->_is_where_all_numeric($data));
		$data = array(1, 2, 'id' => 3);
		$this->assertFalse(self::qb()->_is_where_all_numeric($data));
		$data = array(1, 2, 'id');
		$this->assertFalse(self::qb()->_is_where_all_numeric($data));
		$data = array(1,2,3);
		$this->assertTrue(self::qb()->_is_where_all_numeric($data));
		$data = array(1, 2, '');
		$this->assertTrue(self::qb()->_is_where_all_numeric($data));
		$this->assertSame(array(1, 2), $data);
	}
	public function test_subquery() {
// TODO
	}
	public function test_any() {
// TODO
	}
	public function test_exists() {
// TODO
	}
	public function test_not_exists() {
// TODO
	}
	public function test_union() {
// TODO
	}
	public function test_union_all() {
// TODO
	}
	public function test_chunk() {
// TODO
	}
	public function test_shared_lock() {
// TODO
	}
	public function test_lock_for_update() {
// TODO
	}
}
