<?php
return '
	`id` int(10) unsigned NOT NULL auto_increment,
	`object_name` varchar(255) NOT NULL,
	`object_id` int(10) unsigned NOT NULL default \'0\',
	`user_id` int(10) unsigned NOT NULL default \'0\',
	`question` varchar(255) NOT NULL,
	`add_date` int(10) unsigned NOT NULL default \'0\',
	`choices` text NOT NULL,
	`votes` smallint(5) unsigned NOT NULL default \'0\',
	`multiple` enum(\'0\',\'1\') NOT NULL default \'0\',
	`active` enum(\'1\',\'0\') NOT NULL default \'1\',
	PRIMARY KEY	(`id`),
	KEY `object_name` (`object_name`),
	KEY `object_id` (`object_id`)
';