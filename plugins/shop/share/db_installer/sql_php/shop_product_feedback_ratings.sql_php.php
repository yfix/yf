<?php
return array(
	'fields' => array(
		'product_id' => array(
			'name' => 'product_id',
			'type' => 'int',
			'length' => '11',
			'decimals' => NULL,
			'unsigned' => false,
			'nullable' => false,
			'default' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'rating_avg' => array(
			'name' => 'rating_avg',
			'type' => 'decimal',
			'length' => '2',
			'decimals' => '1',
			'unsigned' => false,
			'nullable' => false,
			'default' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'num_votes' => array(
			'name' => 'num_votes',
			'type' => 'int',
			'length' => '11',
			'decimals' => NULL,
			'unsigned' => false,
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
			),
		),
	),
	'foreign_keys' => array(
	),
	'options' => array(
	),
);
