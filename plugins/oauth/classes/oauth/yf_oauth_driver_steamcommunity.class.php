<?php

load('oauth_driver2', 'framework', 'classes/oauth/');

class yf_oauth_driver_steamcommunity extends yf_oauth_driver2 {
    public $url_rsa_key = 'https://steamcommunity.com/login/getrsakey';
    public $url_captcha = 'https://steamcommunity.com/public/captcha.php?gid=';
    public $url_user_info = 'http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/';

}

