<?php
return array(
	'fields' => array(
		'start_ip' => array(
			'name' => 'start_ip',
			'type' => 'int',
			'length' => '8',
			'decimals' => NULL,
			'unsigned' => true,
			'nullable' => false,
			'default' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'end_ip' => array(
			'name' => 'end_ip',
			'type' => 'int',
			'length' => '8',
			'decimals' => NULL,
			'unsigned' => true,
			'nullable' => false,
			'default' => NULL,
			'auto_inc' => false,
			'primary' => false,
			'unique' => false,
			'values' => NULL,
		),
		'loc_id' => array(
			'name' => 'loc_id',
			'type' => 'int',
			'length' => '6',
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
				'end_ip' => 'end_ip',
			),
		),
	),
	'foreign_keys' => array(
	),
	'options' => array(
	),
);
