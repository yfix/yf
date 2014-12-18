<?php

return array(
	'versions' => array(
		'8.0' => array(
			'js' => array(
				'//cdnjs.cloudflare.com/ajax/libs/highlight.js/8.0/highlight.min.js',
				'//cdnjs.cloudflare.com/ajax/libs/highlight.js/8.0/languages/php.min.js',
				'hljs.initHighlightingOnLoad();',
			),
			'css' => array(
				'//cdnjs.cloudflare.com/ajax/libs/highlight.js/8.0/styles/railscasts.min.css',
				'section.page-contents pre, pre.prettyprint { background-color: transparent; border: 0; font-family: inherit; font-size: inherit; font-weight: bold; }',
			),
		),
	),
);
