<?php
$data = array (
	'fields' => 
	array (
		'ip' => 
		array (
			'type' => 'varchar',
			'length' => '20',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'admin_id' => 
		array (
			'type' => 'int',
			'length' => '10',
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
		'ban_type' => 
		array (
			'type' => 'varchar',
			'length' => '16',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
	),
	'keys' => 
	array (
		'ip' => 
		array (
			'fields' => 
			array (
				0 => 'ip',
			),
			'type' => 'primary',
		),
	),
);
