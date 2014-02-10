<?php
$data = '
	`id` int(5) unsigned zerofill NOT NULL,
	`lon` float NOT NULL default \'0\',
	`lat` float NOT NULL default \'0\'
	UNIQUE KEY `id` (`id`),
	KEY `lon` (`lon`,`lat`)
';