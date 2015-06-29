<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class yf_console_langs_export extends Command {
	protected function configure() {
		$this
			->setName('langs:export')
			->setDescription('YF langs export')
			->addArgument('params',	InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Params for sub-command');
		;
	}
	protected function execute(InputInterface $input, OutputInterface $output) {
		init_yf();
		if (!defined('PROJECT_PATH') || !strlen(constant('PROJECT_PATH'))) {
			$output->writeln('Error: not inside a project');
			return false;
		}
		foreach ((array)main()->get_data('locale_langs') as $lang => $linfo) {
			echo '== '.$lang.' =='.PHP_EOL;
			list($tr_vars) = module('locale_editor')->_get_vars_from_files($lang);
			if (!$tr_vars) {
				continue;
			}
			$fname = './langs_exported_'.$lang.'.csv';
			$data = array();
			$data['__'] = '"key";"val"';
			foreach((array)$tr_vars as $k => $v) {
				$k = trim($k);
				$v = trim($v);
				if (!strlen($k)) {
					continue;
				}
				$data[$k] = '"'.str_replace('"', '\\\"', str_replace('_', ' ', $k)).'";"'.str_replace('"', '\\\"', str_replace('_', ' ', $v)).'"';
			}
			ksort($data);
			file_put_contents($fname, implode(PHP_EOL, $data));

			passthru('ls -l '.escapeshellarg($fname));
		}
	}
}
