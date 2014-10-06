<?php
return '
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `productparams_id` int(11) NOT NULL DEFAULT \'0\',
  `title` varchar(100) NOT NULL DEFAULT \'\',
  `sort` varchar(10) NOT NULL DEFAULT \'\',
  PRIMARY KEY (`id`),
  KEY `productparams_id` (`productparams_id`)
';
