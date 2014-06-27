<?php

return function() {
	_class('aggregator')->_do_cron_job();
	return 'Aggregator updated';
};
