<?php
return array(
	'fields' => array(
		'product_id' => array(
			'name' => 'product_id',
			'type' => 'int',
			'length' => '11',
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
		),
		'category_id' => array(
			'name' => 'category_id',
			'type' => 'int',
			'length' => '11',
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
		),
	),
	'indexes' => array(
		'PRIMARY' => array(
			'name' => 'PRIMARY',
			'type' => 'primary',
			'columns' => array(
				'product_id' => 'product_id',
				'category_id' => 'category_id',
			),
		),
		'product_id' => array(
			'name' => 'product_id',
			'type' => 'index',
			'columns' => array(
				'product_id' => 'product_id',
			),
		),
		'category_id' => array(
			'name' => 'category_id',
			'type' => 'index',
			'columns' => array(
				'category_id' => 'category_id',
			),
		),
	),
	'foreign_keys' => array(
	),
	'options' => array(
	),
);
