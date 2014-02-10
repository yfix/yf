<?php
$data = '
	`id` smallint(5) unsigned NOT NULL auto_increment,
	`code` varchar(50) default NULL,
	`url` varchar(100) default NULL,
	`emoticon` varchar(75) default NULL,
	`emo_set` tinyint(3) unsigned NOT NULL default \'1\',
	PRIMARY KEY  (`id`)
';