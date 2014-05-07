<?php
$data = '
	`id` int(10) unsigned NOT NULL auto_increment,
	`parent` int(10) unsigned NOT NULL default \'0\',
	`name` varchar(255) NOT NULL default \'\',
	`desc` varchar(255) NOT NULL default \'\',
	`status` char(1) NOT NULL default \'a\',
	`active` tinyint(1) NOT NULL default \'1\',
	`order` int(10) unsigned NOT NULL default \'0\',
	`icon` varchar(255) NOT NULL default \'\',
	`language` varchar(12) NOT NULL default \'0\',
	PRIMARY KEY	(`id`)
';