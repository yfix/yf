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
		'group' => 
		array (
			'type' => 'tinyint',
			'length' => '3',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'name' => 
		array (
			'type' => 'varchar',
			'length' => '255',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'nick' => 
		array (
			'type' => 'varchar',
			'length' => '64',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'login' => 
		array (
			'type' => 'varchar',
			'length' => '64',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'email' => 
		array (
			'type' => 'varchar',
			'length' => '50',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'password' => 
		array (
			'type' => 'varchar',
			'length' => '32',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'phone' => 
		array (
			'type' => 'varchar',
			'length' => '40',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'city' => 
		array (
			'type' => 'varchar',
			'length' => '40',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'state' => 
		array (
			'type' => 'varchar',
			'length' => '20',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'country' => 
		array (
			'type' => 'varchar',
			'length' => '30',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => 'USA',
			'auto_inc' => 0,
		),
		'zip_code' => 
		array (
			'type' => 'varchar',
			'length' => '16',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'address' => 
		array (
			'type' => 'varchar',
			'length' => '255',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'sex' => 
		array (
			'type' => 'enum',
			'length' => '\'Female\',\'Male\'',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => 'Female',
			'auto_inc' => 0,
		),
		'age' => 
		array (
			'type' => 'smallint',
			'length' => '2',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'birth_date' => 
		array (
			'type' => 'date',
			'length' => '',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0000-00-00',
			'auto_inc' => 0,
		),
		'visits' => 
		array (
			'type' => 'smallint',
			'length' => '6',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'active' => 
		array (
			'type' => 'tinyint',
			'length' => '1',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '1',
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
		'last_update' => 
		array (
			'type' => 'int',
			'length' => '10',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'last_login' => 
		array (
			'type' => 'int',
			'length' => '10',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'num_logins' => 
		array (
			'type' => 'smallint',
			'length' => '6',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'last_view' => 
		array (
			'type' => 'int',
			'length' => '10',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'num_views' => 
		array (
			'type' => 'int',
			'length' => '10',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'verify_code' => 
		array (
			'type' => 'varchar',
			'length' => '32',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'profile_url' => 
		array (
			'type' => 'varchar',
			'length' => '64',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'admin_comments' => 
		array (
			'type' => 'text',
			'length' => '',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'ip' => 
		array (
			'type' => 'varchar',
			'length' => '15',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'photo_verified' => 
		array (
			'type' => 'enum',
			'length' => '\'0\',',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '1)',
			'auto_inc' => 0,
		),
		'avatar' => 
		array (
			'type' => 'varchar',
			'length' => '255',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'priority' => 
		array (
			'type' => 'int',
			'length' => '11',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'lon' => 
		array (
			'type' => 'decimal',
			'length' => '8,4',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0.0000',
			'auto_inc' => 0,
		),
		'lat' => 
		array (
			'type' => 'decimal',
			'length' => '8,4',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0.0000',
			'auto_inc' => 0,
		),
		'has_avatar' => 
		array (
			'type' => 'enum',
			'length' => '\'0\',\'1\'',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'contact_by_email' => 
		array (
			'type' => 'enum',
			'length' => '\'1\',\'0\'',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'emails' => 
		array (
			'type' => 'smallint',
			'length' => '6',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'emailssent' => 
		array (
			'type' => 'smallint',
			'length' => '6',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'go_after_login' => 
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
		'login' => 
		array (
			'fields' => 
			array (
				0 => 'login',
			),
			'type' => 'key',
		),
		'nick' => 
		array (
			'fields' => 
			array (
				0 => 'nick',
			),
			'type' => 'key',
		),
		'active' => 
		array (
			'fields' => 
			array (
				0 => 'active',
			),
			'type' => 'key',
		),
		'group' => 
		array (
			'fields' => 
			array (
				0 => 'group',
			),
			'type' => 'key',
		),
		'email' => 
		array (
			'fields' => 
			array (
				0 => 'email',
			),
			'type' => 'key',
		),
		'has_avatar' => 
		array (
			'fields' => 
			array (
				0 => 'has_avatar',
			),
			'type' => 'key',
		),
		'priority' => 
		array (
			'fields' => 
			array (
				0 => 'priority',
			),
			'type' => 'key',
		),
		'country' => 
		array (
			'fields' => 
			array (
				0 => 'country',
			),
			'type' => 'key',
		),
	),
);
