<?php

require_once __DIR__.'/db_real_abstract.php';

/**
 * @requires extension mysql
 */
class class_db_real_installer_mysql_test extends db_real_abstract {

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
	public static function db_name() {
		return self::$DB_NAME;
	}
	public static function table_name($name) {
		return self::db_name().'.'.$name;
	}

/*
	public function test_sql_into_array_basic() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }

		$sql = 'id varchar(32) NOT NULL default \'\'';
		$expected = array(
			'fields' => array(
				'id' => array( 'type' => 'varchar', 'length' => '32', 'attrib' => null, 'not_null' => 1, 'default' => '', 'auto_inc' => 0 ),
			),
		);
		$received = _class('db_installer_mysql', 'classes/db/')->_db_table_struct_into_array($sql);
		$this->assertSame( $expected, $received );
	}
*/
	public function test_sql_into_array_complex() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
/*
		$sql = '
			`id` varchar(32) NOT NULL default \'\',
			`user_id` int(10) unsigned NOT NULL,
			`user_group` int(10) unsigned NOT NULL,
			`time` int(10) unsigned NOT NULL default \'0\',
			`type` enum(\'user\',\'admin\') NOT NULL,
			`ip` varchar(16) NOT NULL,
			`user_agent` varchar(255) NOT NULL,
			`query_string` varchar(255) NOT NULL,
			`site_id` tinyint(3) unsigned NOT NULL,
			PRIMARY KEY	(`id`),
			KEY `user_id` (`user_id`)
		';
		$this->assertEquals( $this->data_good, _class('db_installer_mysql', 'classes/db/')->_db_table_struct_into_array($sql) );

		$sql = '
			`id` varchar(32) NOT NULL default \'\',
			`user_id` int(10) unsigned NOT NULL,
			`user_group` int(10) unsigned NOT NULL,
			`time` int(10) unsigned NOT NULL default \'0\',
			`type` enum(\'user\',\'admin\') NOT NULL,
			`ip` varchar(16) NOT NULL,
			`user_agent` varchar(255) NOT NULL,
			`query_string` varchar(255) NOT NULL,
			`site_id` tinyint(3) unsigned NOT NULL,
			PRIMARY KEY	(`id`),
			KEY `user_id` (`user_id`)
		';
		$this->assertEquals( $this->data_good, _class('db_installer_mysql', 'classes/db/')->_db_table_struct_into_array($sql) );
*/
	}
	public function test_sql_into_array2() {
/*
		$sql = '`id` varchar(32) NOT NULL default \'\', `user_id` int(10) unsigned NOT NULL, `user_group` int(10) unsigned NOT NULL, `time` int(10) unsigned NOT NULL default \'0\',
			`type` enum(\'user\',\'admin\') NOT NULL, `ip` varchar(16) NOT NULL, `user_agent` varchar(255) NOT NULL, `query_string` varchar(255) NOT NULL,
			`site_id` tinyint(3) unsigned NOT NULL, PRIMARY KEY	(`id`), KEY `user_id` (`user_id`)
		';
		$this->assertEquals( $this->data_good, _class('db_installer_mysql', 'classes/db/')->_db_table_struct_into_array($sql) );
*/
	}
	public function test_sql_into_array_multi_key() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
		$data_good = array(
			'fields' => array(
				'key' => array('type' => 'varchar', 'length' => '32', 'attrib' => NULL, 'not_null'	=> 1, 'default' => '', 'auto_inc' => 0),
			),
			'keys'	=> array(
				'id' => array('fields' => array('key', 'name'), 'type' => 'primary'),
			),
		);
		$sql = '
			`key` varchar(32) NOT NULL default \'\',
			`name` varchar(32) NOT NULL default \'\',
			PRIMARY KEY	(`key`,`name`),
		';
#		$this->assertEquals( $data, _class('db_installer_mysql', 'classes/db/')->_db_table_struct_into_array($sql) );
	}
	public function test_sql_into_array_foreign_key() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
// TODO
	}
	public function test_sql_into_array_partition() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
// TODO
	}
	public function test_sql_into_array_collate() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
// TODO
	}
	public function test_mysql_create_table_from_array() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
// TODO
	}
	public function test_mysql_alter_table_from_array() {
		if ($this->_need_skip_test(__FUNCTION__)) { return ; }
// TODO
	}

}