<?php
return '
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`name` varchar(255) NOT NULL DEFAULT \'\',
	`web_path` varchar(255) NOT NULL DEFAULT \'\',
	`real_path` varchar(255) NOT NULL DEFAULT \'\',
	`active` enum(\'1\',\'0\') NOT NULL DEFAULT \'1\',
	`vertical` char(5) NOT NULL DEFAULT \'homes\',
	`locale` char(7) NOT NULL DEFAULT \'en\',
	`country` char(2) NOT NULL,
	PRIMARY KEY (`id`)
';