<?php
return '
  `id` mediumint(7) NOT NULL AUTO_INCREMENT,
  `sender` int(10) unsigned NOT NULL,
  `receiver` int(10) unsigned NOT NULL,
  `s_folder_id` tinyint(3) unsigned NOT NULL,
  `r_folder_id` tinyint(3) unsigned NOT NULL,
  `subject` varchar(255) NOT NULL DEFAULT \'\',
  `message` text NOT NULL,
  `time` int(10) unsigned NOT NULL DEFAULT \'0\',
  `r_read_time` int(10) unsigned NOT NULL,
  `sender_ip` varchar(16) NOT NULL,
  `activity` int(10) unsigned NOT NULL DEFAULT \'0\',
  PRIMARY KEY (`id`),
  KEY `receiver` (`receiver`),
  KEY `sender` (`sender`),
  KEY `r_read_time` (`r_read_time`)
';
