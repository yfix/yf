<?php
$data = '
	`id` int(10) unsigned NOT NULL auto_increment,
	`name` varchar(255) character set utf8 NOT NULL,
	`locale` char(7) NOT NULL default \'en\',
	PRIMARY KEY  (`id`)
';