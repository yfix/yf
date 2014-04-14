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
		'name' => 
		array (
			'type' => 'varchar',
			'length' => '255',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => 'CHARACTER SET utf8',
			'auto_inc' => 0,
		),
		'active' => 
		array (
			'type' => 'enum',
			'length' => '\'1\',\'0\'',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '1',
			'auto_inc' => 0,
		),
		'locale' => 
		array (
			'type' => 'char',
			'length' => '7',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => 'en',
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
