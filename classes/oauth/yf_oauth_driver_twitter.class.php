<?php

load('oauth_driver1', 'framework', 'classes/oauth/');
class yf_oauth_driver_twitter extends yf_oauth_driver1 {

	protected $url_authorize = 'https://api.twitter.com/oauth/authorize';
	protected $url_request_token = 'https://api.twitter.com/oauth/request_token';
	protected $url_access_token = 'https://api.twitter.com/oauth/access_token';
	protected $url_authenticate = 'https://api.twitter.com/oauth/authenticate';
	protected $url_user = 'https://api.twitter.com/1.1/users/lookup.json';
	protected $scope = '';
	protected $get_access_token_method = 'POST';
}