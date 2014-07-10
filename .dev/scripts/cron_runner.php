<?php
$PROJECT_PATH = dirname(dirname(dirname(__DIR__)));
require_once $PROJECT_PATH.'/scripts/scripts_init.php';

$_CRON_PATH = array(
	$PROJECT_PATH.'/yf/share/cron_jobs/*.cron.php',
);

if (!empty($argv[1])) {
    $time_start = microtime(true);
    db()->insert('cron_logs', array(
		'name' 			=> basename($argv[1]),
        'time_start'    => $time_start, 
    ));
	$id = db()->insert_id();

	if($argv[2] == "include_php"){
	    //run cron script body
		$func = include($argv[1]);
		$log = $func();
	}
	if($argv[2] == "custom_php_script"){
		exec("php ".$argv[1], $log);
		$log = implode("\n", $log);
	}
	if($argv[2] == "sh"){
		exec($argv[1], $log);
		$log = implode("\n", $log);
	}
	$time_end = microtime(true);
	$time_diff = $time_end - $time_start;
    db()->update_safe('cron_logs', array(
		'log'		=> $log,
        'time_end'  => $time_end, 
        'time_spent'=> $time_diff, 
    ), 'id ='.intval($id));
    exit();

}else{

	foreach($_CRON_PATH as $dir){
		foreach(glob($dir) as $file){
			$name = basename($file);
			$cron_info = db()->get("SELECT * FROM ".db('cron_tasks')." WHERE name ='".$name."'");
			$last_start_time = db()->get_one("SELECT time_start FROM ".db('cron_logs'). " WHERE name ='".$name."' ORDER BY time_start DESC");
			if(!empty($cron_info['frequency'])){
				$time_str = explode(' ', $cron_info['frequency']);
				$minutes= ($time_str[0] !== '*')? $time_str[0].' minutes ' 	: ' ';
				$hours 	= ($time_str[1] !== '*')? $time_str[1].' hours ' 	: ' ';
				$days 	= ($time_str[2] !== '*')? $time_str[2].' days ' 	: ' ';
				$time_wait = $minutes.$hours.$days;
			}
			exec('pgrep -a php | grep '.$name, $is_already_run);
			if(empty($last_start_time) || (time() > strtotime("+".$time_wait, $last_start_time))){
				$timer = true;
			}else{
				$timer = false;
			}	
			if(preg_match("/cron_jobs/", $file)){
				$type = "include_php";
			}elseif(preg_match("/\.sh/", $file)){
				$type = "sh";
			}elseif(preg_match("/\.php/", $file)){
				$type = "custom_php_script";
			}else{
				$type = '';
			}
			if (file_exists($file) && $cron_info['active'] && $timer && empty($is_already_run)) {
   			 	$cmd = 'php '.__FILE__.' '.$file.' '.$type.' &';
			    echo $cmd.PHP_EOL;
    			exec($cmd);
			}
		}
	}


}


