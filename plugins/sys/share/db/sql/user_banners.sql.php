<?php
return '
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `hash` varchar(32) NOT NULL,
  `type` varchar(32) NOT NULL,
  `resolution` varchar(32) NOT NULL,
  `offer_id` int(11) NOT NULL,
  `url_list` text NOT NULL,
  `image_list` text NOT NULL,
  `header_list` text NOT NULL,
  `desc_list` text NOT NULL,
  `active` tinyint(4) NOT NULL,
  `status` tinyint(4) NOT NULL,
  `add_date` int(11) NOT NULL,
  PRIMARY KEY (`id`)
  /** ENGINE=InnoDB DEFAULT CHARSET=utf8 **/
';
