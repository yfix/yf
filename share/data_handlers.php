<?php

$h_suffix = '.h.php';
$h_dir = 'share/data_handlers/';
$globs = array(
	'yf_main'				=> YF_PATH. $h_dir. '*'. $h_suffix,
	'yf_plugins'			=> YF_PATH. 'plugins/*/'. $h_dir. '*'. $h_suffix,
	'project_main'			=> PROJECT_PATH. $h_dir. '*'. $h_suffix,
	'project_app'			=> APP_PATH. $h_dir. '*'. $h_suffix,
	'project_plugins'		=> PROJECT_PATH. 'plugins/*/'. $h_dir. '*'. $h_suffix,
	'project_app_plugins'	=> APP_PATH. 'plugins/*/'. $h_dir. '*'. $h_suffix,
);
$handlers = array();
$strlen_suffix = strlen($h_suffix);
foreach($globs as $gname => $glob) {
	foreach(glob($glob) as $path) {
		$name = substr(basename($path), 0, -$strlen_suffix);
		$handlers[$name] = $path;
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
main()->data_handlers = (array)main()->data_handlers + $handlers;
