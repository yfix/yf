<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class yf_console_db_migrate extends Command
{
    protected function configure()
    {
        $this
            ->setName('db:migrate')
            ->setDescription('YF project database migration tools')
            ->addArgument('method', InputArgument::OPTIONAL, 'API method to call')
            ->addArgument('params', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Params for sub-command');
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        global $yf_paths;
        require_once $yf_paths['db_setup_path'];
        init_yf();

        $params = [];
        // Parse arguments like that: k1=v1 k2=v2 into array('k1' => 'v1', 'k2' => 'v2')
        foreach ((array) $input->getArgument('params') as $p) {
            list($k, $v) = explode('=', trim($p));
            $k = trim($k);
            $v = trim($v);
            if (strlen($k) && strlen($v)) {
                $params[$k] = $v;
            }
        }

        $method = $input->getArgument('method');

        $methods = [
            'compare' => 'compare',
            'generate' => 'generate',
            'create' => 'create',
            'apply' => 'apply',
            'list' => '_list',
            'dump' => 'dump',
            'sync' => 'sync',
        ];
        if ($method && isset($methods[$method])) {
            $func = $methods[$method];
            $text = db()->migrator()->$func($params);
            if (is_array($text)) {
                $text = _var_export($text);
            }
            $output->writeln($text);
        } else {
            $table = $this->getHelperSet()->get('table');
            $rows = [];
            foreach ($methods as $name => $real_name) {
                $rows[] = [$name];
            }
            $table->setHeaders(['Sub-commands'])
                ->setRows($rows);
            $table->render($output);
        }
    }
}
