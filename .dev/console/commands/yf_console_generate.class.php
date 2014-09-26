<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class yf_console_generate extends Command {
	protected function configure() {
		$this
			->setName('db:utils')
			->setDescription('YF project generation toolkit')
			->addArgument('method', InputArgument::OPTIONAL, 'API method to call')
		;
	}
	protected function execute(InputInterface $input, OutputInterface $output) {
		global $yf_paths;
		require_once $yf_paths['db_setup_path'];
		init_yf();
// TODO
	}
}
