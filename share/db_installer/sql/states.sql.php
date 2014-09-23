<?php
return '
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL DEFAULT \'\',
  `code` varchar(32) NOT NULL DEFAULT \'\',
  `country_code` char(2) NOT NULL DEFAULT \'\',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `state` (`name`),
  KEY `country_code` (`country_code`)
';
