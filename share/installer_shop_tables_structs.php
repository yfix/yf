<?php
$data = my_array_merge((array)$data, array(
"shop_group_options"	=> "
	`product_id` int(10) unsigned NOT NULL,
	`group_id` int(10) unsigned NOT NULL,
	`price` decimal(8,2) NOT NULL
",
"shop_product_related"	=> "
	`product_id` int(11) unsigned NOT NULL,
	`related_id` int(11) unsigned NOT NULL,
	PRIMARY KEY  (`product_id`,`related_id`)
",
"shop_product_to_category" => "
	`product_id` int(11) NOT NULL,
	`category_id` int(11) NOT NULL,
	PRIMARY KEY  (`product_id`,`category_id`)
",
"shop_products"	=> "
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`name` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '',
	`url` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '',
	`image` enum('0','1') CHARACTER SET utf8 NOT NULL DEFAULT '0',
	`description` text CHARACTER SET utf8 NOT NULL,
	`meta_keywords` text CHARACTER SET utf8 NOT NULL,
	`meta_desc` text CHARACTER SET utf8 NOT NULL,
	`external_url` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '',
	`cat_id` text CHARACTER SET utf8 NOT NULL,
	`model` varchar(64) CHARACTER SET utf8 NOT NULL,
	`sku` varchar(64) CHARACTER SET utf8 NOT NULL,
	`quantity` int(10) NOT NULL DEFAULT '0',
	`stock_status_id` int(10) NOT NULL DEFAULT '0',
	`manufacturer_id` int(10) NOT NULL DEFAULT '0',
	`supplier_id` int(10) NOT NULL DEFAULT '0',
	`price` decimal(8,2) NOT NULL DEFAULT '0.00',
	`price_promo` decimal(8,2) NOT NULL DEFAULT '0.00',
	`price_partner` decimal(8,2) NOT NULL DEFAULT '0.00',
	`price_raw` decimal(8,2) NOT NULL DEFAULT '0.00',
	`old_price` decimal(8,2) NOT NULL DEFAULT '0.00',
	`currency` tinyint(3) unsigned NOT NULL DEFAULT '0',
	`add_date` int(10) unsigned NOT NULL DEFAULT '0',
	`update_date` int(10) unsigned NOT NULL DEFAULT '0',
	`last_viewed_date` int(10) NOT NULL DEFAULT '0',
	`featured` enum('0','1') CHARACTER SET utf8 NOT NULL DEFAULT '0',
	`active` enum('0','1') CHARACTER SET utf8 NOT NULL DEFAULT '0',
	`viewed` int(10) NOT NULL DEFAULT '0',
	PRIMARY KEY (`id`)
",
"shop_manufacturers" => "
	`id` int(11) NOT NULL auto_increment,
	`name` varchar(64) NOT NULL default '',
	`url` varchar(255) NOT NULL,
	`desc` varchar(255) NOT NULL,
	`meta_keywords` text NOT NULL,
	`meta_desc` text NOT NULL,
	`image` int(10) unsigned NOT NULL,
	`sort_order` int(3) NOT NULL,
	PRIMARY KEY	(`id`)
",
"shop_suppliers" => "
	`id` int(11) NOT NULL auto_increment,
	`name` varchar(64) NOT NULL default '',
	`url` varchar(255) NOT NULL,
	`desc` varchar(255) NOT NULL,
	`meta_keywords` text NOT NULL,
	`meta_desc` text NOT NULL,
	`image` int(10) unsigned NOT NULL,
	`sort_order` int(3) NOT NULL,
	PRIMARY KEY	(`id`)
",
"shop_orders"	=> "
	`id` int(10) unsigned NOT NULL auto_increment,
	`user_id` int(11) unsigned NOT NULL default '0',
	`date` int(11) unsigned NOT NULL default '0',
	`ship_type` int(11) unsigned NOT NULL default '0',
	`pay_type` int(11) unsigned NOT NULL default '0',
	`total_sum` decimal(12,2) NOT NULL,
	`card_num` varchar(50) NOT NULL,
	`exp_date` varchar(4) NOT NULL,
	`name` varchar(32) NOT NULL,
	`email` varchar(50) NOT NULL,
	`phone` varchar(40) NOT NULL,
	`address` text NOT NULL,
	`comment_c` text NOT NULL,
	`comment_m` text NOT NULL,
	`hash` varchar(128) NOT NULL,
	`status` varchar(16) NOT NULL,
	PRIMARY KEY	(`id`)
",
"shop_order_items" => "
	`order_id` int(10) unsigned NOT NULL default '0',
	`product_id` int(10) unsigned NOT NULL default '0',
	`user_id` int(10) unsigned NOT NULL default '0',
	`quantity` int(10) unsigned NOT NULL default '0',
	`attributes` text NOT NULL default '',
	`sum` decimal(12,2) NOT NULL,
	KEY `order_id` (`order_id`)
",
"shop_product_attributes_values"	=> "
	`category_id` int(11) NOT NULL default '0',
	`object_name` varchar(50) NOT NULL default '',
	`object_id` int(11) NOT NULL default '0',
	`field_id` int(11) NOT NULL default '0',
	`value` text NOT NULL default '',
	`add_value` text NOT NULL default ''
",
"shop_product_attributes_info"	=> "
	`id` int(10) unsigned NOT NULL auto_increment,
	`category_id` int(11) NOT NULL default '0',
	`name` varchar(64) NOT NULL default '',
	`type` varchar(64) NOT NULL default '',
	`value_list` text NOT NULL,
	`default_value` text NOT NULL,
	`order` int(10) unsigned NOT NULL default '0',
	`active` enum('1','0') NOT NULL default '1',
	PRIMARY KEY	(`id`)
",
"shop_product_sets"	=> "
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`name` varchar(32) NOT NULL DEFAULT '',
	`desc` text NOT NULL,
	`add_date` int(11) NOT NULL DEFAULT '0',
	`active` enum('0','1') NOT NULL DEFAULT '0',
	PRIMARY KEY (`id`)
",
"shop_product_set_items"	=> "
	`set_id` int(10) unsigned NOT NULL DEFAULT '0',
	`product_id` int(10) unsigned NOT NULL DEFAULT '0',
	`quantity` int(10) unsigned NOT NULL DEFAULT '0',
	`attributes` text NOT NULL,
	`sum` decimal(12,2) NOT NULL,
	KEY `set_id_product_id` (`set_id`,`product_id`)
",
));
