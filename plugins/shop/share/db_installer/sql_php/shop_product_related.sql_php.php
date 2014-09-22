<?php
return array(
	'fields' => array(
		'product_id' => array(
			'name' => 'product_id',
			'type' => 'int',
			'length' => '11',
			'decimals' => NULL,
			'unsigned' => true,
			'nullable' => false,
			'default' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'related_id' => array(
			'name' => 'related_id',
			'type' => 'int',
			'length' => '11',
			'decimals' => NULL,
			'unsigned' => true,
			'nullable' => false,
			'default' => NULL,
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
				'product_id' => 'product_id',
				'related_id' => 'related_id',
			),
		),
	),
	'foreign_keys' => array(
	),
	'options' => array(
	),
);
