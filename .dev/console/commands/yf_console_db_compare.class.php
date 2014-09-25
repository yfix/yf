<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class yf_console_db_compare extends Command {
	protected function configure() {
		$this
			->setName('db:compare')
			->setDescription('YF project database comparison toolkit')
			->addArgument('method', InputArgument::OPTIONAL, 'API method to call')
		;
	}
	protected function execute(InputInterface $input, OutputInterface $output) {
		global $yf_paths;
		require_once $yf_paths['db_setup_path'];
		init_yf();

		$report = db()->migrator()->compare();
#		echo _var_export($report);

		$output->writeln( _var_export($report) );
// TODO
	}
}
