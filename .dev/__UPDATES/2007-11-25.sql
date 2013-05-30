DROP TABLE IF EXISTS `sexy_forum_reports`;
CREATE TABLE IF NOT EXISTS `sexy_forum_reports` (
  `id` int(11) NOT NULL auto_increment,
  `post_id` int(11) NOT NULL default '0',
  `user_id` int(11) NOT NULL default '0',
  `time` int(11) NOT NULL default '0',
  `text` text NOT NULL,
  `active` int(11) NOT NULL default '1',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=cp1251 AUTO_INCREMENT=24 ;


INSERT INTO `sexy_forum_reports` VALUES (12, 921, 1, 1195118929, '', 1);
INSERT INTO `sexy_forum_reports` VALUES (11, 756, 1, 1195118919, '', 1);
INSERT INTO `sexy_forum_reports` VALUES (10, 730, 1, 1195118910, '', 0);
INSERT INTO `sexy_forum_reports` VALUES (13, 843, 1, 1195131537, '', 1);
INSERT INTO `sexy_forum_reports` VALUES (14, 921, 1, 1195131548, '', 1);
INSERT INTO `sexy_forum_reports` VALUES (15, 921, 1, 1195131553, '', 1);
INSERT INTO `sexy_forum_reports` VALUES (16, 843, 1, 1195131560, '', 1);
INSERT INTO `sexy_forum_reports` VALUES (17, 786, 1, 1195131568, '', 1);
INSERT INTO `sexy_forum_reports` VALUES (18, 786, 1, 1195131569, '', 1);
INSERT INTO `sexy_forum_reports` VALUES (19, 786, 1, 1195131570, '', 1);
INSERT INTO `sexy_forum_reports` VALUES (20, 786, 1, 1195131571, '', 1);
INSERT INTO `sexy_forum_reports` VALUES (21, 786, 1, 1195131572, '', 1);
INSERT INTO `sexy_forum_reports` VALUES (22, 786, 1, 1195131573, '', 1);
INSERT INTO `sexy_forum_reports` VALUES (23, 786, 1, 1195131574, '', 1);
        