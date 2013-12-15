<?php

load('oauth_driver2', 'framework', 'classes/oauth/');
class yf_oauth_driver_heroku extends yf_oauth_driver2 {

	protected $url_authorize = 'https://id.heroku.com/oauth/authorize';
	protected $url_access_token = 'https://id.heroku.com/oauth/token';
	protected $url_user = 'https://api.heroku.com/account';
	protected $scope = 'identity';
	protected $get_access_token_method = 'POST';
	protected $url_params_access_token = array(
		'grant_type'	=> 'authorization_code',
	);
	protected $get_user_info_user_bearer = true;

}