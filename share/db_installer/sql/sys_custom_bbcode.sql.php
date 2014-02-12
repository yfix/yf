<?php
$data = '
	`id` int(10) NOT NULL auto_increment,
	`title` varchar(255) NOT NULL default \'\',
	`desc` text NOT NULL,
	`tag` varchar(255) NOT NULL default \'\',
	`replace` text NOT NULL,
	`useoption` tinyint(1) NOT NULL default \'0\',
	`example` text NOT NULL,
	PRIMARY KEY  (`id`)
';