<?php
return '
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT \'\',
  `text` text NOT NULL CHARACTER SET utf8,
  `type` tinyint(1) NOT NULL DEFAULT \'1\',
  `active` enum(\'1\',\'0\') NOT NULL DEFAULT \'1\',
  `locale` char(7) NOT NULL DEFAULT \'en\',
  PRIMARY KEY (`id`)
';
