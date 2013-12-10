<?php
$data = array(
	'oauth_version' => '2.0',
	'token_request_method' => 'POST',
	'dialog_url' => 'http://oauth.vk.com/authorize?client_id={CLIENT_ID}&redirect_uri={REDIRECT_URI}&scope={SCOPE}&state={STATE}',
	'access_token_url' => 'https://oauth.vk.com/access_token',
	'user_info_url' => 'https://api.vk.com/method/users.get',
	'scope' => 'nickname screen_name photo_big',
	'get_user_info_callback' => function ($settings, $_this) {
/*
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
*/
	}
);
/*
class OAuth2_Provider_Vkontakte
{
	protected $method = 'POST';
	public $uid_key = 'user_id';

	public function url_authorize()
	{
		return 'http://oauth.vk.com/authorize';
	}

	public function url_access_token()
	{
		return 'https://oauth.vk.com/access_token';
	}

	public function get_user_info(OAuth2_Token_Access $token)
	{
		$scope = array('nickname', 'screen_name','photo_big');
		$url = 'https://api.vk.com/method/users.get?'.http_build_query(array(
			'uids' => $token->uid,
			'fields' => implode(",",$scope),
			'access_token' => $token->access_token,
		));

		$user = json_decode(file_get_contents($url))->response;

		if(sizeof($user)==0)
			return null;
		else
			$user = $user[0];

		return array(
			'uid' => $user->uid,
			'nickname' => isset($user->nickname) ? $user->nickname : null,
			'name' => isset($user->name) ? $user->name : null,
			'first_name' => isset($user->first_name) ? $user->first_name : null,
			'last_name' => isset($user->last_name) ? $user->last_name : null,
			'email' => null,
			'location' => null,
			'description' => null,
			'image' => isset($user->photo_big) ? $user->photo_big : null,
			'urls' => array(),
		);
	}
}
*/