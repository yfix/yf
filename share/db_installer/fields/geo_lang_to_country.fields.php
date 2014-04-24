<?php
$data = array (
	'fields' => 
	array (
		'lang' => 
		array (
			'type' => 'char',
			'length' => '2',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'country' => 
		array (
			'type' => 'char',
			'length' => '2',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
	),
	'keys' => 
	array (
		'lang_ country' => 
		array (
			'fields' => 
			array (
				0 => 'lang',
				1 => ' country',
			),
			'type' => 'primary',
		),
	),
);
