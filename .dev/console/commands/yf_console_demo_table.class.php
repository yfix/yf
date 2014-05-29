<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class yf_console_demo_table extends Command {
	protected function configure() {
		$this
			->setName('demo:table')
			->setDescription('Demo table helper')
		;
	}
	protected function execute(InputInterface $input, OutputInterface $output) {
		$table = $this->getHelperSet()->get('table');
		$table
		    ->setHeaders(array('ISBN', 'Title', 'Author'))
		    ->setRows(array(
		        array('99921-58-10-7', 'Divine Comedy', time()),
		        array('9971-5-0210-0', 'A Tale of Two Cities', 'Charles Dickens'),
		        array('960-425-059-0', 'The Lord of the Rings', 'J. R. R. Tolkien'),
		        array('80-902734-1-6', 'And Then There Were None', 'Agatha Christie'),
		    ))
		;
		$table->render($output);
	}
}
