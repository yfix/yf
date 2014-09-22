<?php
return array(
	'name' => 'film_text',
	'fields' => array(
		'film_id' => array(
			'name' => 'film_id',
			'type' => 'smallint',
			'length' => '6',
			'decimals' => NULL,
			'unsigned' => false,
			'nullable' => false,
			'default' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
			'raw' => '`film_id` smallint(6) NOT NULL',
		),
		'title' => array(
			'name' => 'title',
			'type' => 'varchar',
			'length' => '255',
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => NULL,
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
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
			'raw' => '`description` text',
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
		'idx_title_description' => array(
			'name' => 'idx_title_description',
			'type' => 'fulltext',
			'columns' => array(
				'title' => 'title',
				'description' => 'description',
			),
			'raw' => 'FULLTEXT KEY `idx_title_description` (`title`,`description`)',
		),
	),
	'foreign_keys' => array(
	),
	'options' => array(
		'engine' => 'MyISAM',
		'charset' => 'utf8',
	),
);