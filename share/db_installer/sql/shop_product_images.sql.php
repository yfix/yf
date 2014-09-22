<?php
return '
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL DEFAULT \'0\',
  `is_default` tinyint(11) NOT NULL DEFAULT \'0\',
  `md5` varchar(32) CHARACTER SET utf8 NOT NULL,
  `date_uploaded` int(11) NOT NULL DEFAULT \'0\',
  `active` tinyint(1) unsigned NOT NULL DEFAULT \'1\',
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`)
';