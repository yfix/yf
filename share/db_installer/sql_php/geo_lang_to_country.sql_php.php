<?php
return array(
	'fields' => array(
		'lang' => array(
			'name' => 'lang',
			'type' => 'char',
			'length' => '2',
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
		'country' => array(
			'name' => 'country',
			'type' => 'char',
			'length' => '2',
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
				'lang' => 'lang',
				'country' => 'country',
			),
		),
	),
	'foreign_keys' => array(
	),
	'options' => array(
	),
);
