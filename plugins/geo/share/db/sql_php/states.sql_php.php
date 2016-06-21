<?php
return [
	'fields' => [
		'id' => [
			'name' => 'id',
			'type' => 'int',
			'length' => 11,
			'decimals' => NULL,
			'unsigned' => false,
			'nullable' => false,
			'default' => NULL,
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => true,
			'primary' => true,
			'unique' => false,
			'values' => NULL,
		],
		'name' => [
			'name' => 'name',
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
		'code' => [
			'name' => 'code',
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
			'unique' => true,
			'values' => NULL,
		],
		'country_code' => [
			'name' => 'country_code',
			'type' => 'char',
			'length' => 2,
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
		'code' => [
			'name' => 'code',
			'type' => 'unique',
			'columns' => [
				'code' => 'code',
			],
		],
		'state' => [
			'name' => 'state',
			'type' => 'index',
			'columns' => [
				'name' => 'name',
			],
		],
		'country_code' => [
			'name' => 'country_code',
			'type' => 'index',
			'columns' => [
				'country_code' => 'country_code',
			],
		],
	],
	'foreign_keys' => [
	],
	'options' => [
	],
];
