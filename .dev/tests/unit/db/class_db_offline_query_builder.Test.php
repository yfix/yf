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
// TODO
	}
	public function test_min() {
// TODO
	}
	public function test_max() {
// TODO
	}
	public function test_sum() {
// TODO
	}
	public function test_where_raw() {
// TODO
	}
	public function test_compile_insert() {
// TODO
	}
	public function test_compile_update() {
// TODO
	}
	public function test_render_select() {
// TODO
	}
	public function test_render_from() {
// TODO
	}
	public function test_render_where() {
// TODO
	}
	public function test_render_joins() {
// TODO
	}
	public function test_render_order_by() {
// TODO
	}
	public function test_render_limit() {
// TODO
	}
	public function test_insert() {
// TODO
	}
	public function test_replace() {
// TODO
	}
	public function test_insert_into() {
// TODO
	}
	public function test_replace_into() {
// TODO
	}
	public function test_update() {
// TODO
	}
	public function test_update_batch() {
// TODO
	}
	public function test_union() {
// TODO
	}
	public function test_union_all() {
// TODO
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
	public function test_chunk() {
// TODO
	}
	public function test_shared_lock() {
// TODO
	}
	public function test_lock_for_update() {
// TODO
	}
	public function test_split_by_comma() {
// TODO
	}
	public function test_ids_sql_from_array() {
// TODO
	}
	public function test_is_where_all_numeric() {
// TODO
	}
}
