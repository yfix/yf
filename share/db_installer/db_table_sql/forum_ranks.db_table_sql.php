<?php
$data = '
	`id` smallint(5) unsigned NOT NULL auto_increment,
	`title` varchar(50) NOT NULL default \'\',
	`min` mediumint(8) NOT NULL default \'0\',
	`special` tinyint(1) default NULL,
	`image` varchar(255) default NULL,
	`language` varchar(12) NOT NULL default \'0\',
	PRIMARY KEY	(`id`)
';