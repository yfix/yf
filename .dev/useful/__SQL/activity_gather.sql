(SELECT SUM(`activity`) AS `0` FROM `t_reviews` WHERE `reviewer_id`=1 AND `status` NOT IN(0,3)) 
 UNION ALL 
(SELECT SUM(`activity`) AS `0` FROM `t_forum_posts` WHERE `user_id`=1) 
 UNION ALL 
(SELECT SUM(`activity`) AS `0` FROM `t_mailarchive` WHERE `sender`=1)
 UNION ALL 
(SELECT SUM(`activity`) AS `0` FROM `t_reput_user_votes` WHERE `user_id`=1)
 UNION ALL 
(SELECT SUM(`activity`) AS `0` FROM `t_blog_posts` WHERE `user_id`=1)
 UNION ALL 
(SELECT SUM(`activity`) AS `0` FROM `t_sys_log_auth` WHERE `user_id`=1)
 UNION ALL 
(SELECT SUM(`activity`) AS `0` FROM `t_comments` WHERE `object_name`='blog' AND `user_id`=1)
 UNION ALL 
(SELECT SUM(`activity`) AS `0` FROM `t_comments` WHERE `object_name`='reviews' AND `user_id`=1)
 UNION ALL 
(SELECT SUM(`activity`) AS `0` FROM `t_help_tickets` WHERE `user_id`=1)
 UNION ALL 
(SELECT SUM(`activity`) AS `0` FROM `t_articles_texts` WHERE `user_id`=1)
 UNION ALL 
(SELECT SUM(`activity`) AS `0` FROM `t_referrals` WHERE `type`='e' AND `user_id`=1);