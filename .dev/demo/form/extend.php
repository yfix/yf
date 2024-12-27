<?php

return function () {
    main()->extend('form2', 'new_control', function ($name, $desc = '', $extra = [], $replace = [], $_this) {
        return $_this->input($name, $desc, $extra, $replace);
    });
    return form($r ?? [])
        ->new_control('Hello', 'world')
        ->save();
};
