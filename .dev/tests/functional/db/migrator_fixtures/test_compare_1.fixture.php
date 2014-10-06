<?php
return array(
'test_compare_1'	=> array(
	'fields' => array(
		'category_id' => array(
			'name' => 'category_id',
			'type' => 'tinyint',
			'length' => 3,
			'unsigned' => true,
			'nullable' => false,
			'auto_inc' => true,
			'primary' => true,
		),
		'name' => array(
			'name' => 'name',
			'type' => 'varchar',
			'length' => 25,
			'nullable' => false,
		),
		'last_update' => array(
			'name' => 'last_update',
			'type' => 'timestamp',
			'nullable' => false,
			'default' => 'CURRENT_TIMESTAMP',
			'on_update' => 'ON UPDATE CURRENT_TIMESTAMP',
		),
	),
	'indexes' => array(
		'PRIMARY' => array(
			'name' => 'PRIMARY',
			'type' => 'primary',
			'columns' => array(
				'category_id' => 'category_id',
			),
		),
	),
	'foreign_keys' => array(
	),
	'options' => array(
		'engine' => 'InnoDB',
		'charset' => 'utf8',
	),
));