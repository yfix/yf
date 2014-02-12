<?php
$data = '
	`id` int(10) unsigned NOT NULL auto_increment,
	`name` varchar(255) NOT NULL default \'\',
	`text` text CHARACTER SET utf8 NOT NULL,
	`type` tinyint(1) NOT NULL default \'1\',
	`active` enum(\'1\',\'0\') NOT NULL default \'1\',
	`locale` char(7) DEFAULT \'en\' NOT NULL, 
	PRIMARY KEY	(`id`)
';