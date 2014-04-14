<?php
$data = array (
	'fields' => 
	array (
		'loc_id' => 
		array (
			'type' => 'int',
			'length' => '10',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'country' => 
		array (
			'type' => 'char',
			'length' => '2',
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
		'city' => 
		array (
			'type' => 'varchar',
			'length' => '32',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'postal_code' => 
		array (
			'type' => 'char',
			'length' => '5',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
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
		'dma_code' => 
		array (
			'type' => 'int',
			'length' => '8',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'area_code' => 
		array (
			'type' => 'int',
			'length' => '8',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
	),
	'keys' => 
	array (
		'loc_id' => 
		array (
			'fields' => 
			array (
				0 => 'loc_id',
			),
			'type' => 'primary',
		),
		'country' => 
		array (
			'fields' => 
			array (
				0 => 'country',
			),
			'type' => 'key',
		),
	),
);
