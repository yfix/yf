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
		'region_id' => 
		array (
			'type' => 'int',
			'length' => '11',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
	),
	'keys' => 
	array (
		'supplier_id_region_id' => 
		array (
			'fields' => 
			array (
				0 => 'supplier_id',
				1 => 'region_id',
			),
			'type' => 'primary',
		),
	),
);
