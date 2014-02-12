<?php
$data = '
	`id` int(10) unsigned NOT NULL auto_increment,
	`object_name` varchar(24) NOT NULL,
	`object_id` int(10) unsigned NOT NULL default \'0\',
	`owner_id` int(10) unsigned NOT NULL default \'0\',
	`owner_name` varchar(255) NOT NULL,
	`add_date` int(10) unsigned NOT NULL default \'0\',
	`last_vote_date` int(10) unsigned NOT NULL default \'0\',
	`num_votes` int(10) unsigned NOT NULL,
	`votes_sum` int(10) unsigned NOT NULL,
	`active` tinyint(1) unsigned NOT NULL default \'0\',
	`other_info` varchar(255) NOT NULL,
	PRIMARY KEY	(`id`),
	KEY `object_name` (`object_name`),
	KEY `object_id` (`object_id`)
';