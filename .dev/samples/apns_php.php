<?php

# git clone git@github.com:yfix/ApnsPHP.git /home/www/yf/libs/apns_php/

define('YF_PATH', dirname(dirname(__DIR__)).'/');

require_once YF_PATH . 'libs/apns_php/ApnsPHP/Autoload.php';
$server = new ApnsPHP_Push_Server(ApnsPHP_Abstract::ENVIRONMENT_SANDBOX, 'server_certificates_bundle_sandbox.pem');
var_dump($server);
