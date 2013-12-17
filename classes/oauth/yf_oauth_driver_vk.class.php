<?php

load('oauth_driver2', 'framework', 'classes/oauth/');
class yf_oauth_driver_vk extends yf_oauth_driver2 {

	protected $url_authorize = 'https://oauth.vk.com/authorize';
	protected $url_access_token = 'https://oauth.vk.com/access_token';
	protected $url_user = 'https://api.vk.com/method/users.get';
	protected $scope = 'offline,wall,friends,email'; // Comma or space separated names. Note: "email" currently works only for verified partners like afisha.ru
	protected $get_access_token_method = 'GET';
	protected $url_params = array(
		'v'	=> '5.5',
	);

	/**
	*/
	function _get_user_info_for_auth($raw = array()) {
		$user_info = array(
			'user_id'		=> $raw['response'][0]['id'],
#			'login'			=> $raw['login'],
			'name'			=> $raw['response'][0]['first_name'].' '.$raw['response'][0]['last_name'],
#			'email'			=> $raw['email'],
#			'avatar_url'	=> $raw['avatar_url'],
			'profile_url'	=> 'http://vk.com/id'.$raw['response'][0]['id'],
		);
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
			$access_token_request = $this->_storage_get('access_token_request');
			$user_id = $access_token_request['result']['user_id'];
			$url = $this->url_user.'?'.http_build_query($this->url_params + array(
				'access_token'	=> $access_token,
				'user_id'		=> $user_id,
			));
			$result = common()->get_remote_page($url, $cache = false, $opts, $response);
			$result = $this->_decode_result($result, $response);
			if (isset($result['error']) || substr($response['http_code'], 0, 1) == '4') {
				$this->_storage_clean();
				js_redirect( $this->redirect_uri, $url_rewrite = false );
				return false;
			} else {
				$this->_storage_set('user_info_request', array('result' => $result, 'response' => $response));
				$this->_storage_set('user', $result);
			}
		}
		return $this->_storage_get('user');
	}
}