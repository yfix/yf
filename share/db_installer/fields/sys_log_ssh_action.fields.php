<?php
$data = array (
	'fields' => 
	array (
		'microtime' => 
		array (
			'type' => 'decimal',
			'length' => '13,3',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0.000',
			'auto_inc' => 0,
		),
		'server_id' => 
		array (
			'type' => 'varchar',
			'length' => '64',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'init_type' => 
		array (
			'type' => 'enum',
			'length' => '\'user\',\'admin\'',
			'attrib' => NULL,
			'not_null' => 0,
			'default' => 'user',
			'auto_inc' => 0,
		),
		'action' => 
		array (
			'type' => 'varchar',
			'length' => '32',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'comment' => 
		array (
			'type' => 'varchar',
			'length' => '255',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'get_object' => 
		array (
			'type' => 'varchar',
			'length' => '32',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'get_action' => 
		array (
			'type' => 'varchar',
			'length' => '32',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'user_id' => 
		array (
			'type' => 'int',
			'length' => '11',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'user_group' => 
		array (
			'type' => 'tinyint',
			'length' => '2',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'ip' => 
		array (
			'type' => 'varchar',
			'length' => '32',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
	),
	'keys' => 
	array (
		'microtime' => 
		array (
			'fields' => 
			array (
				0 => 'microtime',
			),
			'type' => 'key',
		),
		'server_id' => 
		array (
			'fields' => 
			array (
				0 => 'server_id',
			),
			'type' => 'key',
		),
	),
);
