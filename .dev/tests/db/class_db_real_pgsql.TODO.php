<?php

require_once __DIR__.'/db_real_abstract.php';

/**
 * @requires extension pgsql
 */
class class_db_real_pgsql_test extends db_real_abstract {
	public static function setUpBeforeClass() {
		self::$_bak['DB_DRIVER'] = self::$DB_DRIVER;
		self::$DB_DRIVER = 'pgsql';
		self::_connect();
#		self::utils()->truncate_database(self::db_name());
	}
	public static function tearDownAfterClass() {
#		self::utils()->truncate_database(self::db_name());
		self::$DB_DRIVER = self::$_bak['DB_DRIVER'];
	}
	public static function _connect($params = array()) {
		self::$DB_NAME = $params['name'] ?: is_string(getenv('YF_DB_PG_NAME')) ? getenv('YF_DB_PG_NAME') : DB_NAME);
		if ($params['driver']) {
			self::$DB_DRIVER = $params['driver'];
		}
		$db_class = load_db_class();
		self::$db = new $db_class(self::$DB_DRIVER);
		self::$db->ALLOW_AUTO_CREATE_DB = true;
		self::$db->NO_AUTO_CONNECT = true;
		self::$db->RECONNECT_NUM_TRIES = 1;
		self::$db->CACHE_TABLE_NAMES = false;
		self::$db->ERROR_AUTO_REPAIR = false;
		self::$db->FIX_DATA_SAFE = true;
		self::$db->_init();
		$res = self::$db->connect(array(
			'host'	=> $params['host'] ?: (is_string(getenv('YF_DB_PG_HOST')) ? getenv('YF_DB_PG_HOST') : '127.0.0.1'),
			'name'	=> self::$DB_NAME,
			'user'	=> $params['user'] ?: (is_string(getenv('YF_DB_PG_USER')) ? getenv('YF_DB_PG_USER') : 'yf'),
			'pswd'	=> is_string($params['pswd']) ? $params['pswd'] : (is_string(getenv('YF_DB_PG_PSWD')) ? getenv('YF_DB_PG_PSWD') : DB_PSWD),
			'prefix'=> $params['prefix'] ?: (is_string(getenv('YF_DB_PG_PREFIX')) ? getenv('YF_DB_PG_PREFIX') : DB_PREFIX),
			'force' => true,
		));
		return !empty($res) ? true : false;
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

	public function test_disconnect_connect() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$this->assertTrue( self::db()->close() );
		$this->assertFalse( self::$db->_connected );
		$this->assertFalse( self::$db->_tried_to_connect );
		$this->assertNull( self::$db->db );
		$this->assertTrue( self::_connect() );
		$this->assertTrue( self::$db->_connected );
		$this->assertTrue( self::$db->_tried_to_connect );
		$this->assertTrue( is_object(self::$db->db) );
		$this->assertTrue( !empty(self::$db->db->db_connect_id) );
	}
	public function test_basic_queries_and_fetching() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$table = self::db()->DB_PREFIX. __FUNCTION__;
		$this->assertNotEmpty( self::db()->query($this->create_table_sql($table)) );
		$expected = array(
			'Table' => $table,
			'Create Table' => 'CREATE TABLE `'.$table.'` ('. PHP_EOL
				. '  `id` int(10) NOT NULL AUTO_INCREMENT,'. PHP_EOL
				. '  `id2` int(10) DEFAULT NULL,'. PHP_EOL
				. '  `id3` int(10) DEFAULT NULL,'. PHP_EOL
				. '  PRIMARY KEY (`id`)'. PHP_EOL
				. ') ENGINE=InnoDB DEFAULT CHARSET=utf8',
		);
		$sql = 'SHOW CREATE TABLE '.$this->table_name($table);
		$this->assertEquals( $expected, self::db()->fetch_assoc(self::db()->query($sql)) );
		$this->assertEquals( $expected, self::db()->fetch_assoc(self::db()->unbuffered_query($sql)) );
		$this->assertEquals( $expected, self::db()->query_fetch($sql) );
		$this->assertEquals( $expected, self::db()->get($sql) );

		$this->assertNotEmpty( self::db()->query('INSERT INTO '.$this->table_name($table).' VALUES (1,1,1),(2,2,2),(3,3,3)') );
		$this->assertEquals( 3, self::db()->affected_rows() );
		$this->assertEquals( 3, self::db()->insert_id() );
		$this->assertEquals( array('id' => 1), self::db()->get('SELECT id FROM '.$this->table_name($table)) );
		$this->assertEquals( array(1 => array('id' => 1), 2 => array('id' => 2), 3 => array('id' => 3)), self::db()->get_all('SELECT id FROM '.$this->table_name($table)) );
		$this->assertEquals( array(3 => array('id' => 3), 2 => array('id' => 2), 1 => array('id' => 1)), self::db()->get_all('SELECT id FROM '.$this->table_name($table).' ORDER BY id DESC') );
		$this->assertEmpty( self::db()->get('SELECT id FROM '.$this->table_name($table).' WHERE id > 9999') );
		$this->assertEmpty( self::db()->get_all('SELECT id FROM '.$this->table_name($table).' WHERE id > 9999') );

		$this->assertEquals( 3, self::db()->num_rows(self::db()->query('SELECT id FROM '.$this->table_name($table))) );
		$this->assertEquals( 3, self::db()->query_num_rows('SELECT id FROM '.$this->table_name($table)) );

		$q = self::db()->query('SELECT id FROM '.$this->table_name($table));
		$this->assertEquals( 3, self::db()->num_rows($q) );
		$this->assertEquals( array('id' => 1), self::db()->fetch_assoc($q) );
		$this->assertTrue( self::db()->free_result($q) );

		$this->assertEquals( array('message' => '', 'code' => 0), self::db()->error() );

		$this->assertEquals( array(1), self::db()->fetch_row(self::db()->query('SELECT id FROM '.$this->table_name($table))) );
		$obj = new stdClass();
		$obj->id = 1;
		$this->assertEquals( $obj, self::db()->fetch_object(self::db()->query('SELECT id FROM '.$this->table_name($table))) );
	}
}
