<?php
return array(
	'fields' => array(
		'id' => array(
			'name' => 'id',
			'type' => 'varchar',
			'length' => '32',
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => '',
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'user_id' => array(
			'name' => 'user_id',
			'type' => 'int',
			'length' => '10',
			'decimals' => NULL,
			'unsigned' => true,
			'nullable' => false,
			'default' => NULL,
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'user_group' => array(
			'name' => 'user_group',
			'type' => 'int',
			'length' => '10',
			'decimals' => NULL,
			'unsigned' => true,
			'nullable' => false,
			'default' => NULL,
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'start_time' => array(
			'name' => 'start_time',
			'type' => 'int',
			'length' => '10',
			'decimals' => NULL,
			'unsigned' => true,
			'nullable' => false,
			'default' => '0',
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'last_time' => array(
			'name' => 'last_time',
			'type' => 'int',
			'length' => '10',
			'decimals' => NULL,
			'unsigned' => true,
			'nullable' => false,
			'default' => '0',
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'type' => array(
			'name' => 'type',
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
				'user' => 'user',
				'admin' => 'admin',
			),
		),
		'host_name' => array(
			'name' => 'host_name',
			'type' => 'varchar',
			'length' => '128',
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
		'data' => array(
			'name' => 'data',
			'type' => 'longtext',
			'length' => NULL,
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => true,
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
		'user_id' => array(
			'name' => 'user_id',
			'type' => 'index',
			'columns' => array(
				'user_id' => 'user_id',
			),
		),
		'start_time' => array(
			'name' => 'start_time',
			'type' => 'index',
			'columns' => array(
				'start_time' => 'start_time',
			),
		),
	),
	'foreign_keys' => array(
	),
	'options' => array(
	),
);
