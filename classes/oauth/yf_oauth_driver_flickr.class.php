<?php

load('oauth_driver1', 'framework', 'classes/oauth/');
class yf_oauth_driver_flickr extends yf_oauth_driver1 {

	protected $url_authenticate = 'http://www.flickr.com/services/oauth/authorize';
	protected $url_request_token = 'http://www.flickr.com/services/oauth/request_token';
	protected $url_access_token = 'http://www.flickr.com/services/oauth/access_token';
	protected $url_user = 'http://api.flickr.com/services/rest?nojsoncallback=1&format=json&method=flickr.test.login';
#	protected $url_user = 'http://api.flickr.com/services/rest?nojsoncallback=1&format=json&method=flickr.urls.getUserProfile';
	public $get_access_token_method = 'POST';
	public $url_params_authenticate = array(
		'perms'	=> 'write',
	);

	/**
	*/
	function _get_user_info_for_auth($raw = array()) {
		$user_info = array(
			'user_id'		=> $raw['user']['id'],
#			'login'			=> $raw['login'],
			'name'			=> $raw['user']['username']['_content'],
#			'email'			=> current($raw['emails']),
#			'avatar_url'	=> $raw['avatar_url'],
#			'profile_url'	=> $raw['url'],
		);
		return $user_info;
	}
}
