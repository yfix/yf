<?php

asset('socialite');
css('
.vhidden { border: 0; clip: rect(0 0 0 0); height: 1px; margin: -1px; overflow: hidden; padding: 0; position: absolute; width: 1px; }

.social-buttons { display: block; list-style: none; padding: 0; margin: 20px; }
.social-buttons > li { display: block; margin: 0; padding: 10px; float: left; }
.social-buttons .socialite { display: block; position: relative; background: url("//rawgit.yfix.net/yfix/Socialite/master/images/social-sprite.png") 0 0 no-repeat; }
.social-buttons .socialite-loaded { background: none !important; }
/*
.social-buttons .twitter-share { width: 55px; height: 65px; background-position: 0 0; }
.social-buttons .googleplus-one { width: 50px; height: 65px; background-position: -75px 0; }
.social-buttons .facebook-like { width: 50px; height: 65px; background-position: -145px 0; }
*/
');
jquery('
	Socialite.setup({
		facebook: { lang: "ru_RU", appId: 123456789 },
		twitter: { lang: "ru" },
		googleplus: { lang: "ru-RU" },
		vkontakte: { lang: "ru" },
		youtube: { lang: "ru-RU" },
	});
	$("article.text").one("mouseenter", function() {
		Socialite.load($(this)[0]);
	});
');
$text = SITE_ADVERT_NAME;
$url = WEB_PATH;

return '
	<article class="text">
		<h2>Article Title</h2>
		<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer ut suscipit felis. Duis massa lectus, vulputate a condimentum eget, pulvinar eget erat. Aliquam erat volutpat. Integer viverra nunc at metus interdum nec luctus felis dapibus. Cras blandit ipsum vel nibh cursus sed condimentum ante hendrerit. Aliquam pulvinar tincidunt dui quis consequat. Donec erat odio, faucibus ac hendrerit et, iaculis vitae enim. Sed volutpat aliquet tempus. Suspendisse sodales mollis varius. Morbi libero turpis, elementum ac hendrerit ac, lobortis a ligula. In hac habitasse platea dictumst.</p>
		<p>Praesent euismod tincidunt felis, quis elementum lorem suscipit sed. Ut adipiscing, tortor elementum hendrerit blandit, lorem lectus tempor magna, vitae semper metus turpis ac leo. Nulla in dui sit amet nulla condimentum ornare. Maecenas sed egestas felis. Nulla porta leo sit amet augue ullamcorper rhoncus. Donec sodales, dui luctus sollicitudin scelerisque, dui risus iaculis magna, id ultricies ipsum arcu eu dui. Etiam gravida sagittis nunc, ut molestie enim placerat et. Ut imperdiet sapien nec est pulvinar ullamcorper. Etiam ac risus diam, vitae rutrum diam. Donec et nulla dolor.</p>
		<ul class="social-buttons cf">
			<li><a href="http://twitter.com/share" class="socialite twitter-share" data-text="' . _prepare_html($text) . '" data-url="' . _prepare_html($url) . '" data-count="" rel="nofollow" target="_blank"><span class="vhidden">Share on Twitter</span></a></li>
			<li><a href="https://plus.google.com/share?url=' . urlencode($url) . '" class="socialite googleplus-one" data-size="" data-href="' . _prepare_html($url) . '" rel="nofollow" target="_blank"><span class="vhidden">Share on Google+</span></a></li>
			<li><a href="http://www.facebook.com/sharer.php?u=' . urlencode($url) . '&amp;t=' . urlencode($text) . '" class="socialite facebook-like" data-href="' . _prepare_html($url) . '" data-send="false" data-layout="" data-width="60" data-show-faces="false" rel="nofollow" target="_blank"><span class="vhidden">Share on Facebook</span></a></li>
			<li><a href="http://vk.com/developers.php?oid=-1&p=Like" class="socialite vkontakte-like" data-href="' . _prepare_html($url) . '" rel="nofollow" target="_blank"><span class="vhidden">Share on VK</span></a></li>
			<li><a href="https://developers.google.com/youtube/youtube_subscribe_button" class="socialite youtube-subscribe" data-href="' . _prepare_html($url) . '" rel="nofollow" target="_blank"><span class="vhidden">Share on Youtube</span></a></li>
		</ul>
	</article>
';
