#!/usr/bin/php
<?php

$f = '/home/www/yf/classes/yf_validate.class.php';

#$regex = '~/\*\*[\s\t\n]*(?P<desc>.*?)\*/[\s\t\n]*[\s\t]*(public|private|protected|static)?[\s\t]+function[\s\t]+(?P<name>[a-z0-9_]+)[\s\t]*\(?P<args>[^\)]+\)~ims';
#$regex = '~/\*\*[\s\t]*(?P<desc>.*?)\*/[\s\t]*function[\s\t]+(?P<name>[a-z][a-z0-9_]+)[\s\t]*\(?P<args>[^\)]+\)~ims';
$regex = '~function[\s\t]+(?P<name>[a-z][a-z0-9_]+)[\s\t]*\((?P<args>[^\{]+)[\s\t]*\)[\s\t]*\{~ims';

$s = file_get_contents($f);
preg_match_all($regex, $s, $m);
print_r($m[0]);

/*
$all_tokens = token_get_all($s);
foreach ($all_tokens as $k => $t) {
	if (!in_array($t[0], array(T_DOC_COMMENT, T_FUNCTION))) {
		continue;
	}
	if ($t[0] == T_DOC_COMMENT) {
		echo $t[1]. PHP_EOL;
	} elseif ($t[0] == T_FUNCTION) {
#print_r($t);
		foreach ($all_tokens as $k2 => $t2) {
			if ($k2 <= $k1) {
				continue;
			}
			if ($t2[0] && $t2[2] != $t[2]) {
				continue;
			}
#			echo $t[1];
#print_r($t);
		}
	}
}
*/
#print_r(token_get_all(file_get_contents($f)));