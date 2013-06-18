DROP TABLE IF EXISTS `t_sys_custom_bbcode`;
CREATE TABLE `t_sys_custom_bbcode` (
  `id` int(10) NOT NULL auto_increment,
  `title` varchar(255) NOT NULL default '',
  `desc` text NOT NULL,
  `tag` varchar(255) NOT NULL default '',
  `replace` text NOT NULL,
  `useoption` tinyint(1) NOT NULL default '0',
  `example` text NOT NULL,
  `active` enum('0','1') NOT NULL,
  PRIMARY KEY  (`id`)
);


INSERT INTO `t_sys_custom_bbcode` VALUES (1, 'Post Snap Back', 'This tag displays a little linked image which links back to a post - used when quoting posts from the board. Opens in same window by default.', 'snapback', '<a href=index.php?act=findpost&pid={content}>{content}</a>', 0, '[snapback]100[/snapback]', '1');
INSERT INTO `t_sys_custom_bbcode` VALUES (2, 'Right', 'Aligns content to the right of the posting area', 'right', '<div align=''right''>{content}</div>', 0, '[right]Some text here[/right]', '1');
INSERT INTO `t_sys_custom_bbcode` VALUES (3, 'Left', 'Aligns content to the left of the post', 'left', '<div align=''left''>{content}</div>', 0, '[left]Left aligned text[/left]', '1');
INSERT INTO `t_sys_custom_bbcode` VALUES (4, 'Center', 'Aligns content to the center of the posting area.', 'center', '<div align=''center''>{content}</div>', 0, '[center]Centered Text[/center]', '1');
INSERT INTO `t_sys_custom_bbcode` VALUES (5, 'Topic Link', 'This tag provides an easy way to link to a topic', 'topic', '<a href=''index.php?showtopic={option}''>{content}</a>', 1, '[topic=100]Click me![/topic]', '1');
INSERT INTO `t_sys_custom_bbcode` VALUES (6, 'Post Link', 'This tag provides an easy way to link to a post.', 'post', '<a href=''index.php?act=findpost&pid={option}''>{content}</a>', 1, '[post=100]Click me![/post]', '1');
INSERT INTO `t_sys_custom_bbcode` VALUES (7, 'CODEBOX', 'Use this BBCode tag to show a scrolling codebox. Useful for long sections of code.', 'codebox', '<div class=''codetop''>CODE</div><div class=''codemain'' style=''height:50px;white-space:pre;overflow:auto''>{content}</div>', 0, '[codebox]long_code_here = '';[/codebox]', '1');
        