<?php

return function() {
	module('reputation')->_do_cron_job();
	return 'Reputation and activity synchronized';
};
