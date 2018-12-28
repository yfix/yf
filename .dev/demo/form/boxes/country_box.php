<?php

return function () {
    return form()
        ->country_box(['selected' => 'US'])
        ->country_box(['selected' => 'US', 'renderer' => 'select2_box'])
        ->country_box(['selected' => ['US' => 'US', 'ES' => 'ES'], 'renderer' => 'select2_box', 'multiple' => 1, 'js_options' => ['width' => '400px', 'allowClear' => 'true']])
        ->country_box(['selected' => 'US', 'renderer' => 'chosen_box'])
        ->country_box(['selected' => 'US', 'renderer' => 'chosen_box', 'multiple' => 1])
        ->country_box(['selected' => 'US', 'renderer' => 'select_box'])
        ->country_box(['selected' => 'US', 'renderer' => 'multi_select_box'])
        ->country_box(['selected' => 'US', 'renderer' => 'multi_check_box'])
        ->country_box(['selected' => 'US', 'renderer' => 'radio_box'])
        ->country_box(['selected' => 'US', 'renderer' => 'radio_box', 'row_tpl' => '%name %icon'])
        ->country_box(['selected' => 'US', 'renderer' => 'radio_box', 'horizontal' => '0'])
        ->country_box(['selected' => 'US', 'renderer' => 'div_box'])
        ->country_box(['selected' => 'US', 'renderer' => 'button_box'])
        ->country_box(['selected' => 'US', 'renderer' => 'button_split_box']);
};
