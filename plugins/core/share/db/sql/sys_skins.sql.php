<?php
return '
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL DEFAULT \'\',
  `desc` varchar(255) NOT NULL DEFAULT \'\',
  `for_admin` enum(\'0\',\'1\') NOT NULL DEFAULT \'0\',
  `for_user` enum(\'1\',\'0\') NOT NULL DEFAULT \'1\',
  `active` enum(\'0\',\'1\') NOT NULL DEFAULT \'0\',
  PRIMARY KEY (`id`)
';
