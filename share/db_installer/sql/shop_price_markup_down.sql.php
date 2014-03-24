<?php
$data = "
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `active` tinyint(4) NOT NULL DEFAULT '0',
  `type` tinyint(4) NOT NULL DEFAULT '0',
  `value` decimal(10,2) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `time_from` datetime DEFAULT NULL,
  `time_to` datetime DEFAULT NULL,
  `conditions` text,
  PRIMARY KEY (`id`)
";
