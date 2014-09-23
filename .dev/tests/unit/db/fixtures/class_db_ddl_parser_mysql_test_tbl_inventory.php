<?php
return array(
	'name' => 'inventory',
	'fields' => array(
		'inventory_id' => array(
			'name' => 'inventory_id',
			'type' => 'mediumint',
			'length' => '8',
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
			'raw' => '`inventory_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT',
		),
		'film_id' => array(
			'name' => 'film_id',
			'type' => 'smallint',
			'length' => '5',
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
			'raw' => '`film_id` smallint(5) unsigned NOT NULL',
		),
		'store_id' => array(
			'name' => 'store_id',
			'type' => 'tinyint',
			'length' => '3',
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
			'raw' => '`store_id` tinyint(3) unsigned NOT NULL',
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
				'inventory_id' => 'inventory_id',
			),
			'raw' => 'PRIMARY KEY (`inventory_id`)',
		),
		'idx_fk_film_id' => array(
			'name' => 'idx_fk_film_id',
			'type' => 'index',
			'columns' => array(
				'film_id' => 'film_id',
			),
			'raw' => 'KEY `idx_fk_film_id` (`film_id`)',
		),
		'idx_store_id_film_id' => array(
			'name' => 'idx_store_id_film_id',
			'type' => 'index',
			'columns' => array(
				'store_id' => 'store_id',
				'film_id' => 'film_id',
			),
			'raw' => 'KEY `idx_store_id_film_id` (`store_id`,`film_id`)',
		),
	),
	'foreign_keys' => array(
		'fk_inventory_film' => array(
			'name' => 'fk_inventory_film',
			'columns' => array(
				'film_id' => 'film_id',
			),
			'ref_table' => 'film',
			'ref_columns' => array(
				'film_id' => 'film_id',
			),
			'on_update' => 'CASCADE',
			'on_delete' => NULL,
			'raw' => 'CONSTRAINT `fk_inventory_film` FOREIGN KEY (`film_id`) REFERENCES `film` (`film_id`) ON UPDATE CASCADE',
		),
		'fk_inventory_store' => array(
			'name' => 'fk_inventory_store',
			'columns' => array(
				'store_id' => 'store_id',
			),
			'ref_table' => 'store',
			'ref_columns' => array(
				'store_id' => 'store_id',
			),
			'on_update' => 'CASCADE',
			'on_delete' => NULL,
			'raw' => 'CONSTRAINT `fk_inventory_store` FOREIGN KEY (`store_id`) REFERENCES `store` (`store_id`) ON UPDATE CASCADE',
		),
	),
	'options' => array(
		'engine' => 'InnoDB',
		'charset' => 'utf8',
	),
);