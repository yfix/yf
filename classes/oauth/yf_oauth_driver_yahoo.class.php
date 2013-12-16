<?php

load('oauth_driver1', 'framework', 'classes/oauth/');
class yf_oauth_driver_yahoo extends yf_oauth_driver1 {

	protected $url_request_token = 'https://api.login.yahoo.com/oauth/v2/get_request_token';
	protected $url_access_token = 'https://api.login.yahoo.com/oauth/v2/get_token';
	protected $url_authenticate = 'https://api.login.yahoo.com/oauth/v2/request_auth';
	protected $url_user = 'http://social.yahooapis.com/v1/user/{user_id}/profile/usercard?format=json';
	protected $access_token_use_header = false;
	protected $field_user_id = 'xoauth_yahoo_guid';
#	protected $header_add_realm = 'yahooapis.com';

#	$url = 'http://query.yahooapis.com/v1/yql?'.http_build_query(array(
#		'q' => 'select * from social.profile where guid=me',
#		'format' => 'json',
#	));
#$debug = $_SESSION['oauth']['yahoo'];
#unset($debug['access_token_request']);
#unset($debug['request_token_request']);
#unset($debug['authorize_request']);
#echo '<pre><small>'.print_r($debug, 1).'</small></pre>';

// TODO
}