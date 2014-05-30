<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class yf_console_demo_progress extends Command {
	protected function configure() {
		$this
			->setName('demo:progress')
			->setDescription('Demo progressbar')
		;
	}
	protected function execute(InputInterface $input, OutputInterface $output) {
		$progress = $this->getHelperSet()->get('progress');
		$progress->start($output, 50);
		$i = 0;
		while ($i++ < 50) {
		    // ... do some work

		    // advance the progress bar 1 unit
		    $progress->advance();
			sleep(1);
		}
		$progress->finish();
	}
}
