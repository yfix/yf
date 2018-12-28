<?php

$PROJECT_PATH = dirname(dirname(dirname(__DIR__))) . '/scripts/scripts_init.php';
require_once $PROJECT_PATH;

if ( ! empty($argv[1])) {
    $time_start = microtime(true);
    db()->insert('cron_logs', [
        'cron_id' => (int) ($argv[3]),
        'time_start' => $time_start,
    ]);
    $id = db()->insert_id();

    if ($argv[2] == 'include_php') {
        //run cron script body
        $cmd = "php -r 'require(\"" . $PROJECT_PATH . '"); $func=include("' . $argv[1] . "\"); echo \$func();'";
        exec($cmd, $log);
        $log = implode("\n", $log);
    }
    if ($argv[2] == 'custom_php_script') {
        exec('php ' . $argv[1], $log);
        $log = implode("\n", $log);
    }
    if ($argv[2] == 'sh') {
        exec($argv[1], $log);
        $log = implode("\n", $log);
    }
    $time_end = microtime(true);
    $time_diff = $time_end - $time_start;
    db()->update_safe('cron_logs', [
        'log' => $log,
        'time_end' => $time_end,
        'time_spent' => round($time_diff, 2),
    ], 'id =' . (int) $id);
    exit();
}
    $crons = db()->get_all('SELECT * FROM ' . db('cron_tasks') . " WHERE active='1'");
    foreach ($crons as $cron_info) {
        $file = $cron_info['dir'] . $cron_info['name'];
        $last_start_time = db()->get_one('SELECT time_start FROM ' . db('cron_logs') . ' WHERE cron_id = ' . $cron_info['id'] . ' ORDER BY time_start DESC');
        exec('pgrep -a php | grep ' . $file, $is_already_run);
        if (empty($last_start_time) || (time() > strtotime('+' . $cron_info['frequency'], $last_start_time))) {
            $timer = true;
        } else {
            $timer = false;
        }
        // kill if exectime is over
        if ($is_already_run && (time() > strtotime('+' . $cron_info['exec_time'] . ' seconds', $last_start_time))) {
            exec('pkill -f ' . $file);
        }
        if (file_exists($file) && $timer && empty($is_already_run)) {
            $cmd = 'php ' . __FILE__ . ' ' . $file . ' ' . $cron_info['exec_type'] . ' ' . $cron_info['id'] . ' &';
            echo $cmd . PHP_EOL;
            exec($cmd);
        }
    }
    sleep(15);
