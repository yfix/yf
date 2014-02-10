<?php
$data = '
	`id` int(10) unsigned NOT NULL auto_increment,
	`name` varchar(255) NOT NULL default \'\',
	`web_path` varchar(255) NOT NULL default \'\',
	`real_path` varchar(255) NOT NULL default \'\',
	`active` enum(\'1\',\'0\') NOT NULL default \'1\',
	`vertical` char(5) NOT NULL default \'homes\',
	`locale` char(7) NOT NULL default \'en\',
	`country` char(2) NOT NULL,
	PRIMARY KEY (`id`)
';