<?php
return [
	'fields' => [
		'id' => [
			'name' => 'id',
			'type' => 'int',
			'length' => 10,
			'decimals' => NULL,
			'unsigned' => true,
			'nullable' => false,
			'default' => NULL,
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => true,
			'primary' => true,
			'unique' => false,
			'values' => NULL,
		],
		'group' => [
			'name' => 'group',
			'type' => 'tinyint',
			'length' => 3,
			'decimals' => NULL,
			'unsigned' => true,
			'nullable' => false,
			'default' => '0',
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		],
		'name' => [
			'name' => 'name',
			'type' => 'varchar',
			'length' => 255,
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => '',
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		],
		'nick' => [
			'name' => 'nick',
			'type' => 'varchar',
			'length' => 64,
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => '',
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		],
		'login' => [
			'name' => 'login',
			'type' => 'varchar',
			'length' => 64,
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => '',
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		],
		'email' => [
			'name' => 'email',
			'type' => 'varchar',
			'length' => 50,
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => '',
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		],
		'password' => [
			'name' => 'password',
			'type' => 'varchar',
			'length' => 32,
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => '',
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		],
		'phone' => [
			'name' => 'phone',
			'type' => 'varchar',
			'length' => 40,
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => '',
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		],
		'city' => [
			'name' => 'city',
			'type' => 'varchar',
			'length' => 40,
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => '',
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		],
		'state' => [
			'name' => 'state',
			'type' => 'varchar',
			'length' => 20,
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => '',
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		],
		'country' => [
			'name' => 'country',
			'type' => 'varchar',
			'length' => 30,
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => 'USA',
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		],
		'zip_code' => [
			'name' => 'zip_code',
			'type' => 'varchar',
			'length' => 16,
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => '',
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		],
		'address' => [
			'name' => 'address',
			'type' => 'varchar',
			'length' => 255,
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => '',
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		],
		'sex' => [
			'name' => 'sex',
			'type' => 'enum',
			'length' => NULL,
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => 'Female',
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => [
				'Female' => 'Female',
				'Male' => 'Male',
			],
		],
		'age' => [
			'name' => 'age',
			'type' => 'smallint',
			'length' => 2,
			'decimals' => NULL,
			'unsigned' => true,
			'nullable' => false,
			'default' => '0',
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		],
		'birth_date' => [
			'name' => 'birth_date',
			'type' => 'date',
			'length' => NULL,
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => '0000-00-00',
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		],
		'visits' => [
			'name' => 'visits',
			'type' => 'smallint',
			'length' => 6,
			'decimals' => NULL,
			'unsigned' => true,
			'nullable' => false,
			'default' => '0',
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		],
		'active' => [
			'name' => 'active',
			'type' => 'tinyint',
			'length' => 1,
			'decimals' => NULL,
			'unsigned' => true,
			'nullable' => false,
			'default' => '1',
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		],
		'add_date' => [
			'name' => 'add_date',
			'type' => 'int',
			'length' => 10,
			'decimals' => NULL,
			'unsigned' => true,
			'nullable' => false,
			'default' => '0',
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		],
		'last_update' => [
			'name' => 'last_update',
			'type' => 'int',
			'length' => 10,
			'decimals' => NULL,
			'unsigned' => true,
			'nullable' => false,
			'default' => '0',
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		],
		'last_login' => [
			'name' => 'last_login',
			'type' => 'int',
			'length' => 10,
			'decimals' => NULL,
			'unsigned' => true,
			'nullable' => false,
			'default' => '0',
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		],
		'num_logins' => [
			'name' => 'num_logins',
			'type' => 'smallint',
			'length' => 6,
			'decimals' => NULL,
			'unsigned' => true,
			'nullable' => false,
			'default' => '0',
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		],
		'last_view' => [
			'name' => 'last_view',
			'type' => 'int',
			'length' => 10,
			'decimals' => NULL,
			'unsigned' => true,
			'nullable' => false,
			'default' => '0',
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		],
		'num_views' => [
			'name' => 'num_views',
			'type' => 'int',
			'length' => 10,
			'decimals' => NULL,
			'unsigned' => true,
			'nullable' => false,
			'default' => '0',
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		],
		'verify_code' => [
			'name' => 'verify_code',
			'type' => 'varchar',
			'length' => 32,
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => '',
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		],
		'profile_url' => [
			'name' => 'profile_url',
			'type' => 'varchar',
			'length' => 64,
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => NULL,
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		],
		'admin_comments' => [
			'name' => 'admin_comments',
			'type' => 'text',
			'length' => NULL,
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => NULL,
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		],
		'ip' => [
			'name' => 'ip',
			'type' => 'varchar',
			'length' => 15,
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => '',
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		],
		'photo_verified' => [
			'name' => 'photo_verified',
			'type' => 'enum',
			'length' => NULL,
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => NULL,
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => [
				0 => '0',
				1 => '1',
			],
		],
		'avatar' => [
			'name' => 'avatar',
			'type' => 'varchar',
			'length' => 255,
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => '',
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		],
		'priority' => [
			'name' => 'priority',
			'type' => 'int',
			'length' => 11,
			'decimals' => NULL,
			'unsigned' => false,
			'nullable' => false,
			'default' => '0',
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		],
		'lon' => [
			'name' => 'lon',
			'type' => 'decimal',
			'length' => 8,
			'decimals' => '4',
			'unsigned' => false,
			'nullable' => false,
			'default' => '0.0000',
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		],
		'lat' => [
			'name' => 'lat',
			'type' => 'decimal',
			'length' => 8,
			'decimals' => '4',
			'unsigned' => false,
			'nullable' => false,
			'default' => '0.0000',
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		],
		'has_avatar' => [
			'name' => 'has_avatar',
			'type' => 'enum',
			'length' => NULL,
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => '0',
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => [
				0 => '0',
				1 => '1',
			],
		],
		'contact_by_email' => [
			'name' => 'contact_by_email',
			'type' => 'enum',
			'length' => NULL,
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => NULL,
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => [
				1 => '1',
				0 => '0',
			],
		],
		'emails' => [
			'name' => 'emails',
			'type' => 'smallint',
			'length' => 6,
			'decimals' => NULL,
			'unsigned' => true,
			'nullable' => false,
			'default' => '0',
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		],
		'emailssent' => [
			'name' => 'emailssent',
			'type' => 'smallint',
			'length' => 6,
			'decimals' => NULL,
			'unsigned' => true,
			'nullable' => false,
			'default' => '0',
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		],
		'go_after_login' => [
			'name' => 'go_after_login',
			'type' => 'varchar',
			'length' => 255,
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => '',
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		],
	],
	'indexes' => [
		'PRIMARY' => [
			'name' => 'PRIMARY',
			'type' => 'primary',
			'columns' => [
				'id' => 'id',
			],
		],
		'login' => [
			'name' => 'login',
			'type' => 'index',
			'columns' => [
				'login' => 'login',
			],
		],
		'nick' => [
			'name' => 'nick',
			'type' => 'index',
			'columns' => [
				'nick' => 'nick',
			],
		],
		'active' => [
			'name' => 'active',
			'type' => 'index',
			'columns' => [
				'active' => 'active',
			],
		],
		'group' => [
			'name' => 'group',
			'type' => 'index',
			'columns' => [
				'group' => 'group',
			],
		],
		'email' => [
			'name' => 'email',
			'type' => 'index',
			'columns' => [
				'email' => 'email',
			],
		],
		'has_avatar' => [
			'name' => 'has_avatar',
			'type' => 'index',
			'columns' => [
				'has_avatar' => 'has_avatar',
			],
		],
		'priority' => [
			'name' => 'priority',
			'type' => 'index',
			'columns' => [
				'priority' => 'priority',
			],
		],
		'country' => [
			'name' => 'country',
			'type' => 'index',
			'columns' => [
				'country' => 'country',
			],
		],
	],
	'foreign_keys' => [
	],
	'options' => [
		'engine' => 'InnoDB',
		'charset' => 'utf8',
	],
];
