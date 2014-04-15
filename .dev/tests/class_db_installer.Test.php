<?php

require dirname(__FILE__).'/yf_unit_tests_setup.php';
require dirname(__FILE__).'/db_setup.php';

class class_db_installer_test extends PHPUnit_Framework_TestCase {
	private $data_good = array(
		'fields' => array(
			'id' => array(
				'type'		=> 'varchar',
				'length'	=> '32',
				'attrib'	=> NULL,
				'not_null'	=> 1,
				'default'	=> '',
				'auto_inc'	=> 0,
			),
			'user_id' => array(
				'type'		=> 'int',
				'length'	=> '10',
				'attrib'	=> NULL,
				'not_null'	=> 1,
				'default'	=> '0',
				'auto_inc'	=> 0,
			),
			'user_group' => array(
				'type'		=> 'int',
				'length'	=> '10',
				'attrib'	=> NULL,
				'not_null'	=> 1,
				'default'	=> '0',
				'auto_inc'	=> 0,
			),
			'time' => array(
				'type'		=> 'int',
				'length'	=> '10',
				'attrib'	=> NULL,
				'not_null'	=> 1,
				'default'	=> '0',
				'auto_inc'	=> 0,
			),
			'type' => array(
				'type'		=> 'enum',
				'length'	=> '\'user\',\'admin\'',
				'attrib'	=> NULL,
				'not_null'	=> 1,
				'default'	=> '',
				'auto_inc'	=> 0,
			),
			'ip' => array(
				'type'		=> 'varchar',
				'length'	=> '16',
				'attrib'	=> NULL,
				'not_null'	=> 1,
				'default'	=> '',
				'auto_inc'	=> 0,
			),
			'user_agent' => array(
				'type'		=> 'varchar',
				'length'	=> '255',
				'attrib'	=> NULL,
				'not_null'	=> 1,
				'default'	=> '',
				'auto_inc'	=> 0,
			),
			'query_string' => array(
				'type'		=> 'varchar',
				'length'	=> '255',
				'attrib'	=> NULL,
				'not_null'	=> 1,
				'default'	=> '',
				'auto_inc'	=> 0,
			),
			'site_id' => array(
				'type'		=> 'tinyint',
				'length'	=> '3',
				'attrib'	=> NULL,
				'not_null'	=> 1,
				'default'	=> '0',
				'auto_inc'	=> 0,
			),
		),
		'keys'	=> array(
			'id' => array(
				'fields'=> array('id'),
				'type'	=> 'primary',
			),
			'user_id' => array(
				'fields'=> array('user_id'),
				'type'	=> 'key',
			),
		),
	);

	public function test_sql_into_array_empty() {
		$this->assertEquals( array(), _class('installer_db_mysql', 'classes/db/')->_db_table_struct_into_array('') );
	}
	public function test_sql_into_array() {
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
			/** ENGINE=MEMORY **/
		';
		$this->assertEquals( $this->data_good, _class('installer_db_mysql', 'classes/db/')->_db_table_struct_into_array($sql) );

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
			/** ENGINE=MEMORY **/
		';

		$this->assertEquals( $this->data_good, _class('installer_db_mysql', 'classes/db/')->_db_table_struct_into_array($sql) );
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
		$this->assertEquals( $this->data_good, _class('installer_db_mysql', 'classes/db/')->_db_table_struct_into_array($sql) );
/*
		$sql = '`id` varchar(32) NOT NULL default \'\', `user_id` int(10) unsigned NOT NULL, `user_group` int(10) unsigned NOT NULL, `time` int(10) unsigned NOT NULL default \'0\',
			`type` enum(\'user\',\'admin\') NOT NULL, `ip` varchar(16) NOT NULL, `user_agent` varchar(255) NOT NULL, `query_string` varchar(255) NOT NULL,
			`site_id` tinyint(3) unsigned NOT NULL, PRIMARY KEY	(`id`), KEY `user_id` (`user_id`)
		';
		$this->assertEquals( $this->data_good, _class('installer_db_mysql', 'classes/db/')->_db_table_struct_into_array($sql) );
*/
	}
	public function test_sql_into_array_multi_key() {
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
#		$this->assertEquals( $data, _class('installer_db_mysql', 'classes/db/')->_db_table_struct_into_array($sql) );
	}
	public function test_sql_into_array_foreign_key() {
// TODO
	}
	public function test_sql_into_array_partition() {
// TODO
	}
	public function test_sql_into_array_collate() {
// TODO
	}
	public function test_mysql_create_table_from_array() {
// TODO
	}
	public function test_mysql_alter_table_from_array() {
// TODO
	}
}