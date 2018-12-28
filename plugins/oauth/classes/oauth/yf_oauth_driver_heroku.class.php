<?php

load('oauth_driver2', '', 'classes/oauth/');
class yf_oauth_driver_heroku extends yf_oauth_driver2
{
    public $scope = 'identity';
    public $get_access_token_method = 'POST';
    public $url_params_access_token = [
        'grant_type' => 'authorization_code',
    ];
    public $get_user_info_user_bearer = true;

    protected $url_authorize = 'https://id.heroku.com/oauth/authorize';
    protected $url_access_token = 'https://id.heroku.com/oauth/token';
    protected $url_user = 'https://api.heroku.com/account';

    /**
     * @param mixed $raw
     */
    public function _get_user_info_for_auth($raw = [])
    {
        $user_info = [
            'user_id' => $raw['id'],
            'login' => $raw['email'],
            'name' => $raw['name'],
            'email' => $raw['email'],
//			'avatar_url'	=> $raw['avatar_url'],
//			'profile_url'	=> $raw['url'],
        ];
        return $user_info;
    }
}
