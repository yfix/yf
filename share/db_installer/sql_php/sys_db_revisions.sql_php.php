<?php
return array(
	'fields' => array(
		'id' => array(
			'name' => 'id',
			'type' => 'int',
			'length' => '10',
			'decimals' => NULL,
			'unsigned' => true,
			'nullable' => false,
			'default' => NULL,
			'auto_inc' => true,
			'primary' => true,
			'unique' => false,
			'values' => NULL,
		),
		'query_method' => array(
			'name' => 'query_method',
			'type' => 'varchar',
			'length' => '128',
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => '',
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'query_table' => array(
			'name' => 'query_table',
			'type' => 'varchar',
			'length' => '128',
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => '',
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'date' => array(
			'name' => 'date',
			'type' => 'datetime',
			'length' => NULL,
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'data_old' => array(
			'name' => 'data_old',
			'type' => 'longtext',
			'length' => NULL,
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'data_new' => array(
			'name' => 'data_new',
			'type' => 'longtext',
			'length' => NULL,
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'data_diff' => array(
			'name' => 'data_diff',
			'type' => 'longtext',
			'length' => NULL,
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => NULL,
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
			'default' => '0',
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
			'default' => '0',
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'site_id' => array(
			'name' => 'site_id',
			'type' => 'tinyint',
			'length' => '3',
			'decimals' => NULL,
			'unsigned' => true,
			'nullable' => false,
			'default' => '0',
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'server_id' => array(
			'name' => 'server_id',
			'type' => 'tinyint',
			'length' => '3',
			'decimals' => NULL,
			'unsigned' => true,
			'nullable' => false,
			'default' => '0',
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'ip' => array(
			'name' => 'ip',
			'type' => 'char',
			'length' => '15',
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'url' => array(
			'name' => 'url',
			'type' => 'text',
			'length' => NULL,
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'extra' => array(
			'name' => 'extra',
			'type' => 'longtext',
			'length' => NULL,
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => NULL,
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
		'query_table' => array(
			'name' => 'query_table',
			'type' => 'index',
			'columns' => array(
				'query_table' => 'query_table',
			),
		),
		'query_method' => array(
			'name' => 'query_method',
			'type' => 'index',
			'columns' => array(
				'query_method' => 'query_method',
			),
		),
	),
	'foreign_keys' => array(
	),
	'options' => array(
	),
);