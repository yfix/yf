<?php

return '
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `add_date` int(11) NOT NULL,
  `receiver_type` enum(\'user_id\',\'admin_id\',\'user_id_tmp\') NOT NULL,
  `title` varchar(255) NOT NULL DEFAULT \'\',
  `content` text NOT NULL,
  PRIMARY KEY (`id`)
';
