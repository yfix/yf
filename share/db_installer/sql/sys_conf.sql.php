<?php
$data = '
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	`name` varchar(255) NOT NULL,
	`value` text NOT NULL,
	`desc` text NOT NULL,
	`active` enum(\'1\',\'0\') NOT NULL DEFAULT \'1\',
	`linked_table` varchar(255) NOT NULL,
	`linked_data` varchar(255) NOT NULL,
	`linked_method` varchar(255) NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `name` (`name`)
';