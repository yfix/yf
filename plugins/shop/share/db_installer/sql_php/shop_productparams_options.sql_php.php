<?php
return array(
	'fields' => array(
		'id' => array(
			'name' => 'id',
			'type' => 'bigint',
			'length' => '20',
			'decimals' => NULL,
			'unsigned' => false,
			'nullable' => false,
			'default' => NULL,
			'auto_inc' => true,
			'primary' => true,
			'unique' => false,
			'values' => NULL,
		),
		'productparams_id' => array(
			'name' => 'productparams_id',
			'type' => 'int',
			'length' => '11',
			'decimals' => NULL,
			'unsigned' => false,
			'nullable' => false,
			'default' => '0',
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'title' => array(
			'name' => 'title',
			'type' => 'varchar',
			'length' => '100',
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => '',
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'sort' => array(
			'name' => 'sort',
			'type' => 'varchar',
			'length' => '10',
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => '',
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
		'productparams_id' => array(
			'name' => 'productparams_id',
			'type' => 'index',
			'columns' => array(
				'productparams_id' => 'productparams_id',
			),
		),
	),
	'foreign_keys' => array(
	),
	'options' => array(
	),
);
