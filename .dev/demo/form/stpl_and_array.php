<?php

return function () {
    $stpl = '
		{form.begin}

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
    return tpl()->parse_string($stpl, [
        'form' => form((array) $_POST + (array) $a, [
            'return_array' => true,
        ])
        ->currency_box(['selected' => 'RUB'])
        ->language_box(['selected' => 'uk'])
        ->timezone_box(['selected' => 'UTC'])
        ->country_box(['selected' => 'US'])
        ->save()
        ->render(),
    ]);
};
