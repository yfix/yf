<?php
$data = '
	`id` int(11) NOT NULL auto_increment,
	`name` varchar(32) NOT NULL default \'\',
	`code` varchar(32) NOT NULL default \'\',
	`country_code` char(2) NOT NULL default \'\',
	PRIMARY KEY	(`id`),
	UNIQUE KEY `code` (`code`),
	KEY `state` (`name`),
	KEY `country_code` (`country_code`)
';