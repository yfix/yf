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
		'rating_avg' => [
			'name' => 'rating_avg',
			'type' => 'decimal',
			'length' => 2,
			'decimals' => '1',
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
		'num_votes' => [
			'name' => 'num_votes',
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
				'product_id' => 'product_id',
			],
		],
	],
	'foreign_keys' => [
	],
	'options' => [
	],
];
