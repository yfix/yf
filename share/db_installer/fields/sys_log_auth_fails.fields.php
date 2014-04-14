<?php
$data = array (
	'fields' => 
	array (
		'time' => 
		array (
			'type' => 'decimal',
			'length' => '13,3',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0.000',
			'auto_inc' => 0,
		),
		'ip' => 
		array (
			'type' => 'varchar',
			'length' => '16',
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
		'pswd' => 
		array (
			'type' => 'varchar',
			'length' => '64',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'reason' => 
		array (
			'type' => 'char',
			'length' => '1',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => 'w',
			'auto_inc' => 0,
		),
		'site_id' => 
		array (
			'type' => 'tinyint',
			'length' => '3',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'server_id' => 
		array (
			'type' => 'tinyint',
			'length' => '3',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
	),
	'keys' => 
	array (
		'time' => 
		array (
			'fields' => 
			array (
				0 => 'time',
			),
			'type' => 'primary',
		),
	),
);
