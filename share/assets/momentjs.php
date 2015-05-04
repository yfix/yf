<?php

return function() {

$lang = conf('language');
return array(
	'versions' => array(
		'2.8.3' => array(
			'js' => '//cdnjs.cloudflare.com/ajax/libs/moment.js/2.8.3/moment-with-locales.min.js',
		),
		'2.10.2' => array(
			'js' => array(
				'//cdnjs.cloudflare.com/ajax/libs/moment.js/2.10.2/moment-with-locales.min.js',
				'moment.locale("'.$lang.'");'
			),
		),
	),
);

};