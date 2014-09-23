<?php
return '
	`id` int(11) NOT NULL auto_increment,
	`id2` tinyint(4) NOT NULL default \'0\',
	`user_id` int(11) NOT NULL default \'0\',
	`title` text character set utf8 NOT NULL,
	`order` int(11) NOT NULL default \'0\',
	PRIMARY KEY  (`id`),
	KEY `id2` (`id2`)
';