<?php
return array(
	'fields' => array(
		'id' => array(
			'name' => 'id',
			'type' => 'int',
			'length' => 10,
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
		),
		'value' => array(
			'name' => 'value',
			'type' => 'text',
			'length' => NULL,
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
		),
		'location' => array(
			'name' => 'location',
			'type' => 'text',
			'length' => NULL,
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
