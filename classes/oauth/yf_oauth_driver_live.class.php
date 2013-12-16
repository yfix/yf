<?php

load('oauth_driver2', 'framework', 'classes/oauth/');
class yf_oauth_driver_live extends yf_oauth_driver2 {

	protected $url_authorize = 'https://login.live.com/oauth20_authorize.srf';
	protected $url_access_token = 'https://login.live.com/oauth20_token.srf';
	protected $url_user = 'https://apis.live.net/v5.0/me';
	protected $scope = 'wl.basic wl.emails wl.offline_access';
	protected $get_access_token_method = 'POST';
	protected $url_params_access_token = array(
		'grant_type'	=> 'authorization_code',
	);

}