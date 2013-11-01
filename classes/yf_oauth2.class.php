<?php

class yf_oauth2 {

	private $_providers = array(
		'__default__' => array(
			'request_token_url' => '',
			'append_state_to_redirect_uri' => '',
			'authorization_header' => true,
			'url_parameters' => false,
			'token_request_method' => 'GET',
			'signature_method' => 'HMAC-SHA1',
		),
		'bitbucket' => array(
			'oauth_version' => '1.0a',
			'request_token_url' => 'https://bitbucket.org/!api/1.0/oauth/request_token',
			'dialog_url' => 'https://bitbucket.org/!api/1.0/oauth/authenticate',
			'access_token_url' => 'https://bitbucket.org/!api/1.0/oauth/access_token',
			'url_parameters' => false,
			'user_info_url' => 'https://api.bitbucket.org/1.0/user',
			'dev_register_url' => array('https://bitbucket.org/account/', 'Integrated Applications', 'Add Consumer'),
		),
		'box' => array(
			'oauth_version' => '2.0',
			'dialog_url' => 'https://www.box.com/api/oauth2/authorize?response_type=code&client_id={CLIENT_ID}&redirect_uri={REDIRECT_URI}&state={STATE}',
			'offline_dialog_url' => 'https://www.box.com/api/oauth2/authorize?response_type=code&client_id={CLIENT_ID}&redirect_uri={REDIRECT_URI}&state={STATE}&access_type=offline&approval_prompt=force',
			'access_token_url' => 'https://www.box.com/api/oauth2/token',
			'user_info_url' => 'https://api.box.com/2.0/users/me',
			'dev_register_url' => 'https://www.box.com/developers/services',
		),
		'disqus' => array(
			'oauth_version' => '2.0',
			'dialog_url' => 'https://disqus.com/api/oauth/2.0/authorize/?response_type=code&client_id={CLIENT_ID}&redirect_uri={REDIRECT_URI}&scope={SCOPE}&state={STATE}',
			'access_token_url' => 'https://disqus.com/api/oauth/2.0/access_token/',
			'user_info_url' => 'https://disqus.com/api/3.0/users/details.json?api_key={CLIENT_ID}', // $client->scope = 'read,write'
			'dev_register_url' => 'http://disqus.com/api/applications/',
		),
		'dropbox' => array(
			'oauth_version' => '1.0',
			'request_token_url' => 'https://api.dropbox.com/1/oauth/request_token',
			'dialog_url' => 'https://www.dropbox.com/1/oauth/authorize',
			'access_token_url' => 'https://api.dropbox.com/1/oauth/access_token',
			'authorization_header' => false,
			'user_info_url' => 'https://api.dropbox.com/1/account/info',
			'dev_register_url' => 'https://www.dropbox.com/developers/apps',
		),
		'evernote' => array(
			'oauth_version' => '1.0a',
			'request_token_url' => 'https://sandbox.evernote.com/oauth',
			'dialog_url' => 'https://sandbox.evernote.com/OAuth.action',
			'access_token_url' => 'https://sandbox.evernote.com/oauth',
			'url_parameters' => true,
			'authorization_header' => false,
			'user_info_url' => '',
			'dev_register_url' => '',
		),
		'facebook' => array(
			'oauth_version' => '2.0',
			'dialog_url' => 'https://www.facebook.com/dialog/oauth?client_id={CLIENT_ID}&redirect_uri={REDIRECT_URI}&scope={SCOPE}&state={STATE}',
			'access_token_url' => 'https://graph.facebook.com/oauth/access_token',
			'user_info_url' => 'https://graph.facebook.com/me', // $client->scope = 'email';
			'dev_register_url' => 'https://developers.facebook.com/apps',
		),
		'flickr' => array(
			'oauth_version' => '1.0a',
			'request_token_url' => 'http://www.flickr.com/services/oauth/request_token',
			'dialog_url' => 'http://www.flickr.com/services/oauth/authorize?perms={SCOPE}',
			'access_token_url' => 'http://www.flickr.com/services/oauth/access_token',
			'authorization_header' => false,
			'user_info_url' => array('http://api.flickr.com/services/rest/', array('method'=>'flickr.test.login', 'format'=>'json', 'nojsoncallback'=>'1')), // $client->scope = 'read'; // 'read', 'write' or 'delete'
			'dev_register_url' => 'http://www.flickr.com/services/apps/create/',
		),
		'foursquare' => array(
			'oauth_version' => '2.0',
			'dialog_url' => 'https://foursquare.com/oauth2/authorize?client_id={CLIENT_ID}&scope={SCOPE}&response_type=code&redirect_uri={REDIRECT_URI}&state={STATE}',
			'access_token_url' => 'https://foursquare.com/oauth2/access_token',
			'access_token_parameter' => 'oauth_token',
			'user_info_url' => 'https://api.foursquare.com/v2/users/self?v=20131013',
			'dev_register_url' => 'https://foursquare.com/developers/apps',
		),
		'github' => array(
			'oauth_version' => '2.0',
			'dialog_url' => 'https://github.com/login/oauth/authorize?client_id={CLIENT_ID}&redirect_uri={REDIRECT_URI}&scope={SCOPE}&state={STATE}',
			'access_token_url' => 'https://github.com/login/oauth/access_token',
			'user_info_url' => 'https://api.github.com/user',
			'dev_register_url' => '',
		),
		'google' => array(
			'oauth_version' => '2.0',
			'dialog_url' => 'https://accounts.google.com/o/oauth2/auth?response_type=code&client_id={CLIENT_ID}&redirect_uri={REDIRECT_URI}&scope={SCOPE}&state={STATE}',
			'offline_dialog_url' => 'https://accounts.google.com/o/oauth2/auth?response_type=code&client_id={CLIENT_ID}&redirect_uri={REDIRECT_URI}&scope={SCOPE}&state={STATE}&access_type=offline&approval_prompt=force',
			'access_token_url' => 'https://accounts.google.com/o/oauth2/token',
			'user_info_url' => 'https://www.googleapis.com/oauth2/v1/userinfo', // $client->scope = 'https://www.googleapis.com/auth/userinfo.email '.'https://www.googleapis.com/auth/userinfo.profile';
			'dev_register_url' => 'http://code.google.com/apis/console',
		),
		'instagram' => array(
			'oauth_version' => '2.0',
			'dialog_url' => 'https://api.instagram.com/oauth/authorize/?client_id={CLIENT_ID}&redirect_uri={REDIRECT_URI}&scope={SCOPE}&response_type=code&state={STATE}',
			'access_token_url' => 'https://api.instagram.com/oauth/access_token',
			'user_info_url' => 'https://api.instagram.com/v1/users/self/', // $client->scope = 'basic';
			'dev_register_url' => 'http://instagram.com/developer/register/',
		),
		'linkedin' => array(
			'oauth_version' => '1.0a',
			'request_token_url' => 'https://api.linkedin.com/uas/oauth/requestToken?scope={SCOPE}',
			'dialog_url' => 'https://api.linkedin.com/uas/oauth/authenticate',
			'access_token_url' => 'https://api.linkedin.com/uas/oauth/accessToken',
			'url_parameters' => true,
			'user_info_url' => array('http://api.linkedin.com/v1/people/~', array('format'=>'json')), // $client->scope = 'r_fullprofile r_emailaddress';
			'dev_register_url' => 'https://www.linkedin.com/secure/developer?newapp=',
		),
		'microsoft' => array(
			'oauth_version' => '2.0',
			'dialog_url' => 'https://login.live.com/oauth20_authorize.srf?client_id={CLIENT_ID}&scope={SCOPE}&response_type=code&redirect_uri={REDIRECT_URI}&state={STATE}',
			'access_token_url' => 'https://login.live.com/oauth20_token.srf',
			'user_info_url' => 'https://apis.live.net/v5.0/me', // $client->scope = 'wl.basic wl.emails';
			'dev_register_url' => 'https://manage.dev.live.com/AddApplication.aspx',
		),
		'rightsignature' => array(
			'oauth_version' => '1.0a',
			'request_token_url' => 'https://rightsignature.com/oauth/request_token',
			'dialog_url' => 'https://rightsignature.com/oauth/authorize',
			'access_token_url' => 'https://rightsignature.com/oauth/access_token',
			'authorization_header' => false,
			'user_info_url' => '',
			'dev_register_url' => '',
		),
		'salesforce' => array(
			'oauth_version' => '2.0',
			'dialog_url' => 'https://login.salesforce.com/services/oauth2/authorize?response_type=code&client_id={CLIENT_ID}&redirect_uri={REDIRECT_URI}&scope={SCOPE}&state={STATE}',
			'access_token_url' => 'https://login.salesforce.com/services/oauth2/token',
			'default_access_token_type' => 'Bearer',
			'store_access_token_response' => true,
			'user_info_url' => '',
			'dev_register_url' => '',
		),
		'surveymonkey' => array(
			'oauth_version' => '2.0',
			'dialog_url' => 'https://api.surveymonkey.net/oauth/authorize?client_id={CLIENT_ID}&redirect_uri={REDIRECT_URI}&response_type=code&state={STATE}&api_key={API_KEY}',
			'access_token_url' => 'https://api.surveymonkey.net/oauth/token?api_key={API_KEY}',
			'user_info_url' => '',
			'dev_register_url' => '',
		),
		'tumblr' => array(
			'oauth_version' => '1.0a',
			'request_token_url' => 'http://www.tumblr.com/oauth/request_token',
			'dialog_url' => 'http://www.tumblr.com/oauth/authorize',
			'access_token_url' => 'http://www.tumblr.com/oauth/access_token',
			'user_info_url' => '',
			'dev_register_url' => '',
		),
		'twitter' => array(
			'oauth_version' => '1.0a',
			'request_token_url' => 'https://api.twitter.com/oauth/request_token',
			'dialog_url' => 'https://api.twitter.com/oauth/authenticate',
			'access_token_url' => 'https://api.twitter.com/oauth/access_token',
			'url_parameters' => true,
			'user_info_url' => '',
			'dev_register_url' => '',
		),
		'xing' => array(
			'oauth_version' => '1.0a',
			'request_token_url' => 'https://api.xing.com/v1/request_token',
			'dialog_url' => 'https://api.xing.com/v1/authorize',
			'access_token_url' => 'https://api.xing.com/v1/access_token',
			'authorization_header' => false,
			'user_info_url' => '',
			'dev_register_url' => '',
		),
		'yahoo' => array(
			'oauth_version' => '1.0a',
			'request_token_url' => 'https://api.login.yahoo.com/oauth/v2/get_request_token',
			'dialog_url' => 'https://api.login.yahoo.com/oauth/v2/request_auth',
			'access_token_url' => 'https://api.login.yahoo.com/oauth/v2/get_token',
			'authorization_header' => false,
			'user_info_url' => '',
			'dev_register_url' => '',
		),
		'amazon' => array(
// TODO
#				'oauth_version' => '2.0',
#	'baseApiUri' => new Uri('https://api.amazon.com/'),
#	return new Uri('https://www.amazon.com/ap/oa'),
#	return new Uri('https://www.amazon.com/ap/oatoken'),
#				'request_token_url' => 'https://oauth.yandex.com/authorize',
#				'dialog_url' => 'https://oauth.yandex.com/authorize',
#				'access_token_url' => 'https://oauth.yandex.com/verification_code',
#				'authorization_header' => false,
			'user_info_url' => '',
			'dev_register_url' => '',
		),
		'yandex' => array(
// TODO
#			'oauth_version' => '2.0',
#			'request_token_url' => 'https://oauth.yandex.com/authorize',
#			'dialog_url' => 'https://oauth.yandex.com/authorize',
#			'access_token_url' => 'https://oauth.yandex.com/verification_code',
#			'authorization_header' => false,
			'user_info_url' => '',
			'dev_register_url' => '',
		),
		'vk' => array(
// TODO
#				'oauth_version' => '2.0',
#	'baseApiUri' => new Uri('https://api.vk.com/method/'),
#	return new Uri('https://oauth.vk.com/authorize'),
#	return new Uri('https://oauth.vk.com/access_token'),
#				'request_token_url' => 'https://oauth.yandex.com/authorize',
#				'dialog_url' => 'https://oauth.yandex.com/authorize',
#				'access_token_url' => 'https://oauth.yandex.com/verification_code',
#				'authorization_header' => false,
			'user_info_url' => '',
			'dev_register_url' => '',
		),
		'odnoklassniki' => array(
// TODO
			'user_info_url' => '',
			'dev_register_url' => '',
		),
		'mailru' => array(
// TODO
			'user_info_url' => '',
			'dev_register_url' => '',
		),
		'bitly' => array(
// TODO
			'user_info_url' => '',
			'dev_register_url' => '',
		),
		'heroku' => array(
// TODO
			'user_info_url' => '',
			'dev_register_url' => '',
		),
		'paypal' => array(
// TODO
			'user_info_url' => '',
			'dev_register_url' => '',
		),
		'windowslive' => array(
// TODO
			'user_info_url' => '',
			'dev_register_url' => '',
		),
	);

	/**
	*/
	function _get_providers() {
		return $this->_providers;
	}
// TODO
}