<?php
return [
	'name' => 'address',
	'fields' => [
		'address_id' => [
			'name' => 'address_id',
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
			'raw' => '`address_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT',
		],
		'address' => [
			'name' => 'address',
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
			'raw' => '`address` varchar(50) NOT NULL',
		],
		'address2' => [
			'name' => 'address2',
			'type' => 'varchar',
			'length' => 50,
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => true,
			'default' => 'NULL',
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
			'raw' => '`address2` varchar(50) DEFAULT NULL',
		],
		'district' => [
			'name' => 'district',
			'type' => 'varchar',
			'length' => 20,
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
			'raw' => '`district` varchar(20) NOT NULL',
		],
		'city_id' => [
			'name' => 'city_id',
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
			'raw' => '`city_id` smallint(5) unsigned NOT NULL',
		],
		'postal_code' => [
			'name' => 'postal_code',
			'type' => 'varchar',
			'length' => 10,
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => true,
			'default' => 'NULL',
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
			'raw' => '`postal_code` varchar(10) DEFAULT NULL',
		],
		'phone' => [
			'name' => 'phone',
			'type' => 'varchar',
			'length' => 20,
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
			'raw' => '`phone` varchar(20) NOT NULL',
		],
		'last_update' => [
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
		],
	],
	'indexes' => [
		'PRIMARY' => [
			'name' => 'PRIMARY',
			'type' => 'primary',
			'columns' => [
				'address_id' => 'address_id',
			],
			'raw' => 'PRIMARY KEY (`address_id`)',
		],
		'idx_fk_city_id' => [
			'name' => 'idx_fk_city_id',
			'type' => 'index',
			'columns' => [
				'city_id' => 'city_id',
			],
			'raw' => 'KEY `idx_fk_city_id` (`city_id`)',
		],
	],
	'foreign_keys' => [
		'fk_address_city' => [
			'name' => 'fk_address_city',
			'columns' => [
				'city_id' => 'city_id',
			],
			'ref_table' => 'city',
			'ref_columns' => [
				'city_id' => 'city_id',
			],
			'on_update' => 'CASCADE',
			'on_delete' => NULL,
			'raw' => 'CONSTRAINT `fk_address_city` FOREIGN KEY (`city_id`) REFERENCES `city` (`city_id`) ON UPDATE CASCADE',
		],
	],
	'options' => [
		'engine' => 'InnoDB',
		'charset' => 'utf8',
	],
];