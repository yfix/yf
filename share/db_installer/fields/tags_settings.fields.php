<?php
$data = array (
	'fields' => 
	array (
		'user_id' => 
		array (
			'type' => 'int',
			'length' => '',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'allowed_group' => 
		array (
			'type' => 'tinyint',
			'length' => '',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
	),
	'keys' => 
	array (
		' user_id ' => 
		array (
			'fields' => 
			array (
				0 => ' user_id ',
			),
			'type' => 'primary',
		),
	),
);
