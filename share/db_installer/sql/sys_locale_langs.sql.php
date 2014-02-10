<?php
$data = '
	`id` int(10) unsigned NOT NULL auto_increment,
	`locale` varchar(12) NOT NULL default \'\',
	`name` varchar(64) NOT NULL default \'\',
	`charset` varchar(32) NOT NULL,
	`active` enum(\'1\',\'0\') NOT NULL default \'0\',
	`is_default` enum(\'0\',\'1\') NOT NULL default \'0\',
	PRIMARY KEY (`id`),
	UNIQUE KEY `locale` (`locale`)
';