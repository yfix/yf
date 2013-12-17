<?php

load('oauth_driver2', 'framework', 'classes/oauth/');
class yf_oauth_driver_github extends yf_oauth_driver2 {

	protected $url_authorize = 'https://github.com/login/oauth/authorize';
	protected $url_access_token = 'https://github.com/login/oauth/access_token';
	protected $url_user = 'https://api.github.com/user';
	protected $url_user_emails = 'https://api.github.com/user/emails';
	protected $scope = 'user'; // http://developer.github.com/v3/oauth/#scopes // user Read/write access to profile info only. Note: this scope includes user:email and user:follow.
	protected $get_access_token_method = 'POST';

// 'custom_header' => 'Accept: application/vnd.github.v3+json',

	/**
	*/
	function _get_user_info_for_auth($raw = array()) {
		$user_info = array(
			'user_id'		=> $raw['id'],
			'login'			=> $raw['login'],
			'name'			=> $raw['id'],
			'email'			=> current($raw['emails']),
			'avatar_url'	=> $raw['avatar_url'],
			'profile_url'	=> $raw['url'],
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
			$url = $this->url_user.'?'.http_build_query(array(
				'access_token'	=> $access_token,
			));
			$result = common()->get_remote_page($url, $cache = false, $opts, $response);
			$result = $this->_decode_result($result, $response, __FUNCTION__);
			if (isset($result['error']) || substr($response['http_code'], 0, 1) == '4') {
				$this->_storage_clean();
				js_redirect( $this->redirect_uri, $url_rewrite = false );
				return false;
			} else {
				$this->_storage_set('user_info_request', array('result' => $result, 'response' => $response));
				$user = $result;

				// Emails
				$url_emails = $this->url_user_emails.'?'.http_build_query(array(
					'access_token'	=> $access_token,
				));
				$result = common()->get_remote_page($url_emails, $cache = false, $opts = array(), $response);
				$result = $this->_decode_result($result, $response, __FUNCTION__);
				$user['emails'] = $result;

				$this->_storage_set('user', $user);
			}
		}
		return $this->_storage_get('user');
	}
}