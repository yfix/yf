<?php

require dirname(__FILE__).'/yf_unit_tests_setup.php';

class class_db_query_builder_test extends PHPUnit_Framework_TestCase {
	private function qb() {
		return _class('db')->query_builder();
	}
	public function test_01() {
		$this->assertEquals( 'SELECT *', self::qb()->select()->_sql['select'] );
		$this->assertEquals( 'SELECT *', self::qb()->select('*')->_sql['select'] );
		$this->assertEquals( 'SELECT *', self::qb()->select(' *')->_sql['select'] );
		$this->assertEquals( 'SELECT *', self::qb()->select('* ')->_sql['select'] );
		$this->assertEquals( 'SELECT *', self::qb()->select('   *   ')->_sql['select'] );
		$this->assertFalse( self::qb()->select()->render() );
		$this->assertFalse( self::qb()->select()->__toString() );
	}
	public function test_02() {
		$this->assertEquals( 'SELECT id', self::qb()->select('id')->_sql['select'] );
		$this->assertFalse( self::qb()->select('id')->render() );
	}
	public function test_03() {
		$this->assertEquals( 'SELECT id, name', self::qb()->select('id','name')->_sql['select'] );
		$this->assertFalse( self::qb()->select('id','name')->render() );
	}
	public function test_04() {
		$this->assertNull( self::qb()->select('')->_sql['select'] );
		$this->assertNull( self::qb()->select(array())->_sql['select'] );
		$this->assertNull( self::qb()->select(false)->_sql['select'] );
		$this->assertNull( self::qb()->select(0)->_sql['select'] );
		$this->assertNull( self::qb()->select('0')->_sql['select'] );

		$this->assertFalse( self::qb()->select('')->render() );
		$this->assertFalse( self::qb()->select(array())->render() );
		$this->assertFalse( self::qb()->select(false)->render() );
		$this->assertFalse( self::qb()->select(0)->render() );
		$this->assertFalse( self::qb()->select('0')->render() );
	}
// TODO
#	public function test_05() {
#		$this->assertEquals( 'SELECT `id id`', self::qb()->select('id id')->_sql['select'] );
#		$this->assertEquals( 'SELECT u.id, s.id, t.pid', self::qb()->select('u.id', 's.id', 't.pid')->_sql['select'] );
#		$this->assertEquals( 'SELECT u.id, a.id, b.id', self::qb()->select(array('u.id', 'a.id', 'b.id'))->_sql['select'] );
#		$this->assertEquals( 'SELECT u.id AS user_id, a.id AS article_id, b.id AS blog_id', self::qb()->select(array('u.id' => 'user_id', 'a.id' => 'article_id', 'b.id' => 'blog_id'))->_sql['select'] );
#		$this->assertEquals( 'SELECT u.id AS user_id', self::qb()->select(array('u.id' => 'user_id'))->_sql['select'] );
#		$this->assertEquals( 'SELECT COUNT(*) AS num', self::qb()->select(array('COUNT(*)' => 'num'))->_sql['select'] );
#		$this->assertEquals( 'SELECT COUNT(id) AS num', self::qb()->select(array('COUNT(id)' => 'num'))->_sql['select'] );
#		$this->assertEquals( 'SELECT COUNT(u.id) AS num', self::qb()->select(array('COUNT(u.id)' => 'num'))->_sql['select'] );
#		$this->assertEquals( 'SELECT DISTINCT u.id', self::qb()->select('DISTINCT u.id')->_sql['select'] );
#		$this->assertEquals( 'SELECT DISTINCT u.id AS num', self::qb()->select(array('DISTINCT u.id' => 'num'))->_sql['select'] );
#		$this->assertEquals( 'SELECT DISTINCT u.id AS num, a.id AS article_id', self::qb()->select( function(){return 'DISTINCT u.id AS num';}, function(){return 'a.id AS article_id';} )->_sql['select'] );
#	}
	public function test_10() {
		$this->assertFalse( self::qb()->select()->from()->render() );
		$this->assertEquals( 'SELECT * FROM '.DB_PREFIX.'user', self::qb()->select()->from('user')->render() );
	}
}