<?php

$path = @$DIR_TO_CHECK ?: ($argv[1] ?? dirname(dirname(dirname(__DIR__))));

$grep_regex = '//(cdn|netdna|rawgit)';
$min_regex = '~\{[^\}]*?min[^\}]*?\}~';
$url_regex = '~(?P<url>//(cdn|netdna|rawgit).+?\.(js|css))[\'"\{]+~ims';

$matches = [];
// /home/www/yf/.dev/samples/assets_prototype.php:
// '//rawgit.yfix.net/yfix/jQuery-File-Upload/master/js/jquery.fileupload-validate.js',
exec('egrep "' . $grep_regex . '" --include="*.php" -r "' . $path . '" --exclude-dir="libs" --exclude-dir="vendor" --exclude="' . basename(__FILE__) . '"', $matches);

// /home/www/yf/templates/admin/ng_app_lib.stpl:
// <link href="//rawgit.yfix.net/mgcrea/angular-motion/master/dist/angular-motion{js_min}.css" rel="stylesheet">
exec('egrep "' . $grep_regex . '" --include="*.stpl" -r "' . $path . '" --exclude-dir="libs" --exclude-dir="vendor"', $matches); // note that matches added to previous

foreach ((array) $matches as $k => $v) {
    if (preg_match($min_regex, $v)) {
        $matches[$k] = preg_replace($min_regex, '.min', $v);
        $matches[] = preg_replace($min_regex, '', $v);
    }
}
$urls = [];
$paths = [];
foreach ((array) $matches as $v) {
    $path = substr($v, 0, strpos($v, ':'));
    if (!preg_match($url_regex, trim(substr($v, strlen($path . ':'))), $m)) {
        continue;
    }
    $url = trim($m['url']);
    if (!strlen($url) || false !== strpos($url, '$') || false !== strpos($url, '<') || false !== strpos($url, '{')) {
        continue;
    }
    $urls[$url] = $url;
    $paths[$url][$path] = $path;
}
return [
    'urls' => $urls,
    'paths' => $paths,
];
