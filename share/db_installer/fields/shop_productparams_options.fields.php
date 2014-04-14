<?php
$data = array (
	'fields' => 
	array (
		'id' => 
		array (
			'type' => 'bigint',
			'length' => '20',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 1,
		),
		'productparams_id' => 
		array (
			'type' => 'int',
			'length' => '11',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'title' => 
		array (
			'type' => 'varchar',
			'length' => '100',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'sort' => 
		array (
			'type' => 'varchar',
			'length' => '10',
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
		'productparams_id' => 
		array (
			'fields' => 
			array (
				0 => 'productparams_id',
			),
			'type' => 'key',
		),
	),
);
