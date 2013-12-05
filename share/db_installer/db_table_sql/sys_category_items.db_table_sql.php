<?php
$data = '
	`id` int(10) unsigned NOT NULL auto_increment,
	`cat_id` int(10) unsigned NOT NULL,
	`parent_id` int(10) unsigned NOT NULL,
	`name` varchar(255) NOT NULL,
	`desc` varchar(255) NOT NULL,
	`meta_keywords` text collate utf8_unicode_ci NOT NULL,
	`meta_desc` text collate utf8_unicode_ci NOT NULL,
	`url` varchar(255) NOT NULL,
	`type_id` int(10) unsigned NOT NULL,
	`order` int(10) unsigned NOT NULL,
	`active` enum(\'1\',\'0\') NOT NULL,
	`user_groups` varchar(255) NOT NULL,
	`other_info` text NOT NULL,
	`icon` varchar(255) NOT NULL,
	`featured` tinyint(3) unsigned NOT NULL,
	PRIMARY KEY  (`id`)
';