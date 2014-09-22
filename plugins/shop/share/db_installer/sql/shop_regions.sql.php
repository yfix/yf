<?php
return '
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `value` varchar(255) NOT NULL DEFAULT \'\',
  `host` varchar(255) NOT NULL,
  `active` int(11) NOT NULL DEFAULT \'1\',
  PRIMARY KEY (`id`)
';