<?php

load('oauth_driver2', 'framework', 'classes/oauth/');
class yf_oauth_driver_disqus extends yf_oauth_driver2 {

	protected $url_authorize = 'https://disqus.com/api/oauth/2.0/authorize/';
	protected $url_access_token = 'https://disqus.com/api/oauth/2.0/access_token/';
	protected $url_user = 'https://disqus.com/api/3.0/users/details.json';
	public $scope = 'read';
	public $get_access_token_method = 'POST';
	public $url_params_access_token = [
		'grant_type'	=> 'authorization_code',
	];

	/**
	*/
	function _get_user_info_for_auth($raw = []) {
		$user_info = [
			'user_id'		=> $raw['response']['id'],
			'login'			=> $raw['response']['username'],
			'name'			=> $raw['response']['name'],
			'email'			=> '',
			'avatar_url'	=> $raw['response']['avatar']['permalink'],
			'profile_url'	=> $raw['response']['profileUrl'],
		];
		return $user_info;
	}

	/**
	*/
	function get_user_info() {
		$access_token = $this->_storage_get('access_token');
		if (!$access_token) {
			$access_token = $this->get_access_token();
			if (!$access_token) {
				$this->_storage_clean();
				js_redirect( $this->redirect_uri, $url_rewrite = false );
				return false;
			}
		}
		if (!$this->_storage_get('user')) {
			$url = $this->url_user.'?'.http_build_query([
				'access_token'	=> $access_token,
				'api_key'		=> $this->client_id,
				'api_secret'	=> $this->client_secret,
			]);
			$result = common()->get_remote_page($url, $cache = false, $opts, $response);
			$result = $this->_decode_result($result, $response, __FUNCTION__);
			if (isset($result['error']) || substr($response['http_code'], 0, 1) == '4') {
				$this->_storage_clean();
				js_redirect( $this->redirect_uri, $url_rewrite = false );
				return false;
			} else {
				$this->_storage_set('user_info_request', ['result' => $result, 'response' => $response]);
				$this->_storage_set('user', $result);
			}
		}
		return $this->_storage_get('user');
	}

}
