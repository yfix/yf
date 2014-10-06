<?php
return '
  `id` int(10) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fkey_prepare_sample_data` FOREIGN KEY (`id`) REFERENCES `t_test_dump_2` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
  /** ENGINE=InnoDB DEFAULT CHARSET=utf8 **/
';
