<?php
$data = array (
	'fields' => 
	array (
		'id' => 
		array (
			'type' => 'int',
			'length' => '11',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 1,
		),
		'cat_ids' => 
		array (
			'type' => 'varchar',
			'length' => '255',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'name' => 
		array (
			'type' => 'varchar',
			'length' => '64',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => 'CHARACTER SET utf8 COLLATE utf8_unicode_ci',
			'auto_inc' => 0,
		),
		'url' => 
		array (
			'type' => 'varchar',
			'length' => '255',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => 'CHARACTER SET utf8 COLLATE utf8_unicode_ci',
			'auto_inc' => 0,
		),
		'desc' => 
		array (
			'type' => 'varchar',
			'length' => '255',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => 'CHARACTER SET utf8 COLLATE utf8_unicode_ci',
			'auto_inc' => 0,
		),
		'aliaces' => 
		array (
			'type' => 'varchar',
			'length' => '255',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => 'CHARACTER SET utf8 COLLATE utf8_unicode_ci',
			'auto_inc' => 0,
		),
		'meta_keywords' => 
		array (
			'type' => 'text',
			'length' => '',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => 'CHARACTER SET utf8 COLLATE utf8_unicode_ci',
			'auto_inc' => 0,
		),
		'meta_desc' => 
		array (
			'type' => 'text',
			'length' => '',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => 'CHARACTER SET utf8 COLLATE utf8_unicode_ci',
			'auto_inc' => 0,
		),
		'image' => 
		array (
			'type' => 'int',
			'length' => '10',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'sort_order' => 
		array (
			'type' => 'int',
			'length' => '3',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'active' => 
		array (
			'type' => 'int',
			'length' => '3',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
	),
	'keys' => 
	array (
		'id' => 
		array (
			'fields' => 
			array (
				0 => 'id',
			),
			'type' => 'primary',
		),
		'name' => 
		array (
			'fields' => 
			array (
				0 => 'name',
			),
			'type' => 'unique',
		),
		'url' => 
		array (
			'fields' => 
			array (
				0 => 'url',
			),
			'type' => 'unique',
		),
	),
);
