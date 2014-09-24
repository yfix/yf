<?php
return array(
	'fields' => array(
		'id' => array(
			'name' => 'id',
			'type' => 'char',
			'length' => 32,
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => NULL,
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'ts' => array(
			'name' => 'ts',
			'type' => 'timestamp',
			'length' => NULL,
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => 'CURRENT_TIMESTAMP',
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
			'on_update' => 'ON UPDATE CURRENT_TIMESTAMP',
		),
		'data' => array(
			'name' => 'data',
			'type' => 'text',
			'length' => NULL,
			'decimals' => NULL,
			'unsigned' => NULL,
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
				'id' => 'id',
			),
		),
		'ts' => array(
			'name' => 'ts',
			'type' => 'index',
			'columns' => array(
				'ts' => 'ts',
			),
		),
	),
	'foreign_keys' => array(
	),
	'options' => array(
	),
);
