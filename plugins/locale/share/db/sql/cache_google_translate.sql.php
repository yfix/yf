<?php

return '
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `md5` char(32) NOT NULL,
  `lang_from` char(2) NOT NULL,
  `lang_to` char(2) NOT NULL,
  `source` text NOT NULL,
  `translated` text NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`id`)
  /** ENGINE=InnoDB DEFAULT CHARSET=utf8 **/
';
