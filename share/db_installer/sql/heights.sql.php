<?php
$data = '
	`id` tinyint(4) NOT NULL default \'0\',
	`height` varchar(50) NOT NULL default \'\',
	PRIMARY KEY	(`id`),
	UNIQUE KEY `id_2` (`id`),
	KEY `id` (`id`)
';