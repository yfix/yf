<?php
return '
	`id` int(10) unsigned NOT NULL auto_increment,
	`photo_id` int(10) unsigned NOT NULL,
	`user_id` int(10) unsigned NOT NULL,
	`target_user_id` int(10) unsigned NOT NULL,
	`add_date` int(10) unsigned NOT NULL,
	`voted` int(11) NOT NULL,
	`counted` int(11) NOT NULL,
	`ip` varchar(16) NOT NULL,
	`comment` varchar(255) NOT NULL,
	`active` enum(\'1\',\'0\') NOT NULL,
	PRIMARY KEY	(`id`)
';