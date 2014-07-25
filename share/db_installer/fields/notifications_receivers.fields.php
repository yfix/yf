<?php
$data = array (
	'fields' => 
	array (
		'notification_id' => 
		array (
			'type' => 'int',
			'length' => '11',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'receiver_id' => 
		array (
			'type' => 'int',
			'length' => '11',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'receiver_type' => 
		array (
			'type' => 'enum',
			'length' => '\'user_id\',\'admin_id\',\'user_id_tmp\'',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'is_read' => 
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
		'notification_id_receiver_id_receiver_type' => 
		array (
			'fields' => 
			array (
				0 => 'notification_id',
				1 => 'receiver_id',
				2 => 'receiver_type',
			),
			'type' => 'primary',
		),
	),
);
