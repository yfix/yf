<?php

return function($params = array()) {
	$locale = $params['locale'] ?: ($params['lang'] ?: conf('language'));
	return db()->from('tips')->where('locale', $locale)->get_all(null, null, 'name');
};
