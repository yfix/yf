<?php
return [
	'fields' => [
		'user_id' => [
			'name' => 'user_id',
			'type' => 'bigint',
			'length' => 20,
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
		'user_type' => [
			'name' => 'user_type',
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
				'user_id_tmp' => 'user_id_tmp',
				'admin_id' => 'admin_id',
			],
		],
		'time' => [
			'name' => 'time',
			'type' => 'int',
			'length' => 11,
			'decimals' => NULL,
			'unsigned' => false,
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
				'user_id' => 'user_id',
				'user_type' => 'user_type',
			],
		],
	],
	'foreign_keys' => [
	],
	'options' => [
	],
];
