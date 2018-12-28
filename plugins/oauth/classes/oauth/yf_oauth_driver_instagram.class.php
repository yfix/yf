<?php

load('oauth_driver2', '', 'classes/oauth/');
class yf_oauth_driver_instagram extends yf_oauth_driver2
{
    public $scope = 'basic';
    public $get_access_token_method = 'POST';
    public $url_params_access_token = [
        'grant_type' => 'authorization_code',
    ];

    protected $url_authorize = 'https://api.instagram.com/oauth/authorize/';
    protected $url_access_token = 'https://api.instagram.com/oauth/access_token';
    protected $url_user = 'https://api.instagram.com/v1/users/self';

    /**
     * @param mixed $raw
     */
    public function _get_user_info_for_auth($raw = [])
    {
        $user_info = [
            'user_id' => $raw['data']['id'],
            'login' => $raw['data']['username'],
            'name' => $raw['data']['full_name'],
            'email' => '',
            'avatar_url' => $raw['data']['profile_picture'],
            'profile_url' => 'http://instagram.com/' . $raw['data']['username'],
        ];
        return $user_info;
    }
}
