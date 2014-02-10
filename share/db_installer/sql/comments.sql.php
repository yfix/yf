<?php
$data = '
	`id` int(10) unsigned NOT NULL auto_increment,
	`object_name` varchar(24) NOT NULL,
	`object_id` int(10) unsigned NOT NULL default \'0\',
	`parent_id` int(10) unsigned NOT NULL default \'0\',
	`user_id` int(10) unsigned NOT NULL default \'0\',
	`user_name` varchar(255) NOT NULL default \'\',
	`user_email` varchar(128) character set utf8 NOT NULL default \'\',
	`add_date` int(10) unsigned NOT NULL default \'0\',
	`text` text NOT NULL,
	`ip` varchar(15) NOT NULL default \'\',
	`active` tinyint(1) unsigned NOT NULL default \'0\',
	`activity` int(10) unsigned NOT NULL default \'0\',
	PRIMARY KEY	(`id`),
	KEY `object_name` (`object_name`),
	KEY `object_id` (`object_id`),
	KEY `user_id` (`user_id`)
';