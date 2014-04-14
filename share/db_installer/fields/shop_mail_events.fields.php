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
		'event_id' => 
		array (
			'type' => 'int',
			'length' => '10',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'from_email' => 
		array (
			'type' => 'varchar',
			'length' => '254',
			'attrib' => NULL,
			'not_null' => 0,
			'default' => '',
			'auto_inc' => 0,
		),
		'from_name' => 
		array (
			'type' => 'varchar',
			'length' => '100',
			'attrib' => NULL,
			'not_null' => 0,
			'default' => '',
			'auto_inc' => 0,
		),
		'subject' => 
		array (
			'type' => 'varchar',
			'length' => '78',
			'attrib' => NULL,
			'not_null' => 0,
			'default' => '',
			'auto_inc' => 0,
		),
		'html' => 
		array (
			'type' => 'text',
			'length' => ',',
			'attrib' => NULL,
			'not_null' => 0,
			'default' => '',
			'auto_inc' => 0,
		),
		'active' => 
		array (
			'type' => 'tinyint',
			'length' => '4',
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
		'event_id' => 
		array (
			'fields' => 
			array (
				0 => 'event_id',
			),
			'type' => 'key',
		),
		'event_id_active' => 
		array (
			'fields' => 
			array (
				0 => 'event_id',
				1 => 'active',
			),
			'type' => 'key',
		),
	),
);
