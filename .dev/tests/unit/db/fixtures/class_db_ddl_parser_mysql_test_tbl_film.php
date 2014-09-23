<?php
return array(
	'name' => 'film',
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
			'auto_inc' => true,
			'primary' => true,
			'unique' => false,
			'values' => NULL,
			'raw' => '`film_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT',
		),
		'title' => array(
			'name' => 'title',
			'type' => 'varchar',
			'length' => '255',
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
			'raw' => '`title` varchar(255) NOT NULL',
		),
		'description' => array(
			'name' => 'description',
			'type' => 'text',
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
			'raw' => '`description` text',
		),
		'release_year' => array(
			'name' => 'release_year',
			'type' => 'year',
			'length' => '4',
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
			'raw' => '`release_year` year(4) DEFAULT NULL',
		),
		'language_id' => array(
			'name' => 'language_id',
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
			'raw' => '`language_id` tinyint(3) unsigned NOT NULL',
		),
		'original_language_id' => array(
			'name' => 'original_language_id',
			'type' => 'tinyint',
			'length' => '3',
			'decimals' => NULL,
			'unsigned' => true,
			'nullable' => true,
			'default' => 'NULL',
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
			'raw' => '`original_language_id` tinyint(3) unsigned DEFAULT NULL',
		),
		'rental_duration' => array(
			'name' => 'rental_duration',
			'type' => 'tinyint',
			'length' => '3',
			'decimals' => NULL,
			'unsigned' => true,
			'nullable' => false,
			'default' => '3',
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
			'raw' => '`rental_duration` tinyint(3) unsigned NOT NULL DEFAULT \'3\'',
		),
		'rental_rate' => array(
			'name' => 'rental_rate',
			'type' => 'decimal',
			'length' => '4',
			'decimals' => '2',
			'unsigned' => false,
			'nullable' => false,
			'default' => '4.99',
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
			'raw' => '`rental_rate` decimal(4,2) NOT NULL DEFAULT \'4.99\'',
		),
		'length' => array(
			'name' => 'length',
			'type' => 'smallint',
			'length' => '5',
			'decimals' => NULL,
			'unsigned' => true,
			'nullable' => true,
			'default' => 'NULL',
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
			'raw' => '`length` smallint(5) unsigned DEFAULT NULL',
		),
		'replacement_cost' => array(
			'name' => 'replacement_cost',
			'type' => 'decimal',
			'length' => '5',
			'decimals' => '2',
			'unsigned' => false,
			'nullable' => false,
			'default' => '19.99',
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
			'raw' => '`replacement_cost` decimal(5,2) NOT NULL DEFAULT \'19.99\'',
		),
		'rating' => array(
			'name' => 'rating',
			'type' => 'enum',
			'length' => NULL,
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => true,
			'default' => 'G',
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => array(
				'G' => 'G',
				'PG' => 'PG',
				'PG-13' => 'PG-13',
				'R' => 'R',
				'NC-17' => 'NC-17',
			),
			'raw' => '`rating` enum(\'G\',\'PG\',\'PG-13\',\'R\',\'NC-17\') DEFAULT \'G\'',
		),
		'special_features' => array(
			'name' => 'special_features',
			'type' => 'set',
			'length' => NULL,
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => true,
			'default' => 'NULL',
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => array(
				'Trailers' => 'Trailers',
				'Commentaries' => 'Commentaries',
				'Deleted Scenes' => 'Deleted Scenes',
				'Behind the Scenes' => 'Behind the Scenes',
			),
			'raw' => '`special_features` set(\'Trailers\',\'Commentaries\',\'Deleted Scenes\',\'Behind the Scenes\') DEFAULT NULL',
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
			),
			'raw' => 'PRIMARY KEY (`film_id`)',
		),
		'idx_title' => array(
			'name' => 'idx_title',
			'type' => 'index',
			'columns' => array(
				'title' => 'title',
			),
			'raw' => 'KEY `idx_title` (`title`)',
		),
		'idx_fk_language_id' => array(
			'name' => 'idx_fk_language_id',
			'type' => 'index',
			'columns' => array(
				'language_id' => 'language_id',
			),
			'raw' => 'KEY `idx_fk_language_id` (`language_id`)',
		),
		'idx_fk_original_language_id' => array(
			'name' => 'idx_fk_original_language_id',
			'type' => 'index',
			'columns' => array(
				'original_language_id' => 'original_language_id',
			),
			'raw' => 'KEY `idx_fk_original_language_id` (`original_language_id`)',
		),
	),
	'foreign_keys' => array(
		'fk_film_language' => array(
			'name' => 'fk_film_language',
			'columns' => array(
				'language_id' => 'language_id',
			),
			'ref_table' => 'language',
			'ref_columns' => array(
				'language_id' => 'language_id',
			),
			'on_update' => 'CASCADE',
			'on_delete' => NULL,
			'raw' => 'CONSTRAINT `fk_film_language` FOREIGN KEY (`language_id`) REFERENCES `language` (`language_id`) ON UPDATE CASCADE',
		),
		'fk_film_language_original' => array(
			'name' => 'fk_film_language_original',
			'columns' => array(
				'original_language_id' => 'original_language_id',
			),
			'ref_table' => 'language',
			'ref_columns' => array(
				'language_id' => 'language_id',
			),
			'on_update' => 'CASCADE',
			'on_delete' => NULL,
			'raw' => 'CONSTRAINT `fk_film_language_original` FOREIGN KEY (`original_language_id`) REFERENCES `language` (`language_id`) ON UPDATE CASCADE',
		),
	),
	'options' => array(
		'engine' => 'InnoDB',
		'charset' => 'utf8',
	),
);