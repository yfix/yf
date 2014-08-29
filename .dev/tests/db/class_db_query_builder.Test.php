<?php

require_once dirname(__DIR__).'/yf_unit_tests_setup.php';
require_once dirname(__DIR__).'/db_setup.php';

/**
 * @requires extension mysql
 */
class class_db_query_builder_test extends PHPUnit_Framework_TestCase {
	public static $_er = array();
	public static function setUpBeforeClass() {
		self::$_er = error_reporting();
		error_reporting(0);
	}
	public static function tearDownAfterClass() {
		error_reporting(self::$_er);
	}
	private function qb() {
		return _class('db')->query_builder();
	}
	public function test_select1() {
		$this->assertEquals( '*', self::qb()->select()->_sql['select'][0] );
		$this->assertEquals( '*', self::qb()->select('*')->_sql['select'][0] );
		$this->assertEquals( '*', self::qb()->select(' *')->_sql['select'][0] );
		$this->assertEquals( '*', self::qb()->select('* ')->_sql['select'][0] );
		$this->assertEquals( '*', self::qb()->select('   *   ')->_sql['select'][0] );
		$this->assertFalse( self::qb()->select()->sql() );
		$this->assertFalse( self::qb()->select()->__toString() );
	}
	public function test_select2() {
		$this->assertEquals( '`id`', self::qb()->select('id')->_sql['select'][0] );
		$this->assertFalse( self::qb()->select('id')->sql() );
		$this->assertEquals( 'COUNT(id)', self::qb()->select('COUNT(id)')->_sql['select'][0] );
		$this->assertEquals( 'SUM(id)', self::qb()->select('SUM(id)')->_sql['select'][0] );
		$this->assertEquals( 'MIN(id)', self::qb()->select('MIN(id)')->_sql['select'][0] );
		$this->assertEquals( 'MAX(id)', self::qb()->select('MAX(id)')->_sql['select'][0] );
		$this->assertEquals( 'AVG(id)', self::qb()->select('AVG(id)')->_sql['select'][0] );
		$this->assertEquals( 'COUNT(id) AS `num`', self::qb()->select('COUNT(id) as num')->_sql['select'][0] );
	}
	public function test_select3() {
		$this->assertEquals( '`id`, `name`', self::qb()->select('id','name')->_sql['select'][0] );
		$this->assertFalse( self::qb()->select('id','name')->sql() );
	}
	public function test_select4() {
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
	public function test_select5() {
		$this->assertEquals( '`u`.`id`, `s`.`id`, `t`.`pid`', self::qb()->select('u.id', 's.id', 't.pid')->_sql['select'][0] );
		$this->assertEquals( '`u`.`id` AS `user_id`', self::qb()->select(array('u.id' => 'user_id'))->_sql['select'][0] );
		$this->assertEquals( '`u`.`id` AS `user_id`, `a`.`id` AS `article_id`, `b`.`id` AS `blog_id`', self::qb()->select(array('u.id' => 'user_id', 'a.id' => 'article_id', 'b.id' => 'blog_id'))->_sql['select'][0] );
		$this->assertEquals( '`u`.`id` AS `user_id`, `a`.`id` AS `article_id`, `b`.`id` AS `blog_id`', self::qb()->select(array('u.id' => 'user_id'), array('a.id' => 'article_id'), array('b.id' => 'blog_id'))->_sql['select'][0] );
		$this->assertEquals( 'COUNT(*) AS `num`', self::qb()->select(array('COUNT(*)' => 'num'))->_sql['select'][0] );
		$this->assertEquals( 'COUNT(id) AS `num`', self::qb()->select(array('COUNT(id)' => 'num'))->_sql['select'][0] );
		$this->assertEquals( 'COUNT(u.id) AS `num`', self::qb()->select(array('COUNT(u.id)' => 'num'))->_sql['select'][0] );
		$this->assertEquals( 'DISTINCT u.id', self::qb()->select('DISTINCT u.id')->_sql['select'][0] );
		$this->assertEquals( 'DISTINCT u.id AS `num`', self::qb()->select(array('DISTINCT u.id' => 'num'))->_sql['select'][0] );
		$this->assertEquals( 'DISTINCT u.id AS `num`, a.id AS `article_id`', self::qb()->select( function(){return 'DISTINCT u.id AS `num`';}, function(){return 'a.id AS `article_id`';} )->_sql['select'][0] );
#		$this->assertEquals( 'u.id, a.id, b.id', self::qb()->select(array('u.id', 'a.id', 'b.id'))->_sql['select'][0] );
#		$this->assertEquals( '`id id`', self::qb()->select('id id')->_sql['select'][0] );
	}
	public function test_select_string_as() {
		$this->assertEquals( '`s`.`id` AS `sid`', self::qb()->select('s.id as sid')->_sql['select'][0] );
		$this->assertEquals( '`s`.`id` AS `sid`, `u`.`id` AS `uid`', self::qb()->select('s.id as sid', 'u.id as uid')->_sql['select'][0] );
		$this->assertEquals( '`u`.`id` AS `uid`', self::qb()->select(array('u.id as uid'))->_sql['select'][0] );
	}
	public function test_select_complex() {
		$this->assertEquals( 'SELECT `s`.`id` , `u`.`id` FROM `'.DB_PREFIX.'user`', self::qb()->select('s.id')->select('u.id')->from('user')->sql() );
		$this->assertEquals( 'SELECT `s`.`id` AS `sid` , `u`.`id` AS `uid` FROM `'.DB_PREFIX.'user`', self::qb()->select('s.id as sid')->select('u.id as uid')->from('user')->sql() );
	}
	public function test_from() {
		$this->assertFalse( self::qb()->from()->sql() );
		$this->assertFalse( self::qb()->select()->from()->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user`', self::qb()->from('user')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user`', self::qb()->select()->from('user')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user`, `'.DB_PREFIX.'articles`', self::qb()->select()->from('user','articles')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u`', self::qb()->select()->from(array('user' => 'u'))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u`, `'.DB_PREFIX.'articles` AS `a`', self::qb()->select()->from(array('user' => 'u', 'articles' => 'a'))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u`, `'.DB_PREFIX.'articles` AS `a`', self::qb()->select()->from(array('user' => 'u'), array('articles' => 'a'))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` , `'.DB_PREFIX.'articles` AS `a`', self::qb()->select()->from(array('user' => 'u'))->from(array('articles' => 'a'))->sql() );
	}
	public function test_from_string_as() {
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u`', self::qb()->select()->from('user as u')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u`, `'.DB_PREFIX.'articles` AS `a`', self::qb()->select()->from('user as u', 'articles as a')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` , `'.DB_PREFIX.'articles` AS `a`', self::qb()->select()->from('user as u')->from('articles as a')->sql() );
	}
	public function test_where() {
		$this->assertFalse( self::qb()->where()->sql() );
		$this->assertFalse( self::qb()->from()->where()->sql() );
		$this->assertFalse( self::qb()->select()->from()->where()->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `id` = \'1\'', self::qb()->from('user')->where('id','=','1')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `id` = \'1\'', self::qb()->from('user')->where(array('id','=',1))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user`, `'.DB_PREFIX.'articles` WHERE `u`.`id` = \'1\'', self::qb()->from('user','articles')->where(array('u.id','=',1))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` = \'1\'', self::qb()->from(array('user' => 'u'))->where(array('u.id','=',1))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` = \'1\' AND `u`.`gid` = \'4\'', 
			self::qb()->from(array('user' => 'u'))->where(array('u.id','=','1'),'and',array('u.gid','=','4'))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` = \'1\' OR `u`.`gid` = \'4\'', 
			self::qb()->from(array('user' => 'u'))->where(array('u.id','=','1'),'or',array('u.gid','=','4'))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` = \'1\' XOR `u`.`gid` = \'4\'', 
			self::qb()->from(array('user' => 'u'))->where(array('u.id','=','1'),'xor',array('u.gid','=','4'))->sql() );

		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `id` = \'1\' AND `gid` = \'4\'', 
			self::qb()->from(array('user' => 'u'))->where(array('id' => '1', 'gid' => '4'))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` = \'1\' AND `u`.`gid` = \'4\'', 
			self::qb()->from(array('user' => 'u'))->where(array('u.id' => '1', 'u.gid' => '4'))->sql() );
	}
	public function test_where_like() {
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `name` LIKE \'test\'', self::qb()->from('user')->where('name','like','test')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `name` LIKE \'test\'', self::qb()->from('user')->where('name','LIKE','test')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `name` LIKE \'test%\'', self::qb()->from('user')->where('name','like','test%')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `name` LIKE \'test%\'', self::qb()->from('user')->where('name','like','test*')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `name` NOT LIKE \'test%\'', self::qb()->from('user')->where('name','not like','test*')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `name` RLIKE \'(test|other)\'', self::qb()->from('user')->where('name','rlike','(test|other)')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `name` NOT RLIKE \'(test|other)\'', self::qb()->from('user')->where('name','not rlike','(test|other)')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` LIKE \'1%\' AND `u`.`gid` LIKE \'%4\'', 
			self::qb()->from(array('user' => 'u'))->where(array('u.id' => '1*', 'u.gid' => '*4'))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` LIKE \'%1%\' XOR `u`.`gid` NOT LIKE \'%4%\'', 
			self::qb()->from(array('user' => 'u'))->where(array('u.id','like','%1%'),'xor',array('u.gid','not like','%4%'))->sql() );
	}
	public function test_where_simple_syntax() {
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` = \'1\'', self::qb()->from('user as u')->where('u.id = 1')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` > \'1\'', self::qb()->from('user as u')->where('u.id > 1')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` > \'1\' AND `u`.`visits` < \'3\'', self::qb()->from('user as u')->where('u.id > 1')->where('u.visits < 3')->sql() );
	}
	public function test_where_or() {
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` = \'1\' OR `u`.`gid` = \'4\'',
			self::qb()->from('user as u')->where('u.id = 1')->where_or('u.gid = 4')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` WHERE `u`.`id` = \'1\' OR `u`.`gid` = \'4\' OR `u`.`visits` < \'4\'',
			self::qb()->from('user as u')->where('u.id = 1')->where_or('u.gid = 4')->where_or('u.visits < 4')->sql() );
	}
	public function test_whereid() {
		$this->assertFalse( self::qb()->whereid()->sql() );
		$this->assertFalse( self::qb()->from()->whereid()->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `id` = \'1\'', self::qb()->from('user')->whereid(1)->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `id` = \'1\'', self::qb()->from('user')->whereid(1, '')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `uid` = \'1\'', self::qb()->from('user')->whereid(1, 'uid')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `u`.`id` = \'1\'', self::qb()->from('user')->whereid(1, 'u.id')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `id` IN(1,2,3)', self::qb()->from('user')->whereid(array(1,2,3))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `uid` IN(1,2,3)', self::qb()->from('user')->whereid(array(1,2,3), 'uid')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` WHERE `u`.`id` IN(1,2,3)', self::qb()->from('user')->whereid(array(1,2,3), 'u.id')->sql() );
	}
	public function test_join() {
		$this->assertFalse( self::qb()->join()->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` JOIN `'.DB_PREFIX.'articles` AS `a` ON `u`.`id` = `a`.`id`',
			self::qb()->select()->from('user as u')->join(array('articles' => 'a'), array('u.id' => 'a.id'))->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` JOIN `'.DB_PREFIX.'articles` AS `a` ON `u`.`id` = `a`.`id`', 
			self::qb()->select()->from('user as u')->join('articles as a', 'u.id = a.id')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` LEFT JOIN `'.DB_PREFIX.'articles` AS `a` ON `u`.`id` = `a`.`id`', 
			self::qb()->select()->from('user as u')->left_join('articles as a', 'u.id = a.id')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` RIGHT JOIN `'.DB_PREFIX.'articles` AS `a` ON `u`.`id` = `a`.`id`', 
			self::qb()->select()->from('user as u')->right_join('articles as a', 'u.id = a.id')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` INNER JOIN `'.DB_PREFIX.'articles` AS `a` ON `u`.`id` = `a`.`id`', 
			self::qb()->select()->from('user as u')->inner_join('articles as a', 'u.id = a.id')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` INNER JOIN `'.DB_PREFIX.'articles` AS `a` ON `u`.`id` = `a`.`id`', 
			self::qb()->select()->from('user as u')->join('articles as a', 'u.id = a.id', 'inner')->sql() );
		$this->assertEquals( 'SELECT * FROM `'.DB_PREFIX.'user` AS `u` INNER JOIN `'.DB_PREFIX.'articles` AS `a` ON `u`.`id` = `a`.`id` INNER JOIN `'.DB_PREFIX.'blogs` AS `b` ON `u`.`id` = `b`.`id`', 
			self::qb()->select()->from('user as u')->inner_join('articles as a', 'u.id = a.id')->inner_join('blogs as b', 'u.id = b.id')->sql() );
	}
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
}