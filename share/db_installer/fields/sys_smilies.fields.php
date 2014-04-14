<?php
$data = array (
	'fields' => 
	array (
		'id' => 
		array (
			'type' => 'smallint',
			'length' => '5',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 1,
		),
		'code' => 
		array (
			'type' => 'varchar',
			'length' => '50',
			'attrib' => NULL,
			'not_null' => 0,
			'default' => '',
			'auto_inc' => 0,
		),
		'url' => 
		array (
			'type' => 'varchar',
			'length' => '100',
			'attrib' => NULL,
			'not_null' => 0,
			'default' => '',
			'auto_inc' => 0,
		),
		'emoticon' => 
		array (
			'type' => 'varchar',
			'length' => '75',
			'attrib' => NULL,
			'not_null' => 0,
			'default' => '',
			'auto_inc' => 0,
		),
		'emo_set' => 
		array (
			'type' => 'tinyint',
			'length' => '3',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '1',
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
