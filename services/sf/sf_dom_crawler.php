#!/usr/bin/php
<?php

$config = [
    'require_services' => ['sf_css_selector'],
    'git_urls' => ['https://github.com/yfix/DomCrawler.git' => 'sf_dom_crawler/'],
    'autoload_config' => ['sf_dom_crawler/' => 'Symfony\Component\DomCrawler'],
    'example' => function () {
        $crawler = new \Symfony\Component\DomCrawler\Crawler();
        $crawler->addContent('<html><body><p>Hello World!</p></body></html>');
        echo $crawler->filterXPath('descendant-or-self::body/p')->text();
        echo PHP_EOL;
        echo $crawler->filter('body > p')->text(); // require css selector
        echo PHP_EOL;
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
