<?php
return array(
	'fields' => array(
		'id' => array(
			'name' => 'id',
			'type' => 'int',
			'length' => 10,
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => NULL,
			'charset' => NULL,
			'collate' => NULL,
			'auto_inc' => false,
			'primary' => true,
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
		'engine' => 'InnoDB',
		'charset' => 'utf8',
	),
);