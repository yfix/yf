<?php
$data = array (
	'fields' => 
	array (
		'id' => 
		array (
			'type' => 'varchar',
			'length' => '32',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'user_id' => 
		array (
			'type' => 'mediumint',
			'length' => '8',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'user_name' => 
		array (
			'type' => 'varchar',
			'length' => '64',
			'attrib' => NULL,
			'not_null' => 0,
			'default' => '',
			'auto_inc' => 0,
		),
		'user_group' => 
		array (
			'type' => 'smallint',
			'length' => '3',
			'attrib' => NULL,
			'not_null' => 0,
			'default' => '0',
			'auto_inc' => 0,
		),
		'ip_address' => 
		array (
			'type' => 'varchar',
			'length' => '16',
			'attrib' => NULL,
			'not_null' => 0,
			'default' => '',
			'auto_inc' => 0,
		),
		'user_agent' => 
		array (
			'type' => 'varchar',
			'length' => '64',
			'attrib' => NULL,
			'not_null' => 0,
			'default' => '',
			'auto_inc' => 0,
		),
		'login_date' => 
		array (
			'type' => 'int',
			'length' => '10',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'last_update' => 
		array (
			'type' => 'int',
			'length' => '10',
			'attrib' => NULL,
			'not_null' => 0,
			'default' => '0',
			'auto_inc' => 0,
		),
		'login_type' => 
		array (
			'type' => 'tinyint',
			'length' => '1',
			'attrib' => NULL,
			'not_null' => 0,
			'default' => '0',
			'auto_inc' => 0,
		),
		'location' => 
		array (
			'type' => 'varchar',
			'length' => '40',
			'attrib' => NULL,
			'not_null' => 0,
			'default' => '',
			'auto_inc' => 0,
		),
		'in_forum' => 
		array (
			'type' => 'smallint',
			'length' => '5',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'in_topic' => 
		array (
			'type' => 'int',
			'length' => '10',
			'attrib' => NULL,
			'not_null' => 0,
			'default' => '0',
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
		'in_topic' => 
		array (
			'fields' => 
			array (
				0 => 'in_topic',
			),
			'type' => 'key',
		),
		'in_forum' => 
		array (
			'fields' => 
			array (
				0 => 'in_forum',
			),
			'type' => 'key',
		),
	),
);
