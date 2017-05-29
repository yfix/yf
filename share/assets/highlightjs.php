<?php

return [
	'versions' => [
		'8.0' => [
			'js' => [
				'//cdnjs.cloudflare.com/ajax/libs/highlight.js/8.0/highlight.min.js',
				'//cdnjs.cloudflare.com/ajax/libs/highlight.js/8.0/languages/php.min.js',
			],
			'css' => [
				'//cdnjs.cloudflare.com/ajax/libs/highlight.js/8.0/styles/railscasts.min.css',
			],
		],
	],
	'cdn' => [
		'url' => '//cdnjs.cloudflare.com/ajax/libs/highlight.js/{version}/',
		'version' => '8.0',
		'js' => [
			'highlight.min.js',
			'languages/php.min.js',
		],
		'css' => [
			'styles/railscasts.min.css',
		],
	],
	'add' => [
		'js' => [
			'hljs.initHighlightingOnLoad();',
		],
		'css' => [
			'section.page-contents pre, pre.prettyprint { background-color: transparent; border: 0; font-family: inherit; font-size: inherit; font-weight: bold; }',
		],
	],
];
