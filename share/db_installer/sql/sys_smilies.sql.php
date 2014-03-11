<?php
$data = '
	`id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
	`code` varchar(50) DEFAULT NULL,
	`url` varchar(100) DEFAULT NULL,
	`emoticon` varchar(75) DEFAULT NULL,
	`emo_set` tinyint(3) unsigned NOT NULL DEFAULT \'1\',
	PRIMARY KEY (`id`)
';