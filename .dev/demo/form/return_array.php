<?php

return function() {
	$form = form((array)$_POST + (array)$a, [
			'return_array' => true,
		])
		->currency_box(['selected' => 'RUB'])
		->language_box(['selected' => 'uk'])
		->timezone_box(['selected' => 'UTC'])
		->country_box(['selected' => 'US'])
		->render()
	;
	return '<pre><code>'._prepare_html(print_r($form, 1)).'</code></pre>';
};
