<?php
$data = array (
	'fields' => 
	array (
		'c' => 
		array (
			'type' => 'char',
			'length' => '2',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'n' => 
		array (
			'type' => 'varchar',
			'length' => '64',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'f' => 
		array (
			'type' => 'enum',
			'length' => '\'0\',\'1\'',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'cont' => 
		array (
			'type' => 'char',
			'length' => '1',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'call_code' => 
		array (
			'type' => 'char',
			'length' => '4',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'/**' => 
		array (
			'type' => 'engine',
			'length' => '=InnoDB',
			'attrib' => NULL,
			'not_null' => 0,
			'default' => 'CHARSET=utf8 **/',
			'auto_inc' => 0,
		),
	),
	'keys' => 
	array (
		'c' => 
		array (
			'fields' => 
			array (
				0 => 'c',
			),
			'type' => 'primary',
		),
	),
);
