<?php
return [
	'fields' => [
		'product_id' => [
			'name' => 'product_id',
			'type' => 'int',
			'length' => 11,
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
		'related_id' => [
			'name' => 'related_id',
			'type' => 'int',
			'length' => 11,
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
	],
	'indexes' => [
		'PRIMARY' => [
			'name' => 'PRIMARY',
			'type' => 'primary',
			'columns' => [
				'product_id' => 'product_id',
				'related_id' => 'related_id',
			],
		],
	],
	'foreign_keys' => [
	],
	'options' => [
	],
];
