<?php
$data = array (
	'fields' => 
	array (
		'id' => 
		array (
			'type' => 'int',
			'length' => '10',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 1,
		),
		'user_id' => 
		array (
			'type' => 'int',
			'length' => '10',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'old_mail' => 
		array (
			'type' => 'varchar',
			'length' => '50',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'new_mail' => 
		array (
			'type' => 'varchar',
			'length' => '50',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'time' => 
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
		'id' => 
		array (
			'fields' => 
			array (
				0 => 'id',
			),
			'type' => 'primary',
		),
	),
);
