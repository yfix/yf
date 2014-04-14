<?php
$data = array (
	'fields' => 
	array (
		'id' => 
		array (
			'type' => 'int',
			'length' => '5',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'lon' => 
		array (
			'type' => 'float',
			'length' => '',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'lat' => 
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
		'id' => 
		array (
			'fields' => 
			array (
				0 => 'id',
			),
			'type' => 'unique',
		),
		'lon' => 
		array (
			'fields' => 
			array (
				0 => 'lon',
				1 => 'lat',
			),
			'type' => 'key',
		),
	),
);
