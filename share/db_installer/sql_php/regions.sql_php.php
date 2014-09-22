<?php
return array(
	'fields' => array(
		'code' => array(
			'name' => 'code',
			'type' => 'char',
			'length' => '2',
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'country' => array(
			'name' => 'country',
			'type' => 'char',
			'length' => '2',
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => NULL,
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
			'default' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'active' => array(
			'name' => 'active',
			'type' => 'enum',
			'length' => NULL,
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => '1',
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => array(
				1 => '1',
				0 => '0',
			),
		),
	),
	'indexes' => array(
		'country' => array(
			'name' => 'country',
			'type' => 'index',
			'columns' => array(
				'country' => 'country',
			),
		),
		'code' => array(
			'name' => 'code',
			'type' => 'index',
			'columns' => array(
				'code' => 'code',
			),
		),
	),
	'foreign_keys' => array(
	),
	'options' => array(
	),
);
