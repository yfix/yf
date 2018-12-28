<?php

return function () {
    $data = json_decode(file_get_contents(__DIR__ . '/products.json'), true);
    return table($data)->auto();
};
