<?php
return array(
	'tables_changed' => array(
		'test_compare_1' => array(
			'columns_missing' => array(
				'category_id' => array(
					'name' => 'category_id',
					'type' => 'tinyint',
					'length' => 3,
					'unsigned' => true,
					'nullable' => false,
					'auto_inc' => true,
				),
				'name' => array(
					'name' => 'name',
					'type' => 'varchar',
					'length' => 25,
					'nullable' => false,
				),
				'last_update' => array(
					'name' => 'last_update',
					'type' => 'timestamp',
					'nullable' => false,
					'default' => 'CURRENT_TIMESTAMP',
					'on_update' => 'ON UPDATE CURRENT_TIMESTAMP',
				),
			),
			'columns_new' => array(
				'id' => array(
					'name' => 'id',
					'type' => 'int',
					'length' => 10,
					'nullable' => false,
				),
			),
			'indexes_changed' => array(
				'PRIMARY' => array(
					'columns' => array(
						'expected' => array(
							'category_id' => 'category_id',
						),
						'actual' => array(
							'id' => 'id',
						),
					),
				),
			),
			'foreign_keys_new' => array(
				'fkey_prepare_sample_data' => array(
					'name' => 'fkey_prepare_sample_data',
					'columns' => array(
						'id' => 'id',
					),
					'ref_table' => 't_test_compare_2',
					'ref_columns' => array(
						'id' => 'id',
					),
					'on_update' => 'RESTRICT',
					'on_delete' => 'RESTRICT',
				),
			),
		),
	),
	'tables_new' => array(
		'test_compare_2' => 'test_compare_2',
	),
);
