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
		'object_name' => 
		array (
			'type' => 'varchar',
			'length' => '64',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'object_id' => 
		array (
			'type' => 'varchar',
			'length' => '64',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'old_text' => 
		array (
			'type' => 'text',
			'length' => '',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'new_text' => 
		array (
			'type' => 'text',
			'length' => '',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'date' => 
		array (
			'type' => 'int',
			'length' => '10',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
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
		'site_id' => 
		array (
			'type' => 'tinyint',
			'length' => '3',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'server_id' => 
		array (
			'type' => 'tinyint',
			'length' => '3',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'ip' => 
		array (
			'type' => 'char',
			'length' => '15',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'comment' => 
		array (
			'type' => 'varchar',
			'length' => '255',
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
	),
);
