<?php
return '
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `microtime` decimal(13,3) unsigned NOT NULL DEFAULT \'0.000\',
  `server_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` text NOT NULL,
  PRIMARY KEY (`id`)
';
