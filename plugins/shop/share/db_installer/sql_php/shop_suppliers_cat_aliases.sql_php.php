<?php
return array(
	'fields' => array(
		'supplier_id' => array(
			'name' => 'supplier_id',
			'type' => 'int',
			'length' => '11',
			'decimals' => NULL,
			'unsigned' => false,
			'nullable' => false,
			'default' => '0',
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'cat_id' => array(
			'name' => 'cat_id',
			'type' => 'int',
			'length' => '11',
			'decimals' => NULL,
			'unsigned' => false,
			'nullable' => false,
			'default' => '0',
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'name' => array(
			'name' => 'name',
			'type' => 'varchar',
			'length' => '255',
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => '',
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
				'supplier_id' => 'supplier_id',
				'cat_id' => 'cat_id',
				'name' => 'name',
			),
		),
	),
	'foreign_keys' => array(
	),
	'options' => array(
	),
);
