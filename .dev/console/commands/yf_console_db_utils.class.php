<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class yf_console_db_utils extends Command {
	protected function configure() {
		$this
			->setName('db:utils')
			->setDescription('YF project database utils')
			->addArgument('method', InputArgument::OPTIONAL, 'API method to call')
		;
	}
	protected function execute(InputInterface $input, OutputInterface $output) {
		global $yf_paths;
		require_once $yf_paths['db_setup_path'];
		init_yf();

		$method = $input->getArgument('method');
		if ($method == 'conf' || $method == 'get_conf') {
			$vars = array(
				'DB_TYPE'	=> DB_TYPE,
				'DB_HOST'	=> DB_HOST,
				'DB_NAME'	=> DB_NAME,
				'DB_USER'	=> DB_USER,
				'DB_PSWD'	=> DB_PSWD,
				'DB_PREFIX'	=> DB_PREFIX,
				'DB_CHARSET'=> DB_CHARSET,
			);
			$output->writeln(print_r($vars, 1));
		}
/*
#		$methods = get_class_methods(_class('core_api'));
		$methods = array_combine($methods, $methods);
		foreach ($methods as $name) {
			if ($name[0] == '_') {
				unset($methods[$name]);
			}
		}
		if ($method && in_array($method, $methods)) {
#			$text = _class('db')->utils()->$method();
			$output->writeln($text);
		} else {
			$table = $this->getHelperSet()->get('table');
			$rows = array();
			foreach ($methods as $name) {
				$rows[] = array($name);
			}
			$table->setHeaders(array('API method'))
				->setRows($rows);
			$table->render($output);
		}
*/
	}
}
