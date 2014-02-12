<?php
$data = '
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT \'\',
  `comment` text NOT NULL,
  `hostname` varchar(255) NOT NULL DEFAULT \'\',
  `ip` varchar(255) NOT NULL DEFAULT \'\',
  `role` varchar(255) NOT NULL DEFAULT \'worker\',
  `active` enum(\'1\',\'0\') NOT NULL DEFAULT \'1\',
  PRIMARY KEY (`id`)
';