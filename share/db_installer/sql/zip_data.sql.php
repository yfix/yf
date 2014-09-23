<?php
return '
	`id` int(5) unsigned zerofill NOT NULL,
	`lon` float NOT NULL default \'0\',
	`lat` float NOT NULL default \'0\',
	PRIMARY KEY (`id`),
	KEY `lon` (`lon`,`lat`)
';