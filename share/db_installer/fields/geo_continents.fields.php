<?php
$data = array (
	'fields' => 
	array (
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
			'length' => '20',
			'attrib' => NULL,
			'not_null' => 0,
			'default' => '',
			'auto_inc' => 0,
		),
		'geoname_id' => 
		array (
			'type' => 'int',
			'length' => '11',
			'attrib' => NULL,
			'not_null' => 0,
			'default' => '0',
			'auto_inc' => 0,
		),
	),
	'keys' => 
	array (
		'code' => 
		array (
			'fields' => 
			array (
				0 => 'code',
			),
			'type' => 'primary',
		),
	),
);
