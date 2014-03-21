<?php

$handlers_dirs = array(
	'yf_main'			=> YF_PATH.'share/data_handlers/',
	'yf_plugins'		=> YF_PATH.'plugins/*/share/data_handlers/',
	'project_main'		=> PROJECT_PATH.'share/data_handlers/',
	'project_plugins'	=> PROJECT_PATH.'plugins/*/share/data_handlers/',
);
$handlers = array();
$h_suffix = '.h.php';
$strlen_suffix = strlen($h_suffix);
foreach ($handlers_dirs as $handlers_dir) {
	$strlen_dir = strlen($handlers_dir);
	foreach(glob($handlers_dir.'*'.$h_suffix) as $f) {
		$name = substr($f, $strlen_dir, -$strlen_suffix);
		$handlers[$name] = 'include("'.$f.'");';
	}
}
$handlers_aliases = array(
	'category_sets'	=> 'cats_blocks',
	'sys_sites'		=> 'sites',
	'sys_servers'	=> 'servers',
);
foreach ((array)$handlers_aliases as $from => $to) {
	$handlers[$from] = $handlers[$to];
}
conf('data_handlers', (array)conf('data_handlers') + $handlers);
