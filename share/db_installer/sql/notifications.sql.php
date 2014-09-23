<?php
return '
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `add_date` int(11) NOT NULL,
  `receiver_type` enum(\'user_id\',\'admin_id\',\'user_id_tmp\') NOT NULL,
  `title` varchar(255) NOT NULL CHARACTER SET utf8 DEFAULT \'\',
  `content` text NOT NULL CHARACTER SET utf8,
  PRIMARY KEY (`id`)
';
