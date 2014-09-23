<?php
return '
  `user_id` bigint(20) NOT NULL,
  `user_type` enum(\'user_id\',\'user_id_tmp\',\'admin_id\') NOT NULL,
  `time` int(11) NOT NULL,
  `ip` varchar(16) NOT NULL,
  `session_id` varchar(255) NOT NULL,
  `user_agent` varchar(255) NOT NULL,
  `url` varchar(255) NOT NULL,
  PRIMARY KEY (`user_id`,`user_type`)
';
