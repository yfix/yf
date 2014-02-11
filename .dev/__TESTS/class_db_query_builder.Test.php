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
}