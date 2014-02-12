<?php
$data = '
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `item` varchar(255) NOT NULL DEFAULT \'\',
  `value` text NOT NULL,
  `type` enum(\'text\',\'enum\',\'char\',\'date\') NOT NULL DEFAULT \'text\',
  `size` varchar(255) NOT NULL DEFAULT \'\',
  `debug` enum(\'0\',\'1\') NOT NULL DEFAULT \'0\',
  `order` int(10) unsigned NOT NULL DEFAULT \'0\',
  `category` int(10) unsigned NOT NULL DEFAULT \'0\',
  PRIMARY KEY (`id`),
  UNIQUE KEY `item` (`item`)
';