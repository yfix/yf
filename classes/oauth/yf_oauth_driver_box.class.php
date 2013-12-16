<?php

load('oauth_driver2', 'framework', 'classes/oauth/');
class yf_oauth_driver_box extends yf_oauth_driver2 {

	protected $url_authorize = 'https://www.box.com/api/oauth2/authorize';
	protected $url_access_token = 'https://www.box.com/api/oauth2/token';
	protected $url_user = 'https://api.box.com/2.0/users/me';
	protected $scope = '';
	protected $url_params_access_token = array(
		'grant_type'	=> 'authorization_code',
	);
	protected $get_access_token_method = 'POST';
	protected $get_user_info_user_bearer = true;
	protected $redirect_uri_force_https = true;

}