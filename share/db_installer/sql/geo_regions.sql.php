<?php
$data = '
	`id` int(10) unsigned NOT NULL,
	`country` char(2) NOT NULL,
	`code` char(2) NOT NULL,
	`name` varchar(255) NOT NULL,
	`name_eng` varchar(255) NOT NULL,
	`capital_id` int(10) unsigned NOT NULL,
	`active` enum(\'0\',\'1\') NOT NULL DEFAULT \'0\',
	PRIMARY KEY (`id`),
	KEY `country` (`country`),
	KEY `code` (`code`)
';