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
	`id` int(10) unsigned NOT NULL auto_increment,
	`name` varchar(255) NOT NULL default '',
	`url` varchar(255) NOT NULL default '',
	`image` enum('0','1') NOT NULL,
	`description` text NOT NULL,
	`meta_keywords` text NOT NULL,
	`meta_desc` text NOT NULL,
	`external_url` varchar(255) NOT NULL default '',
	`cat_id` text NOT NULL,
	`model` varchar(64) NOT NULL,
	`sku` varchar(64) NOT NULL,
	`quantity` int(4) NOT NULL default '0',
	`stock_status_id` int(11) NOT NULL,
	`manufacturer_id` int(11) NOT NULL,
	`price` decimal(8,2) NOT NULL default '0.00',
	`old_price` decimal(8,2) NOT NULL default '0.00',
	`currency` int(10) unsigned NOT NULL default '0',
	`add_date` int(10) unsigned NOT NULL default '0',
	`last_viewed_date` int(10) NOT NULL,
	`featured` enum('0','1') NOT NULL default '0',
	`active` enum('0','1') NOT NULL,
	`viewed` int(5) NOT NULL default '0',
	PRIMARY KEY  (`id`)
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
	PRIMARY KEY  (`id`)
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
	PRIMARY KEY  (`id`)
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
	PRIMARY KEY  (`id`)
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
));
