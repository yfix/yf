<?php
$data = array (
	'fields' => 
	array (
		'keyword' => 
		array (
			'type' => 'varchar',
			'length' => '32',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'users' => 
		array (
			'type' => 'int',
			'length' => '10',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
	),
	'keys' => 
	array (
		'keyword' => 
		array (
			'fields' => 
			array (
				0 => 'keyword',
			),
			'type' => 'primary',
		),
	),
);
