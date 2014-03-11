<?php
$data = '
	`id` int(10) unsigned NOT NULL auto_increment,
	`user_id` int(10) unsigned NOT NULL,
	`target_user_id` int(10) unsigned NOT NULL,
	`add_date` int(10) unsigned NOT NULL,
	`voted` int(11) NOT NULL,
	`ip` varchar(16) NOT NULL,
	`comment` varchar(255) NOT NULL,
	`active` enum(\'1\',\'0\') NOT NULL,
	`country_match` enum(\'0\',\'1\') NOT NULL,
	`counted` int(11) NOT NULL,
	`penalty` int(11) NOT NULL,
	`same_voter` enum(\'0\',\'1\') NOT NULL,
	`activity` int(10) unsigned NOT NULL default \'0\',
	`object_name` varchar(64) NOT NULL,
	`object_id` int(10) unsigned NOT NULL,
	PRIMARY KEY	(`id`),
	KEY `user_id` (`user_id`)
';