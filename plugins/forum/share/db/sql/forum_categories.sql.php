<?php
return '
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent` int(10) unsigned NOT NULL DEFAULT \'0\',
  `name` varchar(255) NOT NULL DEFAULT \'\',
  `desc` varchar(255) NOT NULL DEFAULT \'\',
  `status` char(1) NOT NULL DEFAULT \'a\',
  `active` tinyint(1) NOT NULL DEFAULT \'1\',
  `order` int(10) unsigned NOT NULL DEFAULT \'0\',
  `icon` varchar(255) NOT NULL DEFAULT \'\',
  `language` varchar(12) NOT NULL DEFAULT \'0\',
  PRIMARY KEY (`id`)
';
