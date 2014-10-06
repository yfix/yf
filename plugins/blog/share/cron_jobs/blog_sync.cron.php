<?php

return function() {
	module('blog')->_update_all_stats();
	return 'Blog stats re-counted';
};
