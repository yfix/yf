<?php
return array(
	'fields' => array(
		'id' => array(
			'name' => 'id',
			'type' => 'int',
			'length' => '10',
			'decimals' => NULL,
			'unsigned' => true,
			'nullable' => false,
			'default' => NULL,
			'auto_inc' => true,
			'primary' => true,
			'unique' => false,
			'values' => NULL,
		),
		'locale' => array(
			'name' => 'locale',
			'type' => 'char',
			'length' => '5',
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => '',
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'name' => array(
			'name' => 'name',
			'type' => 'varchar',
			'length' => '255',
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => '',
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'title' => array(
			'name' => 'title',
			'type' => 'varchar',
			'length' => '255',
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => '',
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'heading' => array(
			'name' => 'heading',
			'type' => 'varchar',
			'length' => '255',
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => '',
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'text' => array(
			'name' => 'text',
			'type' => 'longtext',
			'length' => NULL,
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => '',
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'meta_keywords' => array(
			'name' => 'meta_keywords',
			'type' => 'text',
			'length' => NULL,
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => '',
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'meta_desc' => array(
			'name' => 'meta_desc',
			'type' => 'text',
			'length' => NULL,
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => false,
			'default' => '',
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'date_created' => array(
			'name' => 'date_created',
			'type' => 'datetime',
			'length' => NULL,
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => true,
			'default' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'date_modified' => array(
			'name' => 'date_modified',
			'type' => 'datetime',
			'length' => NULL,
			'decimals' => NULL,
			'unsigned' => NULL,
			'nullable' => true,
			'default' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'content_type' => array(
			'name' => 'content_type',
			'type' => 'tinyint',
			'length' => '2',
			'decimals' => NULL,
			'unsigned' => true,
			'nullable' => false,
			'default' => '1',
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
			'default' => '1',
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => array(
				1 => '1',
				0 => '0',
			),
		),
	),
	'indexes' => array(
		'idx_1' => array(
			'name' => 'idx_1',
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