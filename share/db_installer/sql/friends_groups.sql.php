<?php
return '
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id2` tinyint(4) NOT NULL DEFAULT \'0\',
  `user_id` int(11) NOT NULL DEFAULT \'0\',
  `title` set(\'\') NOT NULL CHARACTER SET utf8,
  `order` int(11) NOT NULL DEFAULT \'0\',
  PRIMARY KEY (`id`),
  KEY `id2` (`id2`)
';
