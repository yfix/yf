<?php
return array(
	'fields' => array(
		'id' => array(
			'name' => 'id',
			'type' => 'int',
			'length' => 10,
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
	),
	'foreign_keys' => array(
		'fkey_prepare_sample_data' => array(
			'name' => 'fkey_prepare_sample_data',
			'columns' => array(
				'id' => 'id',
			),
			'ref_table' => 't_test_dump_2',
			'ref_columns' => array(
				'id' => 'id',
			),
			'on_update' => 'RESTRICT',
			'on_delete' => 'RESTRICT',
		),
	),
	'options' => array(
		'engine' => 'InnoDB',
		'charset' => 'utf8',
	),
);