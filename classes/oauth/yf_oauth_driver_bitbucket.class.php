<?php

load('oauth_driver1', 'framework', 'classes/oauth/');
class yf_oauth_driver_bitbucket extends yf_oauth_driver1 {

	protected $url_request_token = 'https://bitbucket.org/api/1.0/oauth/request_token';
	protected $url_authenticate = 'https://bitbucket.org/api/1.0/oauth/authenticate';
	protected $url_access_token = 'https://bitbucket.org/api/1.0/oauth/access_token';
	protected $url_user = 'https://bitbucket.org/api/1.0/user';
	protected $get_access_token_method = 'POST';
}