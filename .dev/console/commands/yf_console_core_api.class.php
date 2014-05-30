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
		;
	}
	protected function execute(InputInterface $input, OutputInterface $output) {
		init_yf();
		$method = $input->getArgument('method');
		$methods = get_class_methods(_class('core_api'));
		$methods = array_combine($methods, $methods);
		foreach ($methods as $name) {
			if ($name[0] == '_') {
				unset($methods[$name]);
			}
		}
		if ($method && in_array($method, $methods)) {
			$text = _class('core_api')->$method();
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
