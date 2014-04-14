<?php
$data = array (
	'fields' => 
	array (
		'country' => 
		array (
			'type' => 'char',
			'length' => '2',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'code' => 
		array (
			'type' => 'char',
			'length' => '2',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'name' => 
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
		'country' => 
		array (
			'fields' => 
			array (
				0 => 'country',
			),
			'type' => 'key',
		),
		'code' => 
		array (
			'fields' => 
			array (
				0 => 'code',
			),
			'type' => 'key',
		),
	),
);
