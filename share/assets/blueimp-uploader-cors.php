<?php

return array(
	'versions' => array(
		'master' => array(
			'js' => array(
				'//cdn.rawgit.com/yfix/JQuery-File-Upload/master/js/cors/jquery.postmessage-transport.js',
				'//cdn.rawgit.com/yfix/JQuery-File-Upload/master/js/cors/jquery.xdr-transport.js',
			),
		),
	),
	'config' => array(
		'before' => '<!-- The XDomainRequest Transport is included for cross-domain file deletion for IE 8 and IE 9 -->'.PHP_EOL.'<!--[if (gte IE 8)&(lt IE 10)]>',
		'after' => '<![endif]-->',
	),
);
