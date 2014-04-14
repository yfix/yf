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
		'rating_avg' => 
		array (
			'type' => 'decimal',
			'length' => '2,1',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'num_votes' => 
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
		'product_id' => 
		array (
			'fields' => 
			array (
				0 => 'product_id',
			),
			'type' => 'primary',
		),
	),
);
