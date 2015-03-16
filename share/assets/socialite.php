<?php

return array(
	'versions' => array(
		'master' => array(
			'js' => array(
				'//cdn.rawgit.com/yfix/Socialite/master/socialite.js',
				'//cdn.rawgit.com/yfix/Socialite/master/extensions/socialite.vkontakte.js',
				'//cdn.rawgit.com/yfix/Socialite/master/extensions/socialite.youtube.js',
			),
/*
			'css' => '
.social-buttons { display: block; list-style: none; padding: 0; margin: 20px; }
.social-buttons > li { display: block; margin: 0; padding: 10px; float: left; }
.social-buttons .socialite { display: block; position: relative; background: url("//cdn.rawgit.com/yfix/Socialite/master/images/social-sprite.png") 0 0 no-repeat; }
.social-buttons .socialite-loaded { background: none !important; }

.social-buttons .twitter-share { width: 55px; height: 65px; background-position: 0 0; }
.social-buttons .googleplus-one { width: 50px; height: 65px; background-position: -75px 0; }
.social-buttons .facebook-like { width: 50px; height: 65px; background-position: -145px 0; }
.social-buttons .linkedin-share { width: 60px; height: 65px; background-position: -215px 0; }
			',
*/
		),
	),
	'require' => array(
		'asset' => 'jquery',
	),
);
