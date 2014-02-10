<?php
$data = '
	`id` int(10) unsigned NOT NULL auto_increment,
	`name` varchar(255) NOT NULL default \'\',
	`comment` text NOT NULL default \'\',
	`hostname` varchar(255) NOT NULL default \'\',
	`ip` varchar(255) NOT NULL default \'\',
	`role` varchar(255) NOT NULL default \'worker\',
	`active` enum(\'1\',\'0\') NOT NULL default \'1\',
	PRIMARY KEY (`id`)
';