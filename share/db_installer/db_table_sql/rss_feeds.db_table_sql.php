<?php
$data = '
	`id` int(10) unsigned NOT NULL auto_increment,
	`url` varchar(255) character set utf8 NOT NULL default \'\',
	`name` varchar(255) character set utf8 NOT NULL default \'\',
	`title` varchar(255) character set utf8 NOT NULL default \'\',
	`desc` text character set utf8 NOT NULL,
	`order` int(10) unsigned NOT NULL default \'0\',
	`ttl` int(10) unsigned NOT NULL default \'0\',
	`active` enum(\'1\',\'0\') NOT NULL default \'1\',
	`last_checked` int(10) unsigned NOT NULL default \'0\',
	`last_modified` int(10) unsigned NOT NULL default \'0\',
	`etag` text NOT NULL,
	`image` text NOT NULL,
	`aggregate_info` text NOT NULL,
	PRIMARY KEY  (`id`)		
';