<?php
return array(
	'fields' => array(
		'code' => array(
			'name' => 'code',
			'type' => 'char',
			'length' => '2',
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
		'name' => array(
			'name' => 'name',
			'type' => 'varchar',
			'length' => '20',
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
		),
		'name_eng' => array(
			'name' => 'name_eng',
			'type' => 'varchar',
			'length' => '20',
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
		),
		'geoname_id' => array(
			'name' => 'geoname_id',
			'type' => 'int',
			'length' => '11',
			'decimals' => NULL,
			'unsigned' => false,
			'nullable' => true,
			'default' => 'NULL',
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'active' => array(
			'name' => 'active',
			'type' => 'enum',
			'length' => NULL,
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => '0',
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => array(
				0 => '0',
				1 => '1',
			),
		),
	),
	'indexes' => array(
		'PRIMARY' => array(
			'name' => 'PRIMARY',
			'type' => 'primary',
			'columns' => array(
				'code' => 'code',
			),
		),
	),
	'foreign_keys' => array(
	),
	'options' => array(
	),
);
