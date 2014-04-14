<?php
$data = array (
	'fields' => 
	array (
		'product_id' => 
		array (
			'type' => 'int',
			'length' => '10',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'unit_id' => 
		array (
			'type' => 'int',
			'length' => '10',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
	),
	'keys' => 
	array (
		'product_id_unit_id' => 
		array (
			'fields' => 
			array (
				0 => 'product_id',
				1 => 'unit_id',
			),
			'type' => 'primary',
		),
	),
);
