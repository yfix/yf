<?php
return [
	'fields' => [
		'notification_id' => [
			'name' => 'notification_id',
			'type' => 'int',
			'length' => 11,
			'decimals' => NULL,
			'unsigned' => false,
			'nullable' => false,
			'default' => NULL,
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => true,
			'unique' => false,
			'values' => NULL,
		],
		'receiver_id' => [
			'name' => 'receiver_id',
			'type' => 'int',
			'length' => 11,
			'decimals' => NULL,
			'unsigned' => false,
			'nullable' => false,
			'default' => NULL,
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => true,
			'unique' => false,
			'values' => NULL,
		],
		'receiver_type' => [
			'name' => 'receiver_type',
			'type' => 'enum',
			'length' => NULL,
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => NULL,
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => true,
			'unique' => false,
			'values' => [
				'user_id' => 'user_id',
				'admin_id' => 'admin_id',
				'user_id_tmp' => 'user_id_tmp',
			],
		],
		'is_read' => [
			'name' => 'is_read',
			'type' => 'tinyint',
			'length' => 4,
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
	],
	'indexes' => [
		'PRIMARY' => [
			'name' => 'PRIMARY',
			'type' => 'primary',
			'columns' => [
				'notification_id' => 'notification_id',
				'receiver_id' => 'receiver_id',
				'receiver_type' => 'receiver_type',
			],
		],
	],
	'foreign_keys' => [
	],
	'options' => [
	],
];
