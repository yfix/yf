<?php
return '
	`id` int(11) NOT NULL auto_increment,
	`user_id` int(11) NOT NULL default \'0\',
	`friend_id` int(11) NOT NULL default \'0\',
	`mask` int(11) NOT NULL default \'0\',
	PRIMARY KEY  (`id`)
';