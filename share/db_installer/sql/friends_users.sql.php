<?php
return '
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT \'0\',
  `friend_id` int(11) NOT NULL DEFAULT \'0\',
  `mask` int(11) NOT NULL DEFAULT \'0\',
  PRIMARY KEY (`id`)
';
