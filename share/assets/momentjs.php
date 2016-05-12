<?php

return function() {

$lang = conf('language');
return array(
	'versions' => array(
		'2.13.0' => array(
			'js' => array(
				'//cdnjs.cloudflare.com/ajax/libs/moment.js/2.13.0/moment-with-locales.min.js',
				'moment.locale("'.$lang.'");'
			),
		),
	),
	'info' => array(
		'url' => 'http://momentjs.com/',
		'name' => 'Parse, validate, manipulate, and display dates in JavaScript.',
		'desc' => 'Moment was designed to work both in the browser and in Node.js.
			All code should work in both of these environments, and all unit tests are run in both of these environments.',
		'git' => 'https://github.com/moment/moment.git',
	),
);

};