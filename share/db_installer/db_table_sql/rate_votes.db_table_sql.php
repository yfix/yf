<?php
$data = '
	`id` int(10) unsigned NOT NULL auto_increment,
	`user_id` int(10) unsigned NOT NULL,
	`rate_id` int(10) unsigned NOT NULL,
	`add_date` int(10) unsigned NOT NULL,
	`value` int(11) NOT NULL,
	`ip` varchar(16) NOT NULL,
	`comment` varchar(255) NOT NULL,
	`active` enum(\'1\',\'0\') NOT NULL,
	PRIMARY KEY	(`id`),
	KEY `user_id` (`user_id`)
';