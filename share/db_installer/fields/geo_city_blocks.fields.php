<?php
$data = array (
	'fields' => 
	array (
		'start_ip' => 
		array (
			'type' => 'int',
			'length' => '8',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'end_ip' => 
		array (
			'type' => 'int',
			'length' => '8',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'loc_id' => 
		array (
			'type' => 'int',
			'length' => '6',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
	),
	'keys' => 
	array (
		'end_ip' => 
		array (
			'fields' => 
			array (
				0 => 'end_ip',
			),
			'type' => 'primary',
		),
	),
);
