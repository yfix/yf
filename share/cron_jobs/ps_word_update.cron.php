<?php

return function() {
	_class('ps_word', USER_MODULES_DIR)->_refresh_stats();
	return 'Popular Searches: Updated number of search results for all keywords';
};
