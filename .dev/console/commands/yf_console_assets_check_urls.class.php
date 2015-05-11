<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class yf_console_assets_check_urls extends Command {
	protected function configure() {
		$this
			->setName('assets:check_urls')
			->setDescription('YF assets urls check if all alive')
			->addArgument('params',	InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Params for sub-command');
		;
	}
	protected function execute(InputInterface $input, OutputInterface $output) {
		init_yf();

		$DIR_TO_CHECK = APP_PATH;
		require YF_PATH. '.dev/scripts/assets/assets_urls_check.php';

		$output->writeln($text);
	}
}
