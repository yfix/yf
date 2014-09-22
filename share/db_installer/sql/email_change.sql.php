<?php
return '
	`id` int(10) unsigned NOT NULL auto_increment,
	`user_id` int(10) unsigned NOT NULL default \'0\',
	`old_mail` varchar(50) NOT NULL default \'0\',
	`new_mail` varchar(50) NOT NULL default \'0\',
	`time` int(10) unsigned NOT NULL default \'0\',
	PRIMARY KEY (`id`)
';