<?php

// Fast execute php function (specially for the threaded execution)
return function () {
    if (MAIN_TYPE_ADMIN !== true) {
        return false;
    }
    main()->NO_GRAPHICS = true;
    // Check if console mode
    if ( ! ( ! empty($_SERVER['argc']) && ! isset($_SERVER['REQUEST_METHOD']))) {
        exit('No direct access to method allowed');
    }
    // Get console params
    $params = [];
    foreach ((array) $_SERVER['argv'] as $key => $argv) {
        if ($argv == '--params' && isset($_SERVER['argv'][$key + 1])) {
            $params = unserialize($_SERVER['argv'][$key + 1]);
            break;
        }
    }
    $func = preg_replace('#[^a-z0-9\_]+#', '', substr(trim($params['func']), 0, 32));
    if (function_exists($func)) {
        echo $func($params['name']);
    } else {
        return false;
    }
    main()->_no_fast_init_debug = true;
    return true; // Means success
};
