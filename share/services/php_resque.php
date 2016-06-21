#!/usr/bin/php
<?php

$config = [
	'require_services' => ['credis', 'psr_log'],
	'git_urls' => ['https://github.com/chrisboulton/php-resque.git' => 'php_resque/'],
	'pear' => ['php_resque/lib/' => 'Resque'],
	'example' => function() {
		Resque::setBackend('localhost:6379');
		class My_Job {
			public function perform() {
				echo $this->args['name'];
			}
		}
		$statuses = [
			Resque_Job_Status::STATUS_WAITING	=> 'STATUS_WAITING', // Job is still queued
			Resque_Job_Status::STATUS_RUNNING	=> 'STATUS_RUNNING', // Job is currently running
			Resque_Job_Status::STATUS_FAILED	=> 'STATUS_FAILED', // Job has failed
			Resque_Job_Status::STATUS_COMPLETE	=> 'STATUS_COMPLETE', // Job is complete
		];
		$args = ['name' => 'Chris'];
		$token = Resque::enqueue('default', 'My_Job', $args, true);
		echo $token. PHP_EOL;
		$status = new Resque_Job_Status($token);
		echo $statuses[$status->get()]. PHP_EOL;
		Resque::dequeue('default', ['My_Job']);
		$status = new Resque_Job_Status($token);
		echo $statuses[$status->get()]. PHP_EOL;
	}
];
if ($return_config) { return $config; } require_once __DIR__.'/_yf_autoloader.php'; new yf_autoloader($config);
