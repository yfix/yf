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
		'user_id' => [
			'name' => 'user_id',
			'type' => 'int',
			'length' => 11,
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
		'message' => [
			'name' => 'message',
			'type' => 'text',
			'length' => NULL,
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
		'object' => [
			'name' => 'object',
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
		'action' => [
			'name' => 'action',
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
		'object_id' => [
			'name' => 'object_id',
			'type' => 'int',
			'length' => 11,
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
		'server_id' => [
			'name' => 'server_id',
			'type' => 'int',
			'length' => 11,
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
		'site_id' => [
			'name' => 'site_id',
			'type' => 'int',
			'length' => 11,
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
		'important' => [
			'name' => 'important',
			'type' => 'int',
			'length' => 11,
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
		'old_data' => [
			'name' => 'old_data',
			'type' => 'text',
			'length' => NULL,
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
		'new_data' => [
			'name' => 'new_data',
			'type' => 'text',
			'length' => NULL,
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
		'add_date' => [
			'name' => 'add_date',
			'type' => 'datetime',
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
		'read' => [
			'name' => 'read',
			'type' => 'tinyint',
			'length' => 1,
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
		'type' => [
			'name' => 'type',
			'type' => 'varchar',
			'length' => 100,
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
	],
	'indexes' => [
		'PRIMARY' => [
			'name' => 'PRIMARY',
			'type' => 'primary',
			'columns' => [
				'id' => 'id',
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