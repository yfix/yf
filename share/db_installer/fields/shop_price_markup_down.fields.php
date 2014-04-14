<?php
$data = array (
	'fields' => 
	array (
		'id' => 
		array (
			'type' => 'int',
			'length' => '11',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 1,
		),
		'active' => 
		array (
			'type' => 'tinyint',
			'length' => '4',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'type' => 
		array (
			'type' => 'tinyint',
			'length' => '4',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'value' => 
		array (
			'type' => 'decimal',
			'length' => '10,2',
			'attrib' => NULL,
			'not_null' => 0,
			'default' => '',
			'auto_inc' => 0,
		),
		'description' => 
		array (
			'type' => 'varchar',
			'length' => '255',
			'attrib' => NULL,
			'not_null' => 0,
			'default' => '',
			'auto_inc' => 0,
		),
		'time_from' => 
		array (
			'type' => 'datetime',
			'length' => '',
			'attrib' => NULL,
			'not_null' => 0,
			'default' => '',
			'auto_inc' => 0,
		),
		'time_to' => 
		array (
			'type' => 'datetime',
			'length' => '',
			'attrib' => NULL,
			'not_null' => 0,
			'default' => '',
			'auto_inc' => 0,
		),
		'conditions' => 
		array (
			'type' => 'text',
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
		'active' => 
		array (
			'fields' => 
			array (
				0 => 'active',
			),
			'type' => 'key',
		),
	),
);
