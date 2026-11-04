<?php

load('oauth_driver2', '', 'classes/oauth/');
class yf_oauth_driver_steamcommunity extends yf_oauth_driver2
{
    public $url_rsa_key = 'https://steamcommunity.com/login/getrsakey';
    public $url_captcha = 'https://steamcommunity.com/public/captcha.php?gid=';
    public $url_user_info = 'http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/';

    /**
     * @param mixed $raw Decoded GetPlayerSummaries response
     */
    public function _get_user_info_for_auth($raw = [])
    {
        $player = $raw['response']['players'][0] ?? (array) $raw;
        $user_info = [
            'user_id' => $player['steamid'] ?? '',
            'login' => $player['steamid'] ?? '',
            'name' => $player['personaname'] ?? '',
            'email' => '',
            'avatar_url' => $player['avatarfull'] ?? ($player['avatar'] ?? ''),
            'profile_url' => $player['profileurl'] ?? '',
        ];
        return $user_info;
    }
}
