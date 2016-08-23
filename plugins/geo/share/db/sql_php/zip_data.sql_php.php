<?php
return [
	'fields' => [
		'id' => [
			'name' => 'id',
			'type' => 'int',
			'length' => 5,
			'decimals' => NULL,
			'unsigned' => true,
			'nullable' => false,
			'default' => NULL,
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => true,
			'unique' => false,
			'values' => NULL,
		],
		'lon' => [
			'name' => 'lon',
			'type' => 'float',
			'length' => NULL,
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
		'lat' => [
			'name' => 'lat',
			'type' => 'float',
			'length' => NULL,
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
				'id' => 'id',
			],
		],
		'lon' => [
			'name' => 'lon',
			'type' => 'index',
			'columns' => [
				'lon' => 'lon',
				'lat' => 'lat',
			],
		],
	],
	'foreign_keys' => [
	],
	'options' => [
	],
];
