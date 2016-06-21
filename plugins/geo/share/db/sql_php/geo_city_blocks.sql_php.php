<?php
return [
	'fields' => [
		'start_ip' => [
			'name' => 'start_ip',
			'type' => 'int',
			'length' => 8,
			'decimals' => NULL,
			'unsigned' => true,
			'nullable' => false,
			'default' => NULL,
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		],
		'end_ip' => [
			'name' => 'end_ip',
			'type' => 'int',
			'length' => 8,
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
		'loc_id' => [
			'name' => 'loc_id',
			'type' => 'int',
			'length' => 6,
			'decimals' => NULL,
			'unsigned' => true,
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
				'end_ip' => 'end_ip',
			],
		],
	],
	'foreign_keys' => [
	],
	'options' => [
	],
];
