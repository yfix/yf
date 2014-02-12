<?php
$data = '
	`id` int(10) unsigned NOT NULL auto_increment,
	`name` varchar(32) NOT NULL,
	`descr` text NOT NULL,
	`active` enum(\'0\',\'1\') NOT NULL,
	`tags` varchar(255) NOT NULL,
	PRIMARY KEY  (`id`)
';