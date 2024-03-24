#!/usr/bin/php
<?php

$config = [
    'git_urls' => ['https://github.com/nikic/PHP-Parser.git' => 'php-parser/'],
    'autoload_config' => ['php-parser/lib/PhpParser/' => 'PhpParser'],
    'example' => function () {
        $parser = (new PhpParser\ParserFactory())->create(PhpParser\ParserFactory::PREFER_PHP7);
        $prettyPrinter = new PhpParser\PrettyPrinter\Standard();
        $code = '<' . '?' . 'php echo \'Hi \', hi\\getTarget();';
        try {
            // parse
            $stmts = $parser->parse($code);
            // change
            $stmts[0]		 // the echo statement
                  ->exprs	 // sub expressions
                  [0]		 // the first of them (the string node)
                  ->value	 // it's value, i.e. 'Hi '
                  = 'Hello '; // change to 'Hello '
            // pretty print
            $code = $prettyPrinter->prettyPrint($stmts);
            echo $code;
        } catch (Error $e) {
            echo 'Parse Error: ', $e->getMessage();
        }
    },
];
if ($return_config) {
    return $config;
} require_once __DIR__ . '/_yf_autoloader.php'; new yf_autoloader($config);
