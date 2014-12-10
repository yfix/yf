#!/usr/bin/php
<?php

require __DIR__.'/bash_colors.php'; 

$colors = new bash_colors();
echo $colors->apply('SUCCESS', 'white', 'green') . PHP_EOL;
echo $colors->apply('WARNING', 'white', 'yellow') . PHP_EOL;
echo $colors->apply('INFO', 'white', 'blue') . PHP_EOL;
echo $colors->apply('ERROR', 'white', 'red') . PHP_EOL;

foreach ($colors->get_fg_colors() as $fg) {
	foreach ($colors->get_bg_colors() as $bg) {
		echo $colors->apply($fg.','.$bg, $fg, $bg).' ';
	}
	echo PHP_EOL;
}
