<?php

load('oauth_driver2', '', 'classes/oauth/');
class yf_oauth_driver_live extends yf_oauth_driver2
{
    public $scope = 'wl.basic wl.emails wl.offline_access';
    public $get_access_token_method = 'POST';
    public $url_params_access_token = [
        'grant_type' => 'authorization_code',
    ];

    protected $url_authorize = 'https://login.live.com/oauth20_authorize.srf';
    protected $url_access_token = 'https://login.live.com/oauth20_token.srf';
    protected $url_user = 'https://apis.live.net/v5.0/me';

    /**
     * @param mixed $raw
     */
    public function _get_user_info_for_auth($raw = [])
    {
        $user_info = [
            'user_id' => $raw['id'],
            'login' => $raw['emails']['preferred'],
            'name' => $raw['name'],
            'email' => $raw['emails']['preferred'],
            'avatar_url' => '',
            'profile_url' => $raw['link'],
            'locale' => $raw['locale'],
        ];
        return $user_info;
    }
}
