<?php
$data = '
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
	`name` VARCHAR( 255 ) NOT NULL ,
	`description` TEXT NOT NULL ,
	`css` TEXT NOT NULL ,
	`active` ENUM( \'1\', \'0\' ) NOT NULL ,
	PRIMARY KEY ( `id` ) 
';