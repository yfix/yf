<?php
return '
  `id` tinyint(4) NOT NULL DEFAULT \'0\',
  `height` varchar(50) NOT NULL DEFAULT \'\',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_2` (`id`),
  KEY `id` (`id`)
';
