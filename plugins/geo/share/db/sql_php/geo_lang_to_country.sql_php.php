<?php
return [
	'fields' => [
		'lang' => [
			'name' => 'lang',
			'type' => 'char',
			'length' => 2,
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => NULL,
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => true,
			'unique' => false,
			'values' => NULL,
		],
		'country' => [
			'name' => 'country',
			'type' => 'char',
			'length' => 2,
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => NULL,
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => true,
			'unique' => false,
			'values' => NULL,
		],
	],
	'indexes' => [
		'PRIMARY' => [
			'name' => 'PRIMARY',
			'type' => 'primary',
			'columns' => [
				'lang' => 'lang',
				'country' => 'country',
			],
		],
	],
	'foreign_keys' => [
	],
	'options' => [
	],
];
