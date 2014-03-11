<?php
$data = '
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `event_id` int(10) unsigned NOT NULL,
  `from_email` varchar(254) DEFAULT NULL,
  `from_name` varchar(100) DEFAULT NULL,
  `subject` varchar(78) DEFAULT NULL,
  `html` text,
  `active` tinyint(4) NOT NULL DEFAULT \'0\',
  PRIMARY KEY (`id`),
  KEY `event_id` (`event_id`),
  KEY `event_id_active` (`event_id`,`active`)
';