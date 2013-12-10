<?php
$data = array(
	'oauth_version' => '2.0',
	'dialog_url' => 'https://github.com/login/oauth/authorize?client_id={CLIENT_ID}&redirect_uri={REDIRECT_URI}&scope={SCOPE}&state={STATE}',
	'access_token_url' => 'https://github.com/login/oauth/access_token',
	'user_info_url' => 'https://api.github.com/user',
	'scope' => 'user',
	'get_user_info_callback' => function ($settings, $_this) {
		$_this->call_api(array('url' => $settings['user_info_url']));
		$user = $_this->_get_last_response();
		if (is_object($user)) {
			$user = _class('utils')->object_to_array($user);
		}
		$_this->call_api(array('url' => $settings['user_info_url'].'/emails'));
		foreach ($_this->_get_last_response() as $k => $v) {
			$user['emails'][$k] = $v;
		}
		return $user;
	}
);
