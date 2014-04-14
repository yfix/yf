<?php
$data = array (
	'fields' => 
	array (
		'ip' => 
		array (
			'type' => 'int',
			'length' => '10',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'hits' => 
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
