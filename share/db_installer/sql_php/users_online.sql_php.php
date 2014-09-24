<?php
return array(
	'fields' => array(
		'user_id' => array(
			'name' => 'user_id',
			'type' => 'bigint',
			'length' => 20,
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
		'user_type' => array(
			'name' => 'user_type',
			'type' => 'enum',
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
			'values' => array(
				'user_id' => 'user_id',
				'user_id_tmp' => 'user_id_tmp',
				'admin_id' => 'admin_id',
			),
		),
		'time' => array(
			'name' => 'time',
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
		),
	),
	'indexes' => array(
		'PRIMARY' => array(
			'name' => 'PRIMARY',
			'type' => 'primary',
			'columns' => array(
				'user_id' => 'user_id',
				'user_type' => 'user_type',
			),
		),
	),
	'foreign_keys' => array(
	),
	'options' => array(
	),
);
