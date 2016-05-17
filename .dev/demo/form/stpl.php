<?php

return function() {
	$stpl = '
		{form.begin}

		{form._token}

		<div class="pull-left col-md-3">
			{form.currency}
			{form.language}
		</div>
		<div class="pull-center col-md-3">
			{form.country}
		</div>
		<div class="pull-center col-md-3">
			<h1>Some header</h1>
		</div>
		<div class="pull-right col-md-3">
			{form.timezone}
		</div>

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
