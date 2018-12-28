#!/usr/bin/php
<?php

$cache_dir = dirname(dirname(__DIR__)) . '/assets_cache/';

// https://api.github.com/repos/thomaspark/bootswatch/tags
$twbs_v4 = '4.0.0';
$twbs_v3 = '3.3.7';
$twbs_v2 = '2.3.2';
$fa3 = '3.2.1';
$fa4 = '4.7.0';
$jquery_v = '3.3.1';
$jquery_url = 'http://ajax.googleapis.com/ajax/libs/jquery/' . $jquery_v . '/jquery.min.js';

$dir_twbs4 = $cache_dir . 'bootswatch/' . $twbs_v4 . '/';
$dir_twbs3 = $cache_dir . 'bootswatch/' . $twbs_v3 . '/';
$dir_twbs2 = $cache_dir . 'bootswatch/' . $twbs_v2 . '/';

$themes_twbs4_file = $cache_dir . 'bootswatch/themes_twbs4.txt';
$themes_twbs3_file = $cache_dir . 'bootswatch/themes_twbs3.txt';
$themes_twbs2_file = $cache_dir . 'bootswatch/themes_twbs2.txt';

function save_url_to_file($url, $file)
{
    $dir = dirname($file);
    if ( ! file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
    $str = file_get_contents($url);
    if ( ! strlen($str)) {
        return false;
    }
    if (file_exists($file) && file_get_contents($file) === $str) {
        return true;
    }
    return file_put_contents($file, $str);
}

function get_urls_from_css($css)
{
    preg_match_all('~url\(\'(?P<url>.*?)\'\)~ims', $css, $m);
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
    // github requires http user agent string
    $opts = ['http' => ['method' => 'GET', 'header' => ['User-Agent: PHP']]];
    return file_get_contents($url, false, stream_context_create($opts));
}

function get_themes_twbs4()
{
    global $themes_twbs4_file, $twbs_v4;
    $gh_api_url = 'https://api.github.com/repos/thomaspark/bootswatch/contents/dist?ref=v' . $twbs_v4;
    $themes = [];
    foreach (json_decode(url_get($gh_api_url), $arr = true) as $v) {
        $name = $v['name'];
        if ($v['type'] !== 'dir') {
            continue;
        }
        if ( ! $name || $name === 'default') {
            continue;
        }
        $themes[$name] = $name;
    }
    if ($themes) {
        file_put_contents($themes_twbs4_file, trim(implode(PHP_EOL, $themes)));
    }
    if ( ! file_exists($themes_twbs4_file) || ! filesize($themes_twbs4_file)) {
        exit('ERROR: TWBS4 Themes not found');
    }
    return explode(PHP_EOL, trim(file_get_contents($themes_twbs4_file)));
}

function get_themes_twbs3()
{
    global $themes_twbs3_file, $twbs_v3;

    $gh_api_url = 'https://api.github.com/repos/thomaspark/bootswatch/contents/?ref=v' . $twbs_v3;
    $themes = [];
    foreach (json_decode(url_get($gh_api_url), $arr = true) as $v) {
        $name = $v['name'];
        if ($v['type'] !== 'dir') {
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
    if ( ! file_exists($themes_twbs3_file) || ! filesize($themes_twbs3_file)) {
        exit('ERROR: TWBS3 Themes not found');
    }
    return explode(PHP_EOL, trim(file_get_contents($themes_twbs3_file)));
}

function get_themes_twbs2()
{
    global $themes_twbs2_file, $twbs_v2;

    $gh_api_url = 'https://api.github.com/repos/thomaspark/bootswatch/contents/?ref=v' . $twbs_v2;
    $themes = [];
    foreach (json_decode(url_get($gh_api_url), $arr = true) as $v) {
        $name = $v['name'];
        if ($v['type'] !== 'dir') {
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
    if ( ! file_exists($themes_twbs2_file) || ! filesize($themes_twbs2_file)) {
        exit('ERROR: TWBS2 Themes not found');
    }
    return explode(PHP_EOL, trim(file_get_contents($themes_twbs2_file)));
}

// Bootstrap 4
foreach ((array) get_themes_twbs4() as $theme) {
    save_url_to_file(
        'http://netdna.bootstrapcdn.com/bootswatch/' . $twbs_v4 . '/' . $theme . '/bootstrap.min.css',
        $dir_twbs4 . '/' . $theme . '/bootstrap.min.css'
    );
}

save_url_to_file('http://netdna.bootstrapcdn.com/bootstrap/' . $twbs_v4 . '/css/bootstrap.min.css', $dir_twbs4 . 'default/bootstrap.min.css');
save_url_to_file('http://netdna.bootstrapcdn.com/bootstrap/' . $twbs_v4 . '/js/bootstrap.min.js', $dir_twbs4 . 'bootstrap.min.js');

// Bootstrap 3
foreach (get_themes_twbs3() as $theme) {
    save_url_to_file(
        'http://netdna.bootstrapcdn.com/bootswatch/' . $twbs_v3 . '/' . $theme . '/bootstrap.min.css',
        $dir_twbs3 . '/' . $theme . '/bootstrap.min.css'
    );
}
save_url_to_file('http://netdna.bootstrapcdn.com/bootstrap/' . $twbs_v3 . '/css/bootstrap.min.css', $dir_twbs3 . 'default/bootstrap.min.css');
save_url_to_file('http://netdna.bootstrapcdn.com/bootstrap/' . $twbs_v3 . '/css/bootstrap-theme.min.css', $dir_twbs3 . 'default/bootstrap-theme.min.css');
save_url_to_file('http://netdna.bootstrapcdn.com/bootstrap/' . $twbs_v3 . '/js/bootstrap.min.js', $dir_twbs3 . 'bootstrap.min.js');

// Bootstrap 2
foreach (get_themes_twbs2() as $theme) {
    save_url_to_file(
        'http://netdna.bootstrapcdn.com/bootswatch/' . $twbs_v2 . '/' . $theme . '/bootstrap.min.css',
        $dir_twbs2 . '/' . $theme . '/bootstrap.min.css'
    );
}
save_url_to_file('http://netdna.bootstrapcdn.com/twitter-bootstrap/' . $twbs_v2 . '/css/bootstrap-combined.min.css', $dir_twbs2 . 'default/bootstrap-combined.min.css');
save_url_to_file('http://netdna.bootstrapcdn.com/twitter-bootstrap/' . $twbs_v2 . '/js/bootstrap.min.js', $dir_twbs2 . 'bootstrap.min.js');

// Jquery
save_url_to_file($jquery_url, $cache_dir . 'jquery/' . $jquery_v . '/jquery.min.js');
save_url_to_file('http://ajax.googleapis.com/ajax/libs/jqueryui/' . $jquery_v . '/jquery-ui.min.js', $cache_dir . 'jquery-ui/' . $jquery_v . '/jquery-ui.min.js');

// Font Awesome
save_url_to_file('http://netdna.bootstrapcdn.com/font-awesome/' . $fa3 . '/css/font-awesome.min.css', $cache_dir . 'fontawesome/' . $fa3 . '/css/font-awesome.min.css');
foreach (get_urls_from_css(file_get_contents($cache_dir . 'fontawesome/' . $fa3 . '/css/font-awesome.min.css')) as $url) {
    save_url_to_file('http://netdna.bootstrapcdn.com/font-awesome/' . $fa3 . '/' . $url, $cache_dir . 'fontawesome/' . $fa3 . '/' . $url);
}
save_url_to_file('http://netdna.bootstrapcdn.com/font-awesome/' . $fa4 . '/css/font-awesome.min.css', $cache_dir . 'fontawesome/' . $fa4 . '/css/font-awesome.min.css');
foreach (get_urls_from_css(file_get_contents($cache_dir . 'fontawesome/' . $fa4 . '/css/font-awesome.min.css')) as $url) {
    save_url_to_file('http://netdna.bootstrapcdn.com/font-awesome/' . $fa4 . '/' . $url, $cache_dir . 'fontawesome/' . $fa4 . '/' . $url);
}
