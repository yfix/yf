<?php
return '
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT \'\',
  `post` text NOT NULL,
  `forum` text NOT NULL,
  `author_id` mediumint(8) unsigned NOT NULL DEFAULT \'0\',
  `html_enabled` tinyint(1) NOT NULL DEFAULT \'0\',
  `views` int(10) unsigned NOT NULL DEFAULT \'0\',
  `start_time` int(10) unsigned NOT NULL DEFAULT \'0\',
  `end_time` int(10) unsigned NOT NULL DEFAULT \'0\',
  `active` tinyint(1) NOT NULL DEFAULT \'1\',
  PRIMARY KEY (`id`)
';
