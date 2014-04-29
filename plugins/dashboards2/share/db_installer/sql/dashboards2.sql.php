<?php

$data = '
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT \'\',
  `data` text NOT NULL,
  `type` enum(\'user\',\'admin\') NOT NULL,
  `active` enum(\'1\',\'0\') NOT NULL DEFAULT \'1\',
  `lock` enum(\'1\',\'0\') NOT NULL DEFAULT \'0\',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
';