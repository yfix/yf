<?php

set_include_path (dirname(__FILE__)."/phpseclib/". PATH_SEPARATOR. get_include_path());

include('Crypt/RSA.php');
include('Net/SSH2.php');

$key = new Crypt_RSA();
//$key->setPassword('whatever');
$key->loadKey(file_get_contents('d:/www/htdocs/toggle2_remote/scripts/yurikey.pem'));

$ssh = new Net_SSH2('ns211349.ovh.net');
if (!$ssh->login('root', $key)) {
	exit('Login Failed');
}

#echo $ssh->exec('pwd');
echo $ssh->exec('ls -la');