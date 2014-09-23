<?php
return array(
	'fields' => array(
		'id' => array(
			'name' => 'id',
			'type' => 'smallint',
			'length' => '5',
			'decimals' => NULL,
			'unsigned' => true,
			'nullable' => false,
			'default' => NULL,
			'auto_inc' => true,
			'primary' => true,
			'unique' => false,
			'values' => NULL,
		),
		'title' => array(
			'name' => 'title',
			'type' => 'varchar',
			'length' => '50',
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => '',
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'min' => array(
			'name' => 'min',
			'type' => 'mediumint',
			'length' => '8',
			'decimals' => NULL,
			'unsigned' => false,
			'nullable' => false,
			'default' => '0',
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'special' => array(
			'name' => 'special',
			'type' => 'tinyint',
			'length' => '1',
			'decimals' => NULL,
			'unsigned' => false,
			'nullable' => true,
			'default' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'image' => array(
			'name' => 'image',
			'type' => 'varchar',
			'length' => '255',
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => true,
			'default' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'language' => array(
			'name' => 'language',
			'type' => 'varchar',
			'length' => '12',
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => '0',
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
	),
	'foreign_keys' => array(
	),
	'options' => array(
	),
);
