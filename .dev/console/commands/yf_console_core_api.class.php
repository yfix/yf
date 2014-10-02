<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class yf_console_core_api extends Command {
	protected function configure() {
		$this
			->setName('core:api')
			->setDescription('YF core api connector')
			->addArgument('method', InputArgument::OPTIONAL, 'API method to call')
			->addArgument('params',	InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Params for sub-command');
		;
	}
	protected function execute(InputInterface $input, OutputInterface $output) {
		init_yf();

		$params = array();
		// Parse arguments like that: k1=v1 k2=v2 into array('k1' => 'v1', 'k2' => 'v2')
		foreach ((array)$input->getArgument('params') as $p) {
			list($k, $v) = explode('=', trim($p));
			$k = trim($k);
			$v = trim($v);
			if (strlen($k) && strlen($v)) {
				$params[$k] = $v;
			}
		}

		$method = $input->getArgument('method');

		$methods = get_class_methods(_class('core_api'));
		$methods = array_combine($methods, $methods);
		foreach ($methods as $name) {
			if ($name[0] == '_') {
				unset($methods[$name]);
			}
		}
		if ($method && in_array($method, $methods)) {
			$text = _class('core_api')->$method($params);
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
	}
}
