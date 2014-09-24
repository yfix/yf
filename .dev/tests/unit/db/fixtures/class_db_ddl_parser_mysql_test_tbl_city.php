<?php
return array(
	'name' => 'city',
	'fields' => array(
		'city_id' => array(
			'name' => 'city_id',
			'type' => 'smallint',
			'length' => 5,
			'decimals' => NULL,
			'unsigned' => true,
			'nullable' => false,
			'default' => NULL,
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => true,
			'primary' => true,
			'unique' => false,
			'values' => NULL,
			'raw' => '`city_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT',
		),
		'city' => array(
			'name' => 'city',
			'type' => 'varchar',
			'length' => 50,
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
			'raw' => '`city` varchar(50) NOT NULL',
		),
		'country_id' => array(
			'name' => 'country_id',
			'type' => 'smallint',
			'length' => 5,
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
			'raw' => '`country_id` smallint(5) unsigned NOT NULL',
		),
		'last_update' => array(
			'name' => 'last_update',
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
			'raw' => '`last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
		),
	),
	'indexes' => array(
		'PRIMARY' => array(
			'name' => 'PRIMARY',
			'type' => 'primary',
			'columns' => array(
				'city_id' => 'city_id',
			),
			'raw' => 'PRIMARY KEY (`city_id`)',
		),
		'idx_fk_country_id' => array(
			'name' => 'idx_fk_country_id',
			'type' => 'index',
			'columns' => array(
				'country_id' => 'country_id',
			),
			'raw' => 'KEY `idx_fk_country_id` (`country_id`)',
		),
	),
	'foreign_keys' => array(
		'fk_city_country' => array(
			'name' => 'fk_city_country',
			'columns' => array(
				'country_id' => 'country_id',
			),
			'ref_table' => 'country',
			'ref_columns' => array(
				'country_id' => 'country_id',
			),
			'on_update' => 'CASCADE',
			'on_delete' => NULL,
			'raw' => 'CONSTRAINT `fk_city_country` FOREIGN KEY (`country_id`) REFERENCES `country` (`country_id`) ON UPDATE CASCADE',
		),
	),
	'options' => array(
		'engine' => 'InnoDB',
		'charset' => 'utf8',
	),
);