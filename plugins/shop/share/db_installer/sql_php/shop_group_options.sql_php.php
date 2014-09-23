<?php
return array(
	'fields' => array(
		'product_id' => array(
			'name' => 'product_id',
			'type' => 'int',
			'length' => '10',
			'decimals' => NULL,
			'unsigned' => true,
			'nullable' => false,
			'default' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'group_id' => array(
			'name' => 'group_id',
			'type' => 'int',
			'length' => '10',
			'decimals' => NULL,
			'unsigned' => true,
			'nullable' => false,
			'default' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'price' => array(
			'name' => 'price',
			'type' => 'decimal',
			'length' => '8',
			'decimals' => '2',
			'unsigned' => false,
			'nullable' => false,
			'default' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
	),
	'indexes' => array(
	),
	'foreign_keys' => array(
	),
	'options' => array(
	),
);
