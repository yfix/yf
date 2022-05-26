#!/usr/bin/env php
<?php

$cache_dir = dirname(dirname(__DIR__)) . '/assets_cache/';

// https://api.github.com/repos/thomaspark/bootswatch/tags
$twbs_v2 = '2.3.2';
$twbs_v3 = '3.4.1';
$twbs_v4 = '4.6.1';
$twbs_v5 = '5.1.3';
$fa3 = '3.2.1';
$fa4 = '4.7.0';
$fa5 = '5.15.4';
$fa6 = '6.1.1';
$jquery_v = '3.6.0';
$jquery_ui_v = '1.12.1';

$dir_twbs2 = $cache_dir . 'bootswatch/' . $twbs_v2 . '/';
$dir_twbs3 = $cache_dir . 'bootswatch/' . $twbs_v3 . '/';
$dir_twbs4 = $cache_dir . 'bootswatch/' . $twbs_v4 . '/';
$dir_twbs5 = $cache_dir . 'bootswatch/' . $twbs_v5 . '/';

$themes_twbs2_file = $cache_dir . 'bootswatch/themes_twbs2.txt';
$themes_twbs3_file = $cache_dir . 'bootswatch/themes_twbs3.txt';
$themes_twbs4_file = $cache_dir . 'bootswatch/themes_twbs4.txt';
$themes_twbs5_file = $cache_dir . 'bootswatch/themes_twbs5.txt';

function save_url_to_file($url, $file)
{
    $dir = dirname($file);
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
    $str = file_get_contents($url);
    if (!strlen($str)) {
        return false;
    }
    if (file_exists($file) && file_get_contents($file) === $str) {
        return true;
    }
    return file_put_contents($file, $str);
}

function get_urls_from_css($css)
{
    preg_match_all('~url\([\']?(?P<url>.*?)[\']?\)~ims', $css, $m);
    $urls = [];
    foreach ((array) $m['url'] as $url) {
        if (substr($url, 0, strlen('../')) === '../') {
            $url = substr($url, strlen('../'));
        }
        if (false !== ($pos = strpos($url, '#'))) {
            $url = substr($url, 0, $pos);
        }
        if (false !== ($pos = strpos($url, '?'))) {
            $url = substr($url, 0, $pos);
        }
        $urls[$url] = $url;
    }
    return $urls;
}

function url_get($url)
{
    echo $url . PHP_EOL;
    // github requires http user agent string
    $opts = ['http' => ['method' => 'GET', 'header' => ['User-Agent: PHP'], 'timeout' => 10]];
    return file_get_contents($url, false, stream_context_create($opts));
}

// Jquery
save_url_to_file('https://ajax.googleapis.com/ajax/libs/jquery/' . $jquery_v . '/jquery.min.js', $cache_dir . 'jquery/' . $jquery_v . '/jquery.min.js');

// Jquery UI
save_url_to_file('https://ajax.googleapis.com/ajax/libs/jqueryui/' . $jquery_ui_v . '/jquery-ui.min.js', $cache_dir . 'jquery-ui/' . $jquery_ui_v . '/jquery-ui.min.js');

// Font Awesome
save_url_to_file('https://netdna.bootstrapcdn.com/font-awesome/' . $fa3 . '/css/font-awesome.min.css', $cache_dir . 'fontawesome/' . $fa3 . '/css/font-awesome.min.css');
foreach (get_urls_from_css(file_get_contents($cache_dir . 'fontawesome/' . $fa3 . '/css/font-awesome.min.css')) as $url) {
    save_url_to_file('https://netdna.bootstrapcdn.com/font-awesome/' . $fa3 . '/' . $url, $cache_dir . 'fontawesome/' . $fa3 . '/' . $url);
}
save_url_to_file('https://netdna.bootstrapcdn.com/font-awesome/' . $fa4 . '/css/font-awesome.min.css', $cache_dir . 'fontawesome/' . $fa4 . '/css/font-awesome.min.css');
foreach (get_urls_from_css(file_get_contents($cache_dir . 'fontawesome/' . $fa4 . '/css/font-awesome.min.css')) as $url) {
    save_url_to_file('https://netdna.bootstrapcdn.com/font-awesome/' . $fa4 . '/' . $url, $cache_dir . 'fontawesome/' . $fa4 . '/' . $url);
}
save_url_to_file('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/' . $fa5 . '/css/all.min.css', $cache_dir . 'fontawesome/' . $fa5 . '/css/all.min.css');
foreach (get_urls_from_css(file_get_contents($cache_dir . 'fontawesome/' . $fa5 . '/css/all.min.css')) as $url) {
    save_url_to_file('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/' . $fa5 . '/' . $url, $cache_dir . 'fontawesome/' . $fa5 . '/' . $url);
}
save_url_to_file('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/' . $fa6 . '/css/all.min.css', $cache_dir . 'fontawesome/' . $fa6 . '/css/all.min.css');
foreach (get_urls_from_css(file_get_contents($cache_dir . 'fontawesome/' . $fa6 . '/css/all.min.css')) as $url) {
    save_url_to_file('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/' . $fa6 . '/' . $url, $cache_dir . 'fontawesome/' . $fa6 . '/' . $url);
}

// Bootstrap 2
function get_themes_twbs2()
{
    global $themes_twbs2_file, $twbs_v2;

    $gh_api_url = 'https://api.github.com/repos/thomaspark/bootswatch/contents/?ref=v' . $twbs_v2;
    $themes = [];
    foreach (json_decode(url_get($gh_api_url), $arr = true) as $v) {
        $name = $v['name'];
        if ($v['type'] !== 'dir' || substr($name, 0, 1) === '.') {
            continue;
        }
        if (in_array($name, ['swatchmaker', 'js', 'img', 'global', 'font', 'default', 'css', 'api'])) {
            continue;
        }
        $themes[$name] = $name;
    }
    if ($themes) {
        file_put_contents($themes_twbs2_file, trim(implode(PHP_EOL, $themes)));
    }
    if (!file_exists($themes_twbs2_file) || !filesize($themes_twbs2_file)) {
        exit('ERROR: TWBS2 Themes not found');
    }
    return explode(PHP_EOL, trim(file_get_contents($themes_twbs2_file)));
}
foreach (get_themes_twbs2() as $theme) {
    save_url_to_file(
        'https://netdna.bootstrapcdn.com/bootswatch/' . $twbs_v2 . '/' . $theme . '/bootstrap.min.css',
        $dir_twbs2 . '/' . $theme . '/bootstrap.min.css'
    );
}
save_url_to_file('https://netdna.bootstrapcdn.com/twitter-bootstrap/' . $twbs_v2 . '/css/bootstrap-combined.min.css', $dir_twbs2 . 'default/bootstrap-combined.min.css');
save_url_to_file('https://netdna.bootstrapcdn.com/twitter-bootstrap/' . $twbs_v2 . '/js/bootstrap.min.js', $dir_twbs2 . 'bootstrap.min.js');

// Bootstrap 3
function get_themes_twbs3()
{
    global $themes_twbs3_file, $twbs_v3;

    $gh_api_url = 'https://api.github.com/repos/thomaspark/bootswatch/contents/?ref=v' . $twbs_v3;
    $themes = [];
    foreach (json_decode(url_get($gh_api_url), $arr = true) as $v) {
        $name = $v['name'];
        if ($v['type'] !== 'dir' || substr($name, 0, 1) === '.') {
            continue;
        }
        if (in_array($name, ['tests', 'help', 'global', 'fonts', 'default', 'custom', 'bower_components', 'assets', 'api', '2'])) {
            continue;
        }
        $themes[$name] = $name;
    }
    if ($themes) {
        file_put_contents($themes_twbs3_file, trim(implode(PHP_EOL, $themes)));
    }
    if (!file_exists($themes_twbs3_file) || !filesize($themes_twbs3_file)) {
        exit('ERROR: TWBS3 Themes not found');
    }
    return explode(PHP_EOL, trim(file_get_contents($themes_twbs3_file)));
}
foreach (get_themes_twbs3() as $theme) {
    save_url_to_file(
        'https://netdna.bootstrapcdn.com/bootswatch/' . $twbs_v3 . '/' . $theme . '/bootstrap.min.css',
        $dir_twbs3 . '/' . $theme . '/bootstrap.min.css'
    );
}
save_url_to_file('https://netdna.bootstrapcdn.com/bootstrap/' . $twbs_v3 . '/css/bootstrap.min.css', $dir_twbs3 . 'default/bootstrap.min.css');
save_url_to_file('https://netdna.bootstrapcdn.com/bootstrap/' . $twbs_v3 . '/css/bootstrap-theme.min.css', $dir_twbs3 . 'default/bootstrap-theme.min.css');
save_url_to_file('https://netdna.bootstrapcdn.com/bootstrap/' . $twbs_v3 . '/js/bootstrap.min.js', $dir_twbs3 . 'bootstrap.min.js');

// Bootstrap 4
function get_themes_twbs4()
{
    global $themes_twbs4_file, $twbs_v4;
    $gh_api_url = 'https://api.github.com/repos/thomaspark/bootswatch/contents/dist?ref=v' . $twbs_v4;
    $themes = [];
    foreach (json_decode(url_get($gh_api_url), $arr = true) as $v) {
        $name = $v['name'];
        if ($v['type'] !== 'dir' || substr($name, 0, 1) === '.') {
            continue;
        }
        if (!$name || $name === 'default') {
            continue;
        }
        $themes[$name] = $name;
    }
    if ($themes) {
        file_put_contents($themes_twbs4_file, trim(implode(PHP_EOL, $themes)));
    }
    if (!file_exists($themes_twbs4_file) || !filesize($themes_twbs4_file)) {
        exit('ERROR: TWBS4 Themes not found');
    }
    return explode(PHP_EOL, trim(file_get_contents($themes_twbs4_file)));
}
foreach ((array) get_themes_twbs4() as $theme) {
    save_url_to_file(
        'https://cdn.jsdelivr.net/npm/bootswatch@' . $twbs_v4 . '/dist/' . $theme . '/bootstrap.min.css',
        $dir_twbs4 . '/' . $theme . '/bootstrap.min.css'
    );
}
save_url_to_file('https://cdn.jsdelivr.net/npm/bootstrap@' . $twbs_v4 . '/dist/css/bootstrap.min.css', $dir_twbs4 . 'default/bootstrap.min.css');
save_url_to_file('https://cdn.jsdelivr.net/npm/bootstrap@' . $twbs_v4 . '/dist/js/bootstrap.min.js', $dir_twbs4 . 'bootstrap.min.js');
// save_url_to_file('https://cdn.jsdelivr.net/npm/bootstrap@' . $twbs_v4 . '/dist/js/bootstrap.bundle.min.js', $dir_twbs4 . 'bootstrap.bundle.min.js');

// Bootstrap 5
function get_themes_twbs5()
{
    global $themes_twbs5_file, $twbs_v5;
    $gh_api_url = 'https://api.github.com/repos/thomaspark/bootswatch/contents/dist?ref=v' . $twbs_v5;
    $themes = [];
    foreach (json_decode(url_get($gh_api_url), $arr = true) as $v) {
        $name = $v['name'];
        if ($v['type'] !== 'dir' || substr($name, 0, 1) === '.') {
            continue;
        }
        if (!$name || $name === 'default') {
            continue;
        }
        $themes[$name] = $name;
    }
    if ($themes) {
        file_put_contents($themes_twbs5_file, trim(implode(PHP_EOL, $themes)));
    }
    if (!file_exists($themes_twbs5_file) || !filesize($themes_twbs5_file)) {
        exit('ERROR: TWBS5 Themes not found');
    }
    return explode(PHP_EOL, trim(file_get_contents($themes_twbs5_file)));
}
foreach ((array) get_themes_twbs5() as $theme) {
    save_url_to_file(
        'https://cdn.jsdelivr.net/npm/bootswatch@' . $twbs_v5 . '/dist/' . $theme . '/bootstrap.min.css',
        $dir_twbs5 . '/' . $theme . '/bootstrap.min.css'
    );
}
save_url_to_file('https://cdn.jsdelivr.net/npm/bootstrap@' . $twbs_v5 . '/dist/css/bootstrap.min.css', $dir_twbs5 . 'default/bootstrap.min.css');
save_url_to_file('https://cdn.jsdelivr.net/npm/bootstrap@' . $twbs_v5 . '/dist/js/bootstrap.min.js', $dir_twbs5 . 'bootstrap.min.js');
// save_url_to_file('https://cdn.jsdelivr.net/npm/bootstrap@' . $twbs_v5 . '/dist/js/bootstrap.bundle.min.js', $dir_twbs5 . 'bootstrap.bundle.min.js');
