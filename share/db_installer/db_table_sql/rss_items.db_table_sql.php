<?php
$data = '
	`id` int(10) unsigned NOT NULL auto_increment,
	`feed_id` int(10) unsigned NOT NULL default \'0\',
	`add_date` int(10) unsigned NOT NULL default \'0\',
	`link` varchar(255) character set utf8 NOT NULL default \'\',
	`title` varchar(255) character set utf8 NOT NULL default \'\',
	`text` text character set utf8 NOT NULL,
	`pub_date` int(10) unsigned NOT NULL default \'0\',
	`author` varchar(255) character set utf8 NOT NULL default \'\',
	`cache_md5` varchar(32) NOT NULL default \'\',
	`guid` varchar(255) character set utf8 NOT NULL default \'\',
	`aggregate_saved` varchar(255) NOT NULL default \'\',
	PRIMARY KEY  (`id`),
	UNIQUE KEY `cache_md5` (`cache_md5`)
';