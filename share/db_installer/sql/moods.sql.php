<?php
return '
	`id` smallint(5) unsigned NOT NULL auto_increment,
	`name` varchar(255) CHARACTER SET utf8 NOT NULL default \'\',
	`active` enum(\'1\',\'0\') NOT NULL default \'1\',
	`locale` char(7) DEFAULT \'en\' NOT NULL, 
	PRIMARY KEY	(`id`)
';