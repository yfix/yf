<?php
$data = '
	`id` int(10) unsigned NOT NULL auto_increment,
	`name` varchar(64) NOT NULL,
	`type` varchar(64) NOT NULL,
	`value_list` text NOT NULL,
	`default_value` text NOT NULL,
	`comment` text NOT NULL,
	`order` int(10) unsigned NOT NULL,
	`required` enum(\'0\',\'1\') NOT NULL,
	`display_on` text NOT NULL,
	`active` enum(\'1\',\'0\') NOT NULL,
	PRIMARY KEY  (`id`)
';