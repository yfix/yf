<?php
if (!defined('H_PATH')) {
	define('H_PATH', YF_PATH.'share/data_handlers/');
}
$handlers = array();
$strlen_dir = strlen(H_PATH);
$strlen_suffix = strlen('.h.php');
foreach(glob(H_PATH.'*.h.php') as $f) {
	$name = substr($f, $strlen_dir, -$strlen_suffix);
	$handlers[$name] = 'include("'.$f.'");';
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
