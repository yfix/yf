<?php
$data = array (
	'fields' => 
	array (
		'id' => 
		array (
			'type' => 'mediumint',
			'length' => '8',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 1,
		),
		'user_id' => 
		array (
			'type' => 'mediumint',
			'length' => '8',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'topic_id' => 
		array (
			'type' => 'int',
			'length' => '10',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'start_date' => 
		array (
			'type' => 'int',
			'length' => '10',
			'attrib' => NULL,
			'not_null' => 0,
			'default' => '0',
			'auto_inc' => 0,
		),
		'last_sent' => 
		array (
			'type' => 'int',
			'length' => '10',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'topic_track_type' => 
		array (
			'type' => 'varchar',
			'length' => '100',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => 'delayed',
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
		'topic_id' => 
		array (
			'fields' => 
			array (
				0 => 'topic_id',
			),
			'type' => 'key',
		),
	),
);
