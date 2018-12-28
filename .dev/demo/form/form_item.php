<?php

return function () {
    return implode(PHP_EOL, [
        form_item()->country_box(['selected' => 'US', 'renderer' => 'div_box']),
        form_item()->language_box(['selected' => 'ru', 'renderer' => 'div_box']),
        form_item()->currency_box(['selected' => 'UAH', 'renderer' => 'div_box']),
        form_item()->timezone_box(['selected' => 'UTC', 'renderer' => 'div_box']),
    ]);
};
