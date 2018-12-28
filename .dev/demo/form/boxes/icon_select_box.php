<?php

return function () {
    return form()
        ->icon_select_box(['selected' => 'icon-anchor'])
        ->icon_select_box(['selected' => 'icon-anchor', 'row_tpl' => '%name %icon']);
};
