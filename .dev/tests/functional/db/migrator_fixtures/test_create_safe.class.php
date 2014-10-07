<?php

load('db_migrator', 'framework', 'classes/db/');
class db_migration_%MIGRATION_NUMBER% extends yf_db_migrator {

	/**
	*/
	protected function up() {
		$utils = $this->db->utils();
		$utils->create_table('test_create_safe_1', array(
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
					'ref_table' => 'test_create_safe_2',
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
		));
		$utils->create_table('test_create_safe_2', array(
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
		));
	}

	/**
	*/
	protected function down() {
		$utils = $this->db->utils();
	}
}