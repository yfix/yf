<?php
return '
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT \'0\',
  `login` varchar(255) NOT NULL DEFAULT \'\',
  `group` int(10) unsigned NOT NULL DEFAULT \'0\',
  `date` int(10) unsigned NOT NULL DEFAULT \'0\',
  `session_id` varchar(32) NOT NULL DEFAULT \'\',
  `ip` varchar(16) NOT NULL DEFAULT \'\',
  `user_agent` varchar(255) NOT NULL DEFAULT \'\',
  `referer` varchar(255) NOT NULL DEFAULT \'\',
  `activity` int(10) unsigned NOT NULL DEFAULT \'0\',
  `site_id` tinyint(3) unsigned NOT NULL DEFAULT \'0\',
  `server_id` tinyint(3) unsigned NOT NULL DEFAULT \'0\',
  PRIMARY KEY (`id`),
  KEY `ip` (`ip`),
  KEY `user_id` (`user_id`),
  KEY `date` (`date`)
';
