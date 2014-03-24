<?php
$data = '
  `id` char(32) NOT NULL,
  `ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `data` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ts` (`ts`)
';
