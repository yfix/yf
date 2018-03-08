<?php
return [
	'fields' => [
		'id' => [
			'name' => 'id',
			'type' => 'int',
			'length' => 6,
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
			'length' => 64,
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
		'active' => [
			'name' => 'active',
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
	],
	'indexes' => [
		'PRIMARY' => [
			'name' => 'PRIMARY',
			'type' => 'primary',
			'columns' => [
				'id' => 'id',
			],
		],
		'name' => [
			'name' => 'name',
			'type' => 'unique',
			'columns' => [
				'name' => 'name',
			],
		],
	],
	'foreign_keys' => [
	],
	'options' => [
	],
];
