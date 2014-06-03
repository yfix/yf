<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class yf_console_cache_control extends Command {
	protected function configure() {
		$this
			->setName('cache:purge')
			->setDescription('YF project purge cache')
#			->addArgument('method', InputArgument::OPTIONAL, 'API method to call')
		;
	}
	protected function execute(InputInterface $input, OutputInterface $output) {
		init_yf();
// TODO
		$output->writeln($text);
	}
}
