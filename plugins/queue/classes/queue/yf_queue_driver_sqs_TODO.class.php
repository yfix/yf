<?php

load('queue_driver', '', 'classes/queue/');
class yf_queue_driver_sqs extends yf_queue_driver
{
    // TODO: AWS SQS
    public function _init()
    {
        require_php_lib('pheanstalk');
    }
}
