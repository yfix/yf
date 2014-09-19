<?php
return array (
	'name' => 'actor',
	'fields' => 
	array (
		'actor_id' => 
		array (
			'name' => 'actor_id',
			'type' => 'smallint',
			'length' => '5',
			'decimals' => NULL,
			'unsigned' => true,
			'nullable' => false,
			'default' => NULL,
			'auto_inc' => true,
			'primary' => true,
			'unique' => false,
			'values' => NULL,
			'raw' => '`actor_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT',
		),
		'first_name' => 
		array (
			'name' => 'first_name',
			'type' => 'varchar',
			'length' => '45',
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
			'raw' => '`first_name` varchar(45) NOT NULL',
		),
		'last_name' => 
		array (
			'name' => 'last_name',
			'type' => 'varchar',
			'length' => '45',
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
			'raw' => '`last_name` varchar(45) NOT NULL',
		),
		'last_update' => 
		array (
			'name' => 'last_update',
			'type' => 'timestamp',
			'length' => NULL,
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => 'CURRENT_TIMESTAMP',
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
			'raw' => '`last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
		),
	),
	'indexes' => 
	array (
		'PRIMARY' => 
		array (
			'name' => 'PRIMARY',
			'type' => 'primary',
			'columns' => 
			array (
				'actor_id' => 'actor_id',
			),
			'raw' => 'PRIMARY KEY (`actor_id`)',
		),
		'idx_actor_last_name' => 
		array (
			'name' => 'idx_actor_last_name',
			'type' => 'index',
			'columns' => 
			array (
				'last_name' => 'last_name',
			),
			'raw' => 'KEY `idx_actor_last_name` (`last_name`)',
		),
	),
	'foreign_keys' => 
	array (
	),
	'options' => 
	array (
		'engine' => 'InnoDB',
		'charset' => 'utf8',
	),
);