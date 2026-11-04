<?php

return function () {
    if (isset($_GET['id'])) {
        list($id, $ext) = array_pad(explode('.', ($_GET['id'] ?? '')), 2, null);
        $color_bg = ($_GET['page'] ?? null);
    } else {
        $parts = explode('/', substr(($_SERVER['REQUEST_URI'] ?? ''), strlen('/dynamic/placeholder/')));
        list($id, $ext) = array_pad(explode('.', $parts[0]), 2, null);
        $color_bg = $parts[1] ?? null;
    }
    list($w, $h) = array_pad(explode('x', $id), 2, null);
    $w = (int) $w ?: 100;
    $h = (int) $h ?: 100;
    $params['color_bg'] = $color_bg ? preg_replace('[^a-z0-9]', '', $color_bg) : '';

    require_once YF_PATH . 'functions/yf_placeholder_img.php';
    echo yf_placeholder_img($w, $h, $params);

    return true; // Means success
};
