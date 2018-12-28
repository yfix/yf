<?php

return function () {
    return form()
        ->currency_box(['selected' => 'RUB'])
        ->currency_box(['selected' => 'RUB', 'row_tpl' => '%code %name %sign', 'renderer' => 'div_box']);
};
