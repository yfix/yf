<?php
return array(
	'name' => 'film_category',
	'fields' => array(
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
		'category_id' => array(
			'name' => 'category_id',
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
			'raw' => '`category_id` tinyint(3) unsigned NOT NULL',
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
				'film_id' => 'film_id',
				'category_id' => 'category_id',
			),
			'raw' => 'PRIMARY KEY (`film_id`,`category_id`)',
		),
		'fk_film_category_category' => array(
			'name' => 'fk_film_category_category',
			'type' => 'index',
			'columns' => array(
				'category_id' => 'category_id',
			),
			'raw' => 'KEY `fk_film_category_category` (`category_id`)',
		),
	),
	'foreign_keys' => array(
		'fk_film_category_category' => array(
			'name' => 'fk_film_category_category',
			'columns' => array(
				'category_id' => 'category_id',
			),
			'ref_table' => 'category',
			'ref_columns' => array(
				'category_id' => 'category_id',
			),
			'on_update' => 'CASCADE',
			'on_delete' => NULL,
			'raw' => 'CONSTRAINT `fk_film_category_category` FOREIGN KEY (`category_id`) REFERENCES `category` (`category_id`) ON UPDATE CASCADE',
		),
		'fk_film_category_film' => array(
			'name' => 'fk_film_category_film',
			'columns' => array(
				'film_id' => 'film_id',
			),
			'ref_table' => 'film',
			'ref_columns' => array(
				'film_id' => 'film_id',
			),
			'on_update' => 'CASCADE',
			'on_delete' => NULL,
			'raw' => 'CONSTRAINT `fk_film_category_film` FOREIGN KEY (`film_id`) REFERENCES `film` (`film_id`) ON UPDATE CASCADE',
		),
	),
	'options' => array(
		'engine' => 'InnoDB',
		'charset' => 'utf8',
	),
);