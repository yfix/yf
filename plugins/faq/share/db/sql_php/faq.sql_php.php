<?php
return array(
	'fields' => array(
		'id' => array(
			'name' => 'id',
			'type' => 'int',
			'length' => 10,
			'unsigned' => true,
			'nullable' => false,
			'auto_inc' => true,
		),
		'parent_id' => array(
			'name' => 'parent_id',
			'type' => 'int',
			'length' => 10,
			'unsigned' => true,
			'nullable' => false,
		),
		'author_id' => array(
			'name' => 'author_id',
			'type' => 'int',
			'length' => 10,
			'unsigned' => true,
			'nullable' => false,
		),
		'title' => array(
			'name' => 'title',
			'type' => 'text',
			'nullable' => false,
		),
		'text' => array(
			'name' => 'text',
			'type' => 'text',
			'nullable' => false,
		),
		'active' => array(
			'name' => 'active',
			'type' => 'enum',
			'nullable' => false,
			'values' => array(
				1 => '1',
				0 => '0',
			),
		),
		'add_date' => array(
			'name' => 'add_date',
			'type' => 'int',
			'length' => 10,
			'unsigned' => true,
			'nullable' => false,
		),
		'edit_date' => array(
			'name' => 'edit_date',
			'type' => 'int',
			'length' => 10,
			'unsigned' => true,
			'nullable' => false,
		),
		'views' => array(
			'name' => 'views',
			'type' => 'int',
			'length' => 10,
			'unsigned' => true,
			'nullable' => false,
		),
		'locale' => array(
			'name' => 'locale',
			'type' => 'char',
			'length' => 2,
			'nullable' => false,
			'default' => 'ru',
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
	'options' => array(
		'engine' => 'InnoDB',
		'charset' => 'utf8',
	),
);
