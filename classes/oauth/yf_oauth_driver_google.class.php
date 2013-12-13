<?php

load('oauth_driver', 'framework', 'classes/oauth/');
class yf_oauth_driver_google extends yf_oauth_driver {

	protected $url_authorize = 'https://accounts.google.com/o/oauth2/auth';
	protected $url_access_token = 'https://accounts.google.com/o/oauth2/token';
	protected $url_user = 'https://www.googleapis.com/oauth2/v1/userinfo';
	protected $provider = 'google';
	protected $scope = 'https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email';
	protected $get_access_token_method = 'POST';

}