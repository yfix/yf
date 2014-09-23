<?php
return array(
	'fields' => array(
		'id' => array(
			'name' => 'id',
			'type' => 'tinyint',
			'length' => '4',
			'decimals' => NULL,
			'unsigned' => false,
			'nullable' => false,
			'default' => '0',
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'height' => array(
			'name' => 'height',
			'type' => 'varchar',
			'length' => '50',
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => '',
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
	),
	'indexes' => array(
		'PRIMARY' => array(
			'name' => 'PRIMARY',
			'type' => 'primary',
			'columns' => array(
				'id' => 'id',
			),
		),
		'id_2' => array(
			'name' => 'id_2',
			'type' => 'unique',
			'columns' => array(
				'id' => 'id',
			),
		),
		'id' => array(
			'name' => 'id',
			'type' => 'index',
			'columns' => array(
				'id' => 'id',
			),
		),
	),
	'foreign_keys' => array(
	),
	'options' => array(
	),
);
