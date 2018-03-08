<?php

load('queue_driver', '', 'classes/queue/');
class yf_queue_driver_beanstalkd extends yf_queue_driver {
// TODO
	function _init() {
		require_php_lib('pheanstalk');
	}
}
