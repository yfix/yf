<?php

$PROJECT_CONF = my_array_merge($PROJECT_CONF, [
	'site_map' => [
		// 'TEST_MODE'            => true,
	],
	'send_mail' => [
		'USE_MAILER'           => 'phpmailer',
		'DEFAULT_CHARSET'      => 'UTF-8',
		'MAIL_DEBUG'           => true,
		'DEBUG_TEST_SEND_BULK' => true,
		'DEBUG_TEST_ADDRESS'   => 'debug@test.dev',
	],
]);

#$CONF['DEBUG_CONSOLE_POPUP' ] = 1;
#$PROJECT_CONF['_shop_region']['ENABLE'] = true;
