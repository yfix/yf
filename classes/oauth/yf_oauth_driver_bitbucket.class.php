<?php

load('oauth_driver1', 'framework', 'classes/oauth/');
class yf_oauth_driver_bibucket extends yf_oauth_driver1 {

	protected $url_request_token = 'https://bitbucket.org/api/1.0/oauth/request_token';
	protected $url_authorize = 'https://bitbucket.org/api/1.0/oauth/authenticate';
	protected $url_access_token = 'https://bitbucket.org/api/1.0/oauth/access_token';
	protected $url_user = 'https://api.bitbucket.org/2.0/users/{user_id}';
	protected $scope = '';
	protected $get_access_token_method = 'POST';

}