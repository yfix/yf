<?php
return '
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `banner_id` int(10) unsigned NOT NULL DEFAULT \'0\',
  `date` int(10) unsigned NOT NULL DEFAULT \'0\',
  `action` varchar(16) NOT NULL,
  `session_id` varchar(32) NOT NULL DEFAULT \'\',
  `ip` varchar(16) NOT NULL DEFAULT \'\',
  `user_agent` varchar(255) NOT NULL DEFAULT \'\',
  PRIMARY KEY (`id`),
  KEY `ip` (`ip`),
  KEY `banner_id` (`banner_id`),
  KEY `date` (`date`)
  /** ENGINE=InnoDB DEFAULT CHARSET=utf8 **/
';
