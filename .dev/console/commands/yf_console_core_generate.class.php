<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class yf_console_core_generate extends Command {
	protected function configure() {
		$this
			->setName('core:generate')
			->setDescription('YF project generation toolkit')
			->addArgument('method', InputArgument::OPTIONAL, 'API method to call')
			->addArgument('params',	InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Params for sub-command');
		;
	}
	protected function execute(InputInterface $input, OutputInterface $output) {
		global $yf_paths;
		require_once $yf_paths['db_setup_path'];
		init_yf();

		$params = [];
		// Parse arguments like that: k1=v1 k2=v2 into array('k1' => 'v1', 'k2' => 'v2')
		foreach ((array)$input->getArgument('params') as $p) {
			list($k, $v) = explode('=', trim($p));
			$k = trim($k);
			$v = trim($v);
			if (strlen($k) && strlen($v)) {
				$params[$k] = $v;
			}
		}
// TODO
	}
}
