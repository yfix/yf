<?php

return function() {
	$stpl = '
		{form.begin}

		{form.timezone}	<br> {form.country}
		<br>
		<br>
		{form.currency} <br> {form.language}

		{form._token}

		{form.end}
	';
	return form((array)$_POST + (array)$a, [
			'stpl' => $stpl,
		])
#		->validate([])
		->currency_box(['selected' => 'RUB'])
		->language_box(['selected' => 'uk'])
		->timezone_box(['selected' => 'UTC'])
		->country_box(['selected' => 'US'])
	;
};
