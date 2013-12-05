<?php
$data = '
	`id` int(10) unsigned NOT NULL auto_increment,
	`user_id` int(10) unsigned NOT NULL,
	`date` int(10) unsigned NOT NULL,
	`title` varchar(255) NOT NULL,
	`desc` text NOT NULL,
	`status` tinyint(3) unsigned NOT NULL,
	`hours` text NOT NULL,
	PRIMARY KEY	(`id`),
	UNIQUE KEY `user_id_2` (`user_id`,`date`),
	KEY `user_id` (`user_id`)
';