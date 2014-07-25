<?php
$data = array (
	'fields' => 
	array (
		'user_id' => 
		array (
			'type' => 'bigint',
			'length' => '20',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'user_type' => 
		array (
			'type' => 'enum',
			'length' => '\'user_id\',\'user_id_tmp\',\'admin_id\'',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'time' => 
		array (
			'type' => 'int',
			'length' => '11',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
	),
	'keys' => 
	array (
		'user_id_user_type' => 
		array (
			'fields' => 
			array (
				0 => 'user_id',
				1 => 'user_type',
			),
			'type' => 'primary',
		),
	),
);
