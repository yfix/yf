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
		'city' => 
		array (
			'type' => 'varchar',
			'length' => '32',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'region' => 
		array (
			'type' => 'char',
			'length' => '3',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'population' => 
		array (
			'type' => 'int',
			'length' => '8',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'latitude' => 
		array (
			'type' => 'float',
			'length' => '',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'longitude' => 
		array (
			'type' => 'float',
			'length' => '',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
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
		'population' => 
		array (
			'fields' => 
			array (
				0 => 'population',
			),
			'type' => 'key',
		),
	),
);
