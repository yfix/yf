<?php
$data = '
	`id` int(10) unsigned NOT NULL,
	`country` char(2) NOT NULL,
	`code` char(2) NOT NULL,
	`name` varchar(255) NOT NULL,
	`capital_id` int(10) unsigned NOT NULL,
	PRIMARY KEY (`id`),
	KEY `country` (`country`),
	KEY `code` (`code`)
';