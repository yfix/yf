<?php

load('queue_driver', '', 'classes/queue/');
class yf_queue_driver_beanstalkd extends yf_queue_driver
{
    // TODO
    public function _init()
    {
        require_php_lib('pheanstalk');
    }
}
