<?php
return array(
	'fields' => array(
		'user_id' => array(
			'name' => 'user_id',
			'type' => 'int',
			'length' => '10',
			'decimals' => NULL,
			'unsigned' => true,
			'nullable' => false,
			'default' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'points' => array(
			'name' => 'points',
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
		'alt_power' => array(
			'name' => 'alt_power',
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
		'num_votes' => array(
			'name' => 'num_votes',
			'type' => 'int',
			'length' => '10',
			'decimals' => NULL,
			'unsigned' => true,
			'nullable' => false,
			'default' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'num_voted' => array(
			'name' => 'num_voted',
			'type' => 'int',
			'length' => '10',
			'decimals' => NULL,
			'unsigned' => true,
			'nullable' => false,
			'default' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'reput_change' => array(
			'name' => 'reput_change',
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
				'user_id' => 'user_id',
			),
		),
	),
	'foreign_keys' => array(
	),
	'options' => array(
	),
);
