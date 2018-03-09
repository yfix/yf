<?php
return '
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `object_name` varchar(64) NOT NULL,
  `object_id` varchar(64) NOT NULL,
  `action` varchar(32) NOT NULL,
  `locale` varchar(10) NOT NULL,
  `data_old` text NOT NULL,
  `data_new` text NOT NULL,
  `date` datetime NOT NULL,
  `user_id` int(11) unsigned NOT NULL,
  `site_id` tinyint(3) unsigned NOT NULL DEFAULT \'0\',
  `server_id` tinyint(3) unsigned NOT NULL DEFAULT \'0\',
  `ip` char(15) NOT NULL,
  `url` varchar(255) NOT NULL,
  `user_agent` varchar(255) NOT NULL,
  `comment` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
';
