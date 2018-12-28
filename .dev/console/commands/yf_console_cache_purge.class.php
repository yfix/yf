<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class yf_console_cache_purge extends Command
{
    protected function configure()
    {
        $this
            ->setName('cache:purge')
            ->setDescription('YF project purge cache');
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        init_yf();
        cache()->flush();
        $text = 'Cache flushed successfully';
        $output->writeln($text);
    }
}
