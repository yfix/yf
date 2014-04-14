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
			'auto_inc' => 0,
		),
		'sender_id' => 
		array (
			'type' => 'int',
			'length' => '10',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'receiver_id' => 
		array (
			'type' => 'int',
			'length' => '10',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		's_section' => 
		array (
			'type' => 'enum',
			'length' => '\'sent\',\'trash\'',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => 'sent',
			'auto_inc' => 0,
		),
		'r_section' => 
		array (
			'type' => 'enum',
			'length' => '\'inbox\',\'trash\'',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => 'inbox',
			'auto_inc' => 0,
		),
		's_status' => 
		array (
			'type' => 'enum',
			'length' => '\'read\',\'unread\',\'replied\',\'sent\',\'approved\',\'disapproved\',\'deleted\'',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => 'unread',
			'auto_inc' => 0,
		),
		'r_status' => 
		array (
			'type' => 'enum',
			'length' => '\'read\',\'unread\',\'replied\',\'approved\',\'disapproved\',\'deleted\'',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => 'unread',
			'auto_inc' => 0,
		),
		'add_date' => 
		array (
			'type' => 'int',
			'length' => '10',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'subject' => 
		array (
			'type' => 'varchar',
			'length' => '255',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'message' => 
		array (
			'type' => 'text',
			'length' => '',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'type' => 
		array (
			'type' => 'varchar',
			'length' => '20',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => 'standard',
			'auto_inc' => 0,
		),
		'special_id' => 
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
		'id' => 
		array (
			'fields' => 
			array (
				0 => 'id',
			),
			'type' => 'primary',
		),
		'receiver_id' => 
		array (
			'fields' => 
			array (
				0 => 'receiver_id',
			),
			'type' => 'key',
		),
		'sender_id' => 
		array (
			'fields' => 
			array (
				0 => 'sender_id',
			),
			'type' => 'key',
		),
	),
);
