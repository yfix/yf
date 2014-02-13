<?php
$data = '
	`id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT,
	`name` varchar(255) NOT NULL DEFAULT \'\',
	`active` enum(\'1\',\'0\') NOT NULL DEFAULT \'1\',
	`go_after_login` varchar(255) NOT NULL DEFAULT \'\',
	PRIMARY KEY (`id`)
';