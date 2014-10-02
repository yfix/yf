<?php
return '
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(50) NOT NULL DEFAULT \'\',
  `min` mediumint(8) NOT NULL DEFAULT \'0\',
  `special` tinyint(1) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `language` varchar(12) NOT NULL DEFAULT \'0\',
  PRIMARY KEY (`id`)
';
