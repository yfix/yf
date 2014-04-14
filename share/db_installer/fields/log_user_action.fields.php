<?php
$data = array (
	'fields' => 
	array (
		'id' => 
		array (
			'type' => 'int',
			'length' => '',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 1,
		),
		'owner_id' => 
		array (
			'type' => 'int',
			'length' => '',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'action_name' => 
		array (
			'type' => 'varchar',
			'length' => '',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '255 )',
			'auto_inc' => 0,
		),
		'member_id' => 
		array (
			'type' => 'int',
			'length' => '',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'object_name' => 
		array (
			'type' => 'varchar',
			'length' => '',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '255 )',
			'auto_inc' => 0,
		),
		'object_id' => 
		array (
			'type' => 'int',
			'length' => '',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'add_date' => 
		array (
			'type' => 'int',
			'length' => '',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
	),
	'keys' => 
	array (
		' id ' => 
		array (
			'fields' => 
			array (
				0 => ' id ',
			),
			'type' => 'primary',
		),
	),
);
