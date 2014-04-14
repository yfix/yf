<?php
$data = array (
	'fields' => 
	array (
		'id' => 
		array (
			'type' => 'tinyint',
			'length' => '4',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'height' => 
		array (
			'type' => 'varchar',
			'length' => '50',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'UNIQUE' => 
		array (
			'type' => 'key',
			'length' => '',
			'attrib' => NULL,
			'not_null' => 0,
			'default' => '`id_2` (`id`)',
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
			'type' => 'key',
		),
	),
);
