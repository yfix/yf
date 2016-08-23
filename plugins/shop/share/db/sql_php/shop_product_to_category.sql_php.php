<?php
return [
	'fields' => [
		'product_id' => [
			'name' => 'product_id',
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
		'category_id' => [
			'name' => 'category_id',
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
	],
	'indexes' => [
		'PRIMARY' => [
			'name' => 'PRIMARY',
			'type' => 'primary',
			'columns' => [
				'product_id' => 'product_id',
				'category_id' => 'category_id',
			],
		],
		'product_id' => [
			'name' => 'product_id',
			'type' => 'index',
			'columns' => [
				'product_id' => 'product_id',
			],
		],
		'category_id' => [
			'name' => 'category_id',
			'type' => 'index',
			'columns' => [
				'category_id' => 'category_id',
			],
		],
	],
	'foreign_keys' => [
	],
	'options' => [
	],
];
