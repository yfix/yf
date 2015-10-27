<?php

return array(
	'versions' => array(
		'master' => array(
//			'js' => '//cdn.rawgit.com/yfix/typeahead.js/master/dist/typeahead.jquery.js',
			'js' => '//cdn.rawgit.com/yfix/typeahead.js/master/dist/typeahead.bundle.min.js',
/*
			'css' => '
				.tt-dataset { color: #999; }
				.tt-highlight { color: #555; }
				.tt-menu { width: 400px; margin-top: 10px; padding: 5px 0; background: #fff; border: 1px solid #ccc; border-radius: 5px; text-shadow: none; }
				.tt-suggestion { padding: 3px 10px; line-height: 1.3em; }
				.tt-suggestion.tt-cursor { color: #fff; background-color: #0097cf; }
				.tt-suggestion p { margin: 0; }
				.tt-suggestion:hover { background: #eee; }
				.tt-selectable { cursor: pointer; }
			',
*/
		),
	),
	'require' => array(
		'asset' => 'jquery',
	),
);
