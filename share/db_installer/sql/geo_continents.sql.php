<?php
$data = '
  `code` char(2) NOT NULL DEFAULT \'\',
  `name` varchar(20) DEFAULT NULL,
  `geoname_id` int(11) DEFAULT NULL,
  `active` enum(\'0\',\'1\') NOT NULL DEFAULT \'0\',
  PRIMARY KEY (`code`)
';