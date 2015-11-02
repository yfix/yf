<?php
return array(
	'up' => array(
		0 => array(
			'cmd' => 'create_table',
			'table' => 'test_generate_1',
			'info' => array(
				'fields' => array(
					'id' => array(
						'name' => 'id',
						'type' => 'int',
						'length' => 10,
						'nullable' => false,
					),
				),
				'indexes' => array(
					'PRIMARY' => array(
						'name' => 'PRIMARY',
						'type' => 'primary',
						'columns' => array(
							'id' => 'id',
						),
					),
				),
				'foreign_keys' => array(
					'fkey_prepare_sample_data' => array(
						'name' => 'fkey_prepare_sample_data',
						'columns' => array(
							'id' => 'id',
						),
						'ref_table' => 'test_generate_2',
						'ref_columns' => array(
							'id' => 'id',
						),
						'on_update' => 'RESTRICT',
						'on_delete' => 'RESTRICT',
					),
				),
				'options' => array(
					'engine' => 'InnoDB',
					'charset' => 'utf8',
				),
			),
		),
		1 => array(
			'cmd' => 'create_table',
			'table' => 'test_generate_2',
			'info' => array(
				'fields' => array(
					'id' => array(
						'name' => 'id',
						'type' => 'int',
						'length' => 10,
						'nullable' => false,
					),
				),
				'indexes' => array(
					'PRIMARY' => array(
						'name' => 'PRIMARY',
						'type' => 'primary',
						'columns' => array(
							'id' => 'id',
						),
					),
				),
				'options' => array(
					'engine' => 'InnoDB',
					'charset' => 'utf8',
				),
			),
		),
	),
	'down' => array(
		0 => array(
			'cmd' => 'drop_table',
			'table' => 'test_generate_1',
		),
		1 => array(
			'cmd' => 'drop_table',
			'table' => 'test_generate_2',
		),
	),
);
