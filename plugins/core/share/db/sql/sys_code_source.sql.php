<?php
return '
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `keyword` varchar(255) NOT NULL DEFAULT \'\',
  `source` text NOT NULL,
  PRIMARY KEY (`id`)
';
