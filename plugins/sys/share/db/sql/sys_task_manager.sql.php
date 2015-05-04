<?php
return '
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT \'\',
  `file` varchar(255) NOT NULL DEFAULT \'\',
  `php_code` text NOT NULL DEFAULT \'\',
  `next_run` int(10) NOT NULL DEFAULT \'0\',
  `week_day` tinyint(1) NOT NULL DEFAULT \'-1\',
  `month_day` smallint(2) NOT NULL DEFAULT \'-1\',
  `hour` smallint(2) NOT NULL DEFAULT \'-1\',
  `minute` smallint(2) NOT NULL DEFAULT \'-1\',
  `cronkey` varchar(32) NOT NULL DEFAULT \'\',
  `log` tinyint(1) NOT NULL DEFAULT \'0\',
  `description` text NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT \'1\',
  `key` varchar(30) NOT NULL DEFAULT \'\',
  `safemode` tinyint(1) NOT NULL DEFAULT \'0\',
  PRIMARY KEY (`id`),
  KEY `task_next_run` (`next_run`)
';
