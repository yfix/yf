<?php
return '
	`code` char(2) NOT NULL DEFAULT \'\',
	`code3` char(3) NOT NULL DEFAULT \'\',
	`num` char(3) NOT NULL DEFAULT \'\',
	`name` varchar(64) NOT NULL DEFAULT \'\',
	`name_eng` varchar(64) NOT NULL DEFAULT \'\',
	`cont` char(2) NOT NULL DEFAULT \'\',
	`tld` char(2) NOT NULL DEFAULT \'\',
	`currency` char(3) NOT NULL DEFAULT \'\',
	`area` int(10) unsigned NOT NULL DEFAULT \'0\',
	`population` int(10) unsigned NOT NULL DEFAULT \'0\',
	`phone_prefix` char(10) NOT NULL DEFAULT \'\',
	`languages` varchar(256) NOT NULL DEFAULT \'\',
	`geoname_id` int(10) NOT NULL DEFAULT \'0\',
	`capital_id` int(10) NOT NULL DEFAULT \'0\',
	`active` enum(\'0\',\'1\') NOT NULL DEFAULT \'0\',
	PRIMARY KEY (`code`)
';