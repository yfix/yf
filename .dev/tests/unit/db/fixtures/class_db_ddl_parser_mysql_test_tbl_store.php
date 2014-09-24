<?php
return array(
	'name' => 'store',
	'fields' => array(
		'store_id' => array(
			'name' => 'store_id',
			'type' => 'tinyint',
			'length' => 3,
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
			'raw' => '`store_id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT',
		),
		'manager_staff_id' => array(
			'name' => 'manager_staff_id',
			'type' => 'tinyint',
			'length' => 3,
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
			'raw' => '`manager_staff_id` tinyint(3) unsigned NOT NULL',
		),
		'address_id' => array(
			'name' => 'address_id',
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
			'raw' => '`address_id` smallint(5) unsigned NOT NULL',
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
				'store_id' => 'store_id',
			),
			'raw' => 'PRIMARY KEY (`store_id`)',
		),
		'idx_unique_manager' => array(
			'name' => 'idx_unique_manager',
			'type' => 'unique',
			'columns' => array(
				'manager_staff_id' => 'manager_staff_id',
			),
			'raw' => 'UNIQUE KEY `idx_unique_manager` (`manager_staff_id`)',
		),
		'idx_fk_address_id' => array(
			'name' => 'idx_fk_address_id',
			'type' => 'index',
			'columns' => array(
				'address_id' => 'address_id',
			),
			'raw' => 'KEY `idx_fk_address_id` (`address_id`)',
		),
	),
	'foreign_keys' => array(
		'fk_store_address' => array(
			'name' => 'fk_store_address',
			'columns' => array(
				'address_id' => 'address_id',
			),
			'ref_table' => 'address',
			'ref_columns' => array(
				'address_id' => 'address_id',
			),
			'on_update' => 'CASCADE',
			'on_delete' => NULL,
			'raw' => 'CONSTRAINT `fk_store_address` FOREIGN KEY (`address_id`) REFERENCES `address` (`address_id`) ON UPDATE CASCADE',
		),
		'fk_store_staff' => array(
			'name' => 'fk_store_staff',
			'columns' => array(
				'manager_staff_id' => 'manager_staff_id',
			),
			'ref_table' => 'staff',
			'ref_columns' => array(
				'staff_id' => 'staff_id',
			),
			'on_update' => 'CASCADE',
			'on_delete' => NULL,
			'raw' => 'CONSTRAINT `fk_store_staff` FOREIGN KEY (`manager_staff_id`) REFERENCES `staff` (`staff_id`) ON UPDATE CASCADE',
		),
	),
	'options' => array(
		'engine' => 'InnoDB',
		'charset' => 'utf8',
	),
);