<?php

return [
	'versions' => [
		'1.3.0' => [
			'js' => '//cdnjs.cloudflare.com/ajax/libs/moment-duration-format/1.3.0/moment-duration-format.min.js',
		],
	],
	'require' => [
		'asset' => 'momentjs',
	],
	'info' => [
		'url' => 'https://github.com/jsmreese/moment-duration-format',
		'name' => 'A moment.js plugin for formatting durations.',
		'desc' => 'This is a plugin to the Moment.js JavaScript date library to add comprehensive formatting to Moment Durations.
			Format template grammar is patterned on the existing Moment Date format template grammar, with a few modifications because durations are fundamentally different from dates.
			This plugin does not have any dependencies beyond Moment.js itself, and may be used in the browser and in Node.js.',
		'git' => 'https://github.com/jsmreese/moment-duration-format.git',
	],
];
