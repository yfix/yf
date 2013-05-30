CREATE TABLE `test_user_data_info_fields` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(255) NOT NULL,
  `type` enum('varchar','text','select','check','radio') NOT NULL,
  `value_list` text NOT NULL,
  `default_value` text NOT NULL,
  `order` int(10) unsigned NOT NULL,
  `active` enum('1','0') NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=latin1 AUTO_INCREMENT=10 ;

ALTER TABLE `sexy_user_data_info_fields` ADD `comment` TEXT NOT NULL AFTER `default_value` ;