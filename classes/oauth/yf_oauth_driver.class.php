<?php

abstract class yf_oauth_driver {

	/**
	*/
	function login() {
		$config = _class('oauth')->_load_oauth_config();
		if (!$config[$this->provider] || !$config[$this->provider]['client_id'] || !$config[$this->provider]['client_secret']) {
			return '<h1 class="text-error">Error: no config client_id and client_secret for provider: '.$this->provider.'</h1>';
		}
		$this->redirect_uri = _force_get_url(array('object' => $_GET['object'], 'action' => $_GET['action'], 'id' => $_GET['id']));
		$this->client_id = $config[$this->provider]['client_id'] ?: ''; $application_line = __LINE__;
		$this->client_secret = $config[$this->provider]['client_secret'] ?: '';
		$this->client_public = $config[$this->provider]['client_public'] ?: '';
		return $this->get_user_info();
	}

	/**
	*/
	function _decode_result($result, $response) {
		if (strpos($response['content_type'], 'json') !== false || strpos($response['content_type'], 'javascript') !== false) {
			$result = _class('utils')->object_to_array(json_decode($result));
		} elseif (strpos($response['content_type'], 'application/x-www-form-urlencoded') !== false) {
			parse_str($result, $try_parsed);
			if (is_array($try_parsed) && count($try_parsed) > 1) {
				$result = $try_parsed;
			}
		}
		return $result;
	}
}