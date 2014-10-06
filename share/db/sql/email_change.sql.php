<?php
return '
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT \'0\',
  `old_mail` varchar(50) NOT NULL DEFAULT \'0\',
  `new_mail` varchar(50) NOT NULL DEFAULT \'0\',
  `time` int(10) unsigned NOT NULL DEFAULT \'0\',
  PRIMARY KEY (`id`)
';
