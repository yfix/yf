<?php

function get_data_from_url($path, $url, $sleep = 0) {
	if (!file_exists($path)/* || filemtime($path) < (time() - 86400)*/) {
		passthru('wget "'.$url.'" -O '.$path);
		if ($sleep) {
			sleep($sleep); // Need to avoid github rate limiting
		}
	}
	return json_decode(file_get_contents($path), true);
}
function save_php_data($path, $data) {
	$res = str_replace('array (', 'array(', var_export($data, 1));
	$res = preg_replace('~=>[\s\t]+array\(~ims', '=> array(', $res);
	$res = str_replace('  ', "\t", $res);
	file_put_contents($path, '<?'.'php'.PHP_EOL. '$data = '.$res. PHP_EOL.';');
}
