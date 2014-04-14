<?php
$data = array (
	'fields' => 
	array (
		'id' => 
		array (
			'type' => 'char',
			'length' => '32',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'ts' => 
		array (
			'type' => 'timestamp',
			'length' => '',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
			'auto_inc' => 0,
		),
		'data' => 
		array (
			'type' => 'text',
			'length' => '',
			'attrib' => NULL,
			'not_null' => 1,
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
		'ts' => 
		array (
			'fields' => 
			array (
				0 => 'ts',
			),
			'type' => 'key',
		),
	),
);
