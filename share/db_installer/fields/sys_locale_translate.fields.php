<?php
$data = array (
	'fields' => 
	array (
		'var_id' => 
		array (
			'type' => 'int',
			'length' => '10',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'value' => 
		array (
			'type' => 'text',
			'length' => '',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'locale' => 
		array (
			'type' => 'varchar',
			'length' => '12',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
	),
	'keys' => 
	array (
		'lang' => 
		array (
			'fields' => 
			array (
				0 => 'locale',
			),
			'type' => 'key',
		),
		'var_id' => 
		array (
			'fields' => 
			array (
				0 => 'var_id',
			),
			'type' => 'key',
		),
	),
);
