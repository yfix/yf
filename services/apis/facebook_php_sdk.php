#!/usr/bin/php
<?php

$config = [
    'git_urls' => ['https://github.com/facebook/facebook-php-sdk-v4.git' => 'facebook-php-sdk/'],
    'autoload_config' => ['facebook-php-sdk/src/Facebook/' => 'Facebook'],
    'example' => function () {
        Facebook\FacebookSession::setDefaultApplication('YOUR_APP_ID', 'YOUR_APP_SECRET');
        // Use one of the helper classes to get a FacebookSession object.
        // FacebookRedirectLoginHelper, FacebookCanvasLoginHelper, FacebookJavaScriptLoginHelper or create a FacebookSession with a valid access token:
        $session = new Facebook\FacebookSession('access-token-here');
        // Get the GraphUser object for the current user:
        try {
            $me = (new Facebook\FacebookRequest(
                $session,
                'GET',
                '/me'
            ))->execute()->getGraphObject(Facebook\GraphUser::className());
            echo $me->getName();
        } catch (Facebook\FacebookRequestException $e) {
            // The Graph API returned an error
            echo $e->getMessage() . PHP_EOL;
        } catch (\Exception $e) {
            // Some other error occurred
            echo $e->getMessage() . PHP_EOL;
        }
        var_dump($session);
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
