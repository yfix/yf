REPLACE INTO `%%prefix%%activity_types` (`id`, `name`, `desc`, `points`, `min_value`, `min_time`, `active`, `table_name`) VALUES 
(2, 'forum_post', 'For every forum post.', '10', '100', 120, '1', 'forum_posts'),
(3, 'sent_mail', 'For every email to other site member sent using email form on our site.', '3', '100', 180, '1', 'mailarchive'),
(4, 'rate_user', 'For every reputation vote.', '3', '', 120, '1', 'reput_user_votes'),
(5, 'blog_post', '', '10', '100', 180, '1', 'blog_posts'),
(6, 'site_login', 'For every login to our site (1 per day).', '1', '', 43200, '1', 'log_auth'),
(7, 'blog_comment', 'For every meaningful comment to someone''s blog.', '2', '100', 180, '1', 'comments'),
(10, 'bug_report', 'For reporting site software bugs to us using support ticket system.', '30', '100', 300, '1', 'help_tickets'),
(11, 'article_posted', 'For every article published on our site.', '300', '1000', 300, '1', 'articles_texts'),
(16, 'article_reposted', 'For every article published on our site.', '100', '1000', 300, '1', 'articles_texts');
