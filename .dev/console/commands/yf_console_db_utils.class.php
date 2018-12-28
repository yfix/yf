<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class yf_console_db_utils extends Command
{
    protected function configure()
    {
        $this
            ->setName('db:utils')
            ->setDescription('YF project database utils')
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

        $methods = [];
        $methods[] = 'conf';
        foreach (get_class_methods(db()->utils()) as $v) {
            if (substr($v, 0, 1) !== '_') {
                $methods[] = $v;
            }
        }
        $methods = array_combine($methods, $methods);

        if ($method == 'conf' || $method == 'get_conf') {
            $vars = [
                'DB_TYPE' => DB_TYPE,
                'DB_HOST' => DB_HOST,
                'DB_NAME' => DB_NAME,
                'DB_USER' => DB_USER,
                'DB_PSWD' => DB_PSWD,
                'DB_PREFIX' => DB_PREFIX,
                'DB_CHARSET' => DB_CHARSET,
            ];
            $output->writeln(_var_export($vars));
        } elseif ($method && isset($methods[$method])) {
            $func = $methods[$method];
            $text = db()->utils()->$func($params);
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
