<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class yf_console_assets_combine extends Command {
	protected function configure() {
		$this
			->setName('assets:cache_combine')
			->setDescription('YF assets do combine')
			->addArgument('params',	InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Params for sub-command');
		;
	}
	protected function execute(InputInterface $input, OutputInterface $output) {
		init_yf();
		if (!defined('PROJECT_PATH') || !strlen(constant('PROJECT_PATH'))) {
			$output->writeln('Error: not inside a project');
			return false;
		}
		_class('manage_assets', 'admin_modules/')->combine();
	}
}
