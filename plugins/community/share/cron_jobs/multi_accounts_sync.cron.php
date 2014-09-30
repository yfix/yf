<?php

return function() {
	_class('check_multi_accounts', 'admin_modules/')->_do_cron_job();
	return 'Multi-accounts stats updated';
};
