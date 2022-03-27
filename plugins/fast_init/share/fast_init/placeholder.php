<?php

return function () {
    if (isset($_GET['id'])) {
        list($id, $ext) = explode('.', $_GET['id']);
        $color_bg = $_GET['page'];
    } else {
        $parts = explode('/', substr($_SERVER['REQUEST_URI'], strlen('/dynamic/placeholder/')));
        list($id, $ext) = explode('.', $parts[0]);
        $color_bg = $parts[1];
    }
    list($w, $h) = explode('x', $id);
    $w = (int) $w ?: 100;
    $h = (int) $h ?: 100;
    $params['color_bg'] = $color_bg ? preg_replace('[^a-z0-9]', '', $color_bg) : '';

    require_once YF_PATH . 'functions/yf_placeholder_img.php';
    echo yf_placeholder_img($w, $h, $params);

    return true; // Means success
};
