<?php

load('queue_driver', 'framework', 'classes/queue/');
class yf_queue_sqs extends yf_queue_driver {
// TODO: AWS SQS
	function _init() {
		require_php_lib('pheanstalk');
	}
}
