<?php
$data = '
	`id` int(10) unsigned NOT NULL auto_increment,
	`item` varchar(255) NOT NULL default \'\',
	`value` text NOT NULL,
	`type` enum(\'text\',\'enum\',\'char\',\'date\') NOT NULL default \'text\',
	`size` varchar(255) NOT NULL default \'\',
	`debug` enum(\'0\',\'1\') NOT NULL default \'0\',
	`order` int(10) unsigned NOT NULL default \'0\',
	`category` int(10) unsigned NOT NULL default \'0\',
	PRIMARY KEY (`id`),
	UNIQUE KEY `item` (`item`)
';