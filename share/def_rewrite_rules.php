<?php

// REWRITE LINKS SECTION

define('REWRITE_ADD_EXT', '/'); // Extension to add
// PCRE patterns to rewrite links
if (!isset($GLOBALS['REWRITE_PATTERNS'])) {
	$GLOBALS['REWRITE_PATTERNS'] = array();
}
$GLOBALS['REWRITE_PATTERNS'] = my_array_merge((array)$GLOBALS['REWRITE_PATTERNS'], array(
	'/(\.\/[\?]{0,1})(.*?)&language=([a-z]{2})(.*?)/i'
		=> '\1\3/\2\4',	// Move language code to the begining
	'/&language=([&]*?)/i'
		=> '\1',			// Cut empty language var
	// Other core rewrites
	'/object=login_form&go_url=(.+?)/i'		=> 'login/\1',
	'/^(.*?)(task|object)+=(.*?)$/i'		=> '\1\3',
	'/&page=\./i'							=> '.',
	'/&(action|page|id|post_id)=/i'			=> '/',
	'/\.\/[\?]{0,1}/i'						=> WEB_PATH,
	'/(search|reviews_search)&/i'			=> '\1/',
	'/[&]*?debug_mode=/i'					=> '',
	'/\/0\//i'								=> '/',
	'/\/$/i'								=> '',			// Cut last slash
));
?>