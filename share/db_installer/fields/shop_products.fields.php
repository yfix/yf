<?php
$data = array (
	'fields' => 
	array (
		'id' => 
		array (
			'type' => 'int',
			'length' => '10',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 1,
		),
		'name' => 
		array (
			'type' => 'varchar',
			'length' => '255',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => 'CHARACTER SET utf8 COLLATE utf8_unicode_ci',
			'auto_inc' => 0,
		),
		'url' => 
		array (
			'type' => 'varchar',
			'length' => '255',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => 'CHARACTER SET utf8 COLLATE utf8_unicode_ci',
			'auto_inc' => 0,
		),
		'image' => 
		array (
			'type' => 'tinyint',
			'length' => '1',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'description' => 
		array (
			'type' => 'text',
			'length' => '',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => 'CHARACTER SET utf8 COLLATE utf8_unicode_ci',
			'auto_inc' => 0,
		),
		'features' => 
		array (
			'type' => 'text',
			'length' => '',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => 'CHARACTER SET utf8 COLLATE utf8_unicode_ci',
			'auto_inc' => 0,
		),
		'meta_keywords' => 
		array (
			'type' => 'text',
			'length' => '',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => 'CHARACTER SET utf8 COLLATE utf8_unicode_ci',
			'auto_inc' => 0,
		),
		'meta_desc' => 
		array (
			'type' => 'text',
			'length' => '',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => 'CHARACTER SET utf8 COLLATE utf8_unicode_ci',
			'auto_inc' => 0,
		),
		'external_url' => 
		array (
			'type' => 'varchar',
			'length' => '255',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => 'CHARACTER SET utf8 COLLATE utf8_unicode_ci',
			'auto_inc' => 0,
		),
		'cat_id' => 
		array (
			'type' => 'int',
			'length' => '11',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'model' => 
		array (
			'type' => 'varchar',
			'length' => '64',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => 'CHARACTER SET utf8 COLLATE utf8_unicode_ci',
			'auto_inc' => 0,
		),
		'sku' => 
		array (
			'type' => 'varchar',
			'length' => '64',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => 'CHARACTER SET utf8 COLLATE utf8_unicode_ci',
			'auto_inc' => 0,
		),
		'quantity' => 
		array (
			'type' => 'int',
			'length' => '10',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '100',
			'auto_inc' => 0,
		),
		'stock_status_id' => 
		array (
			'type' => 'int',
			'length' => '10',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'manufacturer_id' => 
		array (
			'type' => 'int',
			'length' => '10',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'supplier_id' => 
		array (
			'type' => 'int',
			'length' => '10',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'price' => 
		array (
			'type' => 'decimal',
			'length' => '8,2',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0.00',
			'auto_inc' => 0,
		),
		'price_promo' => 
		array (
			'type' => 'decimal',
			'length' => '8,2',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0.00',
			'auto_inc' => 0,
		),
		'price_partner' => 
		array (
			'type' => 'decimal',
			'length' => '8,2',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0.00',
			'auto_inc' => 0,
		),
		'price_raw' => 
		array (
			'type' => 'decimal',
			'length' => '8,2',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0.00',
			'auto_inc' => 0,
		),
		'old_price' => 
		array (
			'type' => 'decimal',
			'length' => '8,2',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0.00',
			'auto_inc' => 0,
		),
		'currency' => 
		array (
			'type' => 'tinyint',
			'length' => '3',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'add_date' => 
		array (
			'type' => 'int',
			'length' => '10',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'update_date' => 
		array (
			'type' => 'int',
			'length' => '10',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'last_viewed_date' => 
		array (
			'type' => 'int',
			'length' => '10',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'featured' => 
		array (
			'type' => 'tinyint',
			'length' => '1',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'active' => 
		array (
			'type' => 'tinyint',
			'length' => '1',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'viewed' => 
		array (
			'type' => 'int',
			'length' => '10',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'sold' => 
		array (
			'type' => 'int',
			'length' => '10',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'status' => 
		array (
			'type' => 'int',
			'length' => '10',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '0',
			'auto_inc' => 0,
		),
		'articul' => 
		array (
			'type' => 'varchar',
			'length' => '32',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'origin_url' => 
		array (
			'type' => 'varchar',
			'length' => '255',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
		'source' => 
		array (
			'type' => 'varchar',
			'length' => '255',
			'attrib' => NULL,
			'not_null' => 1,
			'default' => '',
			'auto_inc' => 0,
		),
	),
	'keys' => 
	array (
		'id' => 
		array (
			'fields' => 
			array (
				0 => 'id',
			),
			'type' => 'primary',
		),
		'cat_id' => 
		array (
			'fields' => 
			array (
				0 => 'cat_id',
			),
			'type' => 'key',
		),
		'active' => 
		array (
			'fields' => 
			array (
				0 => 'active',
			),
			'type' => 'key',
		),
		'viewed' => 
		array (
			'fields' => 
			array (
				0 => 'viewed',
			),
			'type' => 'key',
		),
		'sold' => 
		array (
			'fields' => 
			array (
				0 => 'sold',
			),
			'type' => 'key',
		),
		'active_cat_id' => 
		array (
			'fields' => 
			array (
				0 => 'active',
				1 => 'cat_id',
			),
			'type' => 'key',
		),
		'add_date' => 
		array (
			'fields' => 
			array (
				0 => 'add_date',
			),
			'type' => 'key',
		),
		'update_date' => 
		array (
			'fields' => 
			array (
				0 => 'update_date',
			),
			'type' => 'key',
		),
		'manufacturer_id' => 
		array (
			'fields' => 
			array (
				0 => 'manufacturer_id',
			),
			'type' => 'key',
		),
		'supplier_id' => 
		array (
			'fields' => 
			array (
				0 => 'supplier_id',
			),
			'type' => 'key',
		),
	),
);
