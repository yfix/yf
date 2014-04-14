<?php
$data = array (
	'fields' => 
	array (
		'supplier_id' => 
		array (
			'type' => 'int',
			'length' => '11',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'cat_id' => 
		array (
			'type' => 'int',
			'length' => '11',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'name' => 
		array (
			'type' => 'varchar',
			'length' => '255',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
	),
	'keys' => 
	array (
		'supplier_id_cat_id_name' => 
		array (
			'fields' => 
			array (
				0 => 'supplier_id',
				1 => 'cat_id',
				2 => 'name',
			),
			'type' => 'primary',
		),
	),
);
