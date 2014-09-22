<?php
return array(
	'fields' => array(
		'microtime' => array(
			'name' => 'microtime',
			'type' => 'decimal',
			'length' => '13',
			'decimals' => '3',
			'unsigned' => true,
			'nullable' => false,
			'default' => '0.000',
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'server_id' => array(
			'name' => 'server_id',
			'type' => 'varchar',
			'length' => '64',
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => '',
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'init_type' => array(
			'name' => 'init_type',
			'type' => 'enum',
			'length' => NULL,
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => true,
			'default' => 'user',
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => array(
				'user' => 'user',
				'admin' => 'admin',
			),
		),
		'action' => array(
			'name' => 'action',
			'type' => 'varchar',
			'length' => '32',
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => '',
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'comment' => array(
			'name' => 'comment',
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
		'get_object' => array(
			'name' => 'get_object',
			'type' => 'varchar',
			'length' => '32',
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => '',
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'get_action' => array(
			'name' => 'get_action',
			'type' => 'varchar',
			'length' => '32',
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => '',
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'user_id' => array(
			'name' => 'user_id',
			'type' => 'int',
			'length' => '11',
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
			'type' => 'tinyint',
			'length' => '2',
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
			'type' => 'varchar',
			'length' => '32',
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
		'idx_1' => array(
			'name' => 'idx_1',
			'type' => 'index',
			'columns' => array(
				'microtime' => 'microtime',
			),
		),
		'idx_2' => array(
			'name' => 'idx_2',
			'type' => 'index',
			'columns' => array(
				'server_id' => 'server_id',
			),
		),
	),
	'foreign_keys' => array(
	),
	'options' => array(
	),
);
