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
			'default' => '',
			'auto_inc' => 0,
		),
		'user_id' => 
		array (
			'type' => 'int',
			'length' => '10',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'user_group' => 
		array (
			'type' => 'int',
			'length' => '10',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'start_time' => 
		array (
			'type' => 'int',
			'length' => '10',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'last_time' => 
		array (
			'type' => 'int',
			'length' => '10',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'type' => 
		array (
			'type' => 'enum',
			'length' => '\'user\',\'admin\'',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'host_name' => 
		array (
			'type' => 'varchar',
			'length' => '128',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'data' => 
		array (
			'type' => 'longtext',
			'length' => ',',
			'attrib' => NULL,
			'not_null' => 0,
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
		'user_id' => 
		array (
			'fields' => 
			array (
				0 => 'user_id',
			),
			'type' => 'key',
		),
		'start_time' => 
		array (
			'fields' => 
			array (
				0 => 'start_time',
			),
			'type' => 'key',
		),
	),
);
