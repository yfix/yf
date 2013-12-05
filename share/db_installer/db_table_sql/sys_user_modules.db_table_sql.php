<?php
$data = '
	`id` tinyint(3) unsigned NOT NULL auto_increment,
	`name` varchar(255) NOT NULL default \'\',
	`description` VARCHAR( 255 ) NOT NULL default \'\',
	`version` VARCHAR( 16 ) NOT NULL default \'\',
	`author` VARCHAR( 255 ) NOT NULL default \'\',
	`active` ENUM( \'0\', \'1\' ) NOT NULL default \'0\',
	PRIMARY KEY (`id`)
';