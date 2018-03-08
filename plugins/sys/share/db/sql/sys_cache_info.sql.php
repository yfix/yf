<?php
return '
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `object` varchar(32) NOT NULL DEFAULT \'\',
  `action` varchar(32) NOT NULL DEFAULT \'\',
  `query_string` varchar(128) NOT NULL DEFAULT \'\',
  `site_id` tinyint(3) unsigned NOT NULL DEFAULT \'0\',
  `group_id` tinyint(3) unsigned NOT NULL DEFAULT \'1\',
  `hash` varchar(32) NOT NULL DEFAULT \'\',
  PRIMARY KEY (`id`),
  UNIQUE KEY `object` (`object`,`action`,`query_string`,`site_id`)
';
