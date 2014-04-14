<?php
$data = array (
	'fields' => 
	array (
		'product_id' => 
		array (
			'type' => 'int',
			'length' => '11',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'related_id' => 
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
		'product_id_related_id' => 
		array (
			'fields' => 
			array (
				0 => 'product_id',
				1 => 'related_id',
			),
			'type' => 'primary',
		),
	),
);
