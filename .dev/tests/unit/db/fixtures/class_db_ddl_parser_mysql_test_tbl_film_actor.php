<?php
return [
	'name' => 'film_actor',
	'fields' => [
		'actor_id' => [
			'name' => 'actor_id',
			'type' => 'smallint',
			'length' => 5,
			'decimals' => NULL,
			'unsigned' => true,
			'nullable' => false,
			'default' => NULL,
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => true,
			'unique' => false,
			'values' => NULL,
			'raw' => '`actor_id` smallint(5) unsigned NOT NULL',
		],
		'film_id' => [
			'name' => 'film_id',
			'type' => 'smallint',
			'length' => 5,
			'decimals' => NULL,
			'unsigned' => true,
			'nullable' => false,
			'default' => NULL,
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => true,
			'unique' => false,
			'values' => NULL,
			'raw' => '`film_id` smallint(5) unsigned NOT NULL',
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
				'actor_id' => 'actor_id',
				'film_id' => 'film_id',
			],
			'raw' => 'PRIMARY KEY (`actor_id`,`film_id`)',
		],
		'idx_fk_film_id' => [
			'name' => 'idx_fk_film_id',
			'type' => 'index',
			'columns' => [
				'film_id' => 'film_id',
			],
			'raw' => 'KEY `idx_fk_film_id` (`film_id`)',
		],
	],
	'foreign_keys' => [
		'fk_film_actor_actor' => [
			'name' => 'fk_film_actor_actor',
			'columns' => [
				'actor_id' => 'actor_id',
			],
			'ref_table' => 'actor',
			'ref_columns' => [
				'actor_id' => 'actor_id',
			],
			'on_update' => 'CASCADE',
			'on_delete' => NULL,
			'raw' => 'CONSTRAINT `fk_film_actor_actor` FOREIGN KEY (`actor_id`) REFERENCES `actor` (`actor_id`) ON UPDATE CASCADE',
		],
		'fk_film_actor_film' => [
			'name' => 'fk_film_actor_film',
			'columns' => [
				'film_id' => 'film_id',
			],
			'ref_table' => 'film',
			'ref_columns' => [
				'film_id' => 'film_id',
			],
			'on_update' => 'CASCADE',
			'on_delete' => NULL,
			'raw' => 'CONSTRAINT `fk_film_actor_film` FOREIGN KEY (`film_id`) REFERENCES `film` (`film_id`) ON UPDATE CASCADE',
		],
	],
	'options' => [
		'engine' => 'InnoDB',
		'charset' => 'utf8',
	],
];