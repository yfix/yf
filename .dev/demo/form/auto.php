<?php

return function () {
	return form('', [
		'no_form'	=> 1,
		'tabs'		=> [
			'class'		=> 'span6 col-md-6',
			'show_all'	=> 1,
			'no_headers'=> 1,
		],
	])
	->tab_start('auto2')
	->container(
		form()->auto(db('static_pages'), 1)
	)
	->tab_end();
};
