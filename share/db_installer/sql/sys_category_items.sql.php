<?php
$data = '
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`cat_id` int(10) unsigned NOT NULL,
	`parent_id` int(10) unsigned NOT NULL,
	`name` varchar(255) NOT NULL,
	`desc` text NOT NULL,
	`meta_keywords` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
	`meta_desc` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
	`url` varchar(255) NOT NULL,
	`type_id` int(10) unsigned NOT NULL,
	`order` int(10) unsigned NOT NULL,
	`active` enum(\'1\',\'0\') NOT NULL,
	`restricted` tinyint(1) unsigned NOT NULL DEFAULT \'0\',
	`user_groups` varchar(255) NOT NULL,
	`other_info` text NOT NULL,
	`icon` varchar(255) NOT NULL,
	`image` enum(\'1\',\'0\') NOT NULL DEFAULT \'0\',
	`featured` tinyint(3) unsigned NOT NULL,
	`hide` tinyint(3) unsigned NOT NULL DEFAULT \'0\',
	`filter` tinyint(3) unsigned NOT NULL DEFAULT \'0\',
	`origin_url` varchar(255) NOT NULL,
	`force_wo_image` tinyint(3) unsigned NOT NULL DEFAULT \'0\',
	PRIMARY KEY (`id`)
';
/*
,
	FOREIGN KEY (`cat_id`) REFERENCES `{db_prefix}sys_categories` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
	FOREIGN KEY (`parent_id`) REFERENCES `{db_prefix}sys_category_items` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
*/