<?php
$data = '
	`id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
	`name` varchar(255) NOT NULL DEFAULT \'\',
	`description` varchar(255) NOT NULL DEFAULT \'\',
	`version` varchar(16) NOT NULL DEFAULT \'\',
	`author` varchar(255) NOT NULL DEFAULT \'\',
	`active` enum(\'0\',\'1\') NOT NULL DEFAULT \'0\',
	PRIMARY KEY (`id`)
';