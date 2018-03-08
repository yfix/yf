<?php
return '
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `theme_name` varchar(32) NOT NULL DEFAULT \'\',
  `name` varchar(128) NOT NULL DEFAULT \'\',
  `text` longtext NOT NULL,
  `site_id` tinyint(3) unsigned NOT NULL DEFAULT \'0\',
  `language` tinyint(3) unsigned NOT NULL DEFAULT \'0\',
  `active` enum(\'1\',\'0\') NOT NULL DEFAULT \'1\',
  PRIMARY KEY (`id`),
  KEY `theme_name_1` (`theme_name`,`name`,`active`)
';
