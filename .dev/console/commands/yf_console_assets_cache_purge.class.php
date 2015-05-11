<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class yf_console_assets_cache_purge extends Command {
	protected function configure() {
		$this
			->setName('assets:cache_purge')
			->setDescription('YF assets purge cache')
			->addArgument('params',	InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Params for sub-command');
		;
	}
	protected function execute(InputInterface $input, OutputInterface $output) {
		init_yf();
		if (!defined('PROJECT_PATH') || !strlen(constant('PROJECT_PATH'))) {
			$output->writeln('Error: not inside a project');
			return false;
		}
		$cache_path_user	= PROJECT_PATH. 'templates/user/cache/';
		foreach ((array)_class('dir')->scan($cache_path_user) as $path) {
			$output->writeln($path);
		}
		_class('dir')->delete($cache_path_user, $and_start_dir = true);

		$cache_path_admin	= PROJECT_PATH. 'templates/admin/cache/';
		foreach ((array)_class('dir')->scan($cache_path_admin) as $path) {
			$output->writeln($path);
		}
		_class('dir')->delete($cache_path_admin, $and_start_dir = true);
	}
}
