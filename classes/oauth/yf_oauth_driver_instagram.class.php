<?php

load('oauth_driver2', 'framework', 'classes/oauth/');
class yf_oauth_driver_instagram extends yf_oauth_driver2 {

	protected $url_authorize = 'https://api.instagram.com/oauth/authorize/';
	protected $url_access_token = 'https://api.instagram.com/oauth/access_token';
	protected $url_user = 'https://api.instagram.com/v1/users/self';
	protected $scope = 'basic';
	protected $get_access_token_method = 'POST';

}