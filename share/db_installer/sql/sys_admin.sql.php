<?php
return '
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(64) NOT NULL DEFAULT \'\',
  `last_name` varchar(64) NOT NULL DEFAULT \'\',
  `login` varchar(64) NOT NULL DEFAULT \'\',
  `password` varchar(64) NOT NULL DEFAULT \'\',
  `group` int(10) unsigned NOT NULL DEFAULT \'0\',
  `active` enum(\'0\',\'1\') NOT NULL DEFAULT \'0\',
  `add_date` int(10) unsigned NOT NULL DEFAULT \'0\',
  `last_login` int(10) unsigned NOT NULL DEFAULT \'0\',
  `num_logins` smallint(6) unsigned NOT NULL DEFAULT \'0\',
  `go_after_login` varchar(255) NOT NULL DEFAULT \'\',
  `email` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `login` (`login`)
';
