<?php
return array(
	'fields' => array(
		'id' => array(
			'name' => 'id',
			'type' => 'int',
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
		),
		'lon' => array(
			'name' => 'lon',
			'type' => 'float',
			'length' => NULL,
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
		'lat' => array(
			'name' => 'lat',
			'type' => 'float',
			'length' => NULL,
			'decimals' => NULL,
			'unsigned' => false,
			'nullable' => false,
			'default' => '0',
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => true,
			'values' => NULL,
		),
	),
	'indexes' => array(
		'lon' => array(
			'name' => 'lon',
			'type' => 'index',
			'columns' => array(
				'lon' => 'lon',
				'lat' => 'lat',
			),
		),
	),
	'foreign_keys' => array(
	),
	'options' => array(
	),
);
