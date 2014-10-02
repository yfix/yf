<?php
return '
  `id` int(5) unsigned NOT NULL,
  `lon` float NOT NULL DEFAULT \'0\',
  `lat` float NOT NULL DEFAULT \'0\',
  PRIMARY KEY (`id`),
  KEY `lon` (`lon`,`lat`)
';
