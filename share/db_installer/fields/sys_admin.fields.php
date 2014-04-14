<?php
$data = array (
	'fields' => 
	array (
		'id' => 
		array (
			'type' => 'int',
			'length' => '10',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 1,
		),
		'first_name' => 
		array (
			'type' => 'varchar',
			'length' => '64',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'last_name' => 
		array (
			'type' => 'varchar',
			'length' => '64',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'login' => 
		array (
			'type' => 'varchar',
			'length' => '64',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'password' => 
		array (
			'type' => 'varchar',
			'length' => '64',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'group' => 
		array (
			'type' => 'int',
			'length' => '10',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'active' => 
		array (
			'type' => 'enum',
			'length' => '\'0\',\'1\'',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'add_date' => 
		array (
			'type' => 'int',
			'length' => '10',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'last_login' => 
		array (
			'type' => 'int',
			'length' => '10',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'num_logins' => 
		array (
			'type' => 'smallint',
			'length' => '6',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'go_after_login' => 
		array (
			'type' => 'varchar',
			'length' => '255',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'email' => 
		array (
			'type' => 'varchar',
			'length' => '255',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
	),
	'keys' => 
	array (
		'id' => 
		array (
			'fields' => 
			array (
				0 => 'id',
			),
			'type' => 'primary',
		),
		'login' => 
		array (
			'fields' => 
			array (
				0 => 'login',
			),
			'type' => 'unique',
		),
	),
);
