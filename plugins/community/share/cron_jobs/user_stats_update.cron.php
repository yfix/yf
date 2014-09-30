<?php

return function() {
	_class('user_stats')->_refresh_all_stats();
	return 'User Stats Updated';
};
