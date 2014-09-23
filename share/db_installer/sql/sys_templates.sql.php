<?php
return '
	`id` int(10) unsigned NOT NULL auto_increment,
	`theme_name` varchar(32) NOT NULL default \'\',
	`name` varchar(128) NOT NULL default \'\',
	`text` longtext NOT NULL,
	`site_id` tinyint(3) unsigned NOT NULL default \'0\',
	`language` tinyint(3) unsigned NOT NULL default \'0\',
	`active` enum(\'1\',\'0\') NOT NULL default \'1\',
	PRIMARY KEY (`id`),
	KEY `theme_name_1` (`theme_name`,`name`,`active`)
';