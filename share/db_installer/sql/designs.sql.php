<?php
$data = '
	`id` int(10) unsigned NOT NULL auto_increment,
	`name` varchar(32) NOT NULL,
	`theme_id` int(11) NOT NULL,
	`active` enum(\'0\',\'1\') NOT NULL,
	`tags` varchar(255) NOT NULL,
	`owner_id` int(10) unsigned NOT NULL,
	`css` text NOT NULL,
	PRIMARY KEY  (`id`)
';