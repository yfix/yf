<?php
return '
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `object` text NOT NULL,
  `action` text NOT NULL,
  `theme` text NOT NULL,
  `comments` text NOT NULL,
  `columns` text NOT NULL,
  `site_ids` varchar(255) NOT NULL,
  `server_ids` varchar(255) NOT NULL,
  `active` enum(\'1\',\'0\') NOT NULL DEFAULT \'1\',
  `data` text NOT NULL,
  PRIMARY KEY (`id`)
';
