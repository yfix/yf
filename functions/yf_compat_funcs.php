<?php

if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle)
    {
        $strlen_needle = mb_strlen($needle);
        if (mb_substr($haystack, 0, $strlen_needle) == $needle) {
            return true;
        }
        return false;
    }
}

if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle)
    {
        $strlen_needle = mb_strlen($needle);
        if (mb_substr($haystack, -$strlen_needle, $strlen_needle) == $needle) {
            return true;
        }
        return false;
    }
}
