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
		'title' => 
		array (
			'type' => 'varchar',
			'length' => '255',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'file' => 
		array (
			'type' => 'varchar',
			'length' => '255',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'php_code' => 
		array (
			'type' => 'text',
			'length' => '',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'next_run' => 
		array (
			'type' => 'int',
			'length' => '10',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'week_day' => 
		array (
			'type' => 'tinyint',
			'length' => '1',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '-1',
			'auto_inc' => 0,
		),
		'month_day' => 
		array (
			'type' => 'smallint',
			'length' => '2',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '-1',
			'auto_inc' => 0,
		),
		'hour' => 
		array (
			'type' => 'smallint',
			'length' => '2',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '-1',
			'auto_inc' => 0,
		),
		'minute' => 
		array (
			'type' => 'smallint',
			'length' => '2',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '-1',
			'auto_inc' => 0,
		),
		'cronkey' => 
		array (
			'type' => 'varchar',
			'length' => '32',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'log' => 
		array (
			'type' => 'tinyint',
			'length' => '1',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'description' => 
		array (
			'type' => 'text',
			'length' => '',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'enabled' => 
		array (
			'type' => 'tinyint',
			'length' => '1',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '1',
			'auto_inc' => 0,
		),
		'key' => 
		array (
			'type' => 'varchar',
			'length' => '30',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'safemode' => 
		array (
			'type' => 'tinyint',
			'length' => '1',
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
		'task_next_run' => 
		array (
			'fields' => 
			array (
				0 => 'next_run',
			),
			'type' => 'key',
		),
	),
);
