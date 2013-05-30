CREATE TABLE `synonim_grab_patterns` (
  `id` int(11) NOT NULL auto_increment,
  `name` text character set utf8 NOT NULL,
  `desc` text character set utf8 NOT NULL,
  `pattern` text character set utf8 NOT NULL,
  `replace_pattern` text character set utf8 NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=5 ;


INSERT INTO `synonim_grab_patterns` VALUES (3, 'вордпресс', 'http://mobilcontent.ru/?p=476#more-476\r\nhttp://www.allpda.mobi/2008/01/31/nxp-semiconductors-and-purple-labs-bring-linux-to-mass-market-3g-handsets/', '/<div\\sclass="entry">(.+)<p\\sclass="postmetadata\\salt">/si', '/<div\\sclass="addzakl"(.+)<\\/div>/si');
INSERT INTO `synonim_grab_patterns` VALUES (4, 'livejournal', 'http://juliy.livejournal.com/3024129.html', '/<!--\\sContent\\s-->(.+)<div id=\\''Comments\\''>/si', '');
        
		


CREATE TABLE `synonim_grab_content` (
  `id` int(11) NOT NULL auto_increment,
  `url` text NOT NULL,
  `content` text NOT NULL,
  `pattern_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=19 ;
        