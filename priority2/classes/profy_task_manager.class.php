<?php

/**
* Cron-style planned tasks manager
* 
* @package		Profy Framework
* @author		Yuri Vysotskiy <profy.net@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class profy_task_manager {

	/** @var string @conf_skip */
	var $type				= 'internal';
	/** @var int @conf_skip */
	var $time_now			= 0;
	/** @var array @conf_skip */
	var $date_now			= array();
	/** @var string @conf_skip */
	var $cron_key			= "";
	/** @var string */
	var $NEXT_RUN_CACHE_KEY = "task_next_run";
	/** @var bool */
	var $USE_LOCKING		= 1;
	/** @var int */
	var $LOCK_TIMEOUT		= 600;
	/** @var string */
	var $LOCK_FILE_NAME		= "uploads/task_manager.lock";

	/**
	* Constructor
	*/
	function _init () {
		// Path to the tasks files
		define("PROJECT_TASKS_FILES_PATH",	INCLUDE_PATH."classes/tasks/");
		define("FRAMEWORK_TASKS_FILES_PATH",PF_PATH."share/tasks_to_run/");
		// Define that we are inside task_manager
		define("INSIDE_TASK_MANAGER", true);
		// Prepare lock file
		if ($this->USE_LOCKING) {
			$this->LOCK_FILE_NAME = INCLUDE_PATH. $this->LOCK_FILE_NAME;
		}
		// Set current time stamp
		$this->time_now = time();
		// Switch between modes ("cron" or "internal")
		if (!empty($_GET['ck'])) {
			$this->type		= 'cron';
			$this->cron_key	= substr(trim($_GET['ck']), 0, 32);
		}
		$this->date_now['minute']	= intval(gmdate('i', $this->time_now));
		$this->date_now['hour']		= intval(gmdate('H', $this->time_now));
		$this->date_now['wday']		= intval(gmdate('w', $this->time_now));
		$this->date_now['mday']		= intval(gmdate('d', $this->time_now));
		$this->date_now['month']	= intval(gmdate('m', $this->time_now));
		$this->date_now['year']		= intval(gmdate('Y', $this->time_now));
	}

	/**
	* Run the task
	*/
	function run_task()	{
		if ($this->USE_LOCKING) {
			clearstatcache();
			if (file_exists($this->LOCK_FILE_NAME)) {
				// Timed out lock file
				if ((time() - filemtime($this->LOCK_FILE_NAME)) > $this->LOCK_TIMEOUT) {
					unlink($this->LOCK_FILE_NAME);
				} else {
					return false;
				}
			}
			// Put lock file
			file_put_contents($this->LOCK_FILE_NAME, time());
		}
		if ($this->type == 'internal') {
			$sql = "SELECT * FROM `".db('task_manager')."` WHERE `enabled` = 1 AND `next_run` <= ".intval($this->time_now)." ORDER BY `next_run` ASC LIMIT 1";
		} else {
			$sql = "SELECT * FROM `".db('task_manager')."` WHERE `cronkey`='"._es($this->cron_key)."' LIMIT 1";
		}
		$this_task = db()->query_fetch($sql);
		// Process task
		if (!empty($this_task['id'])) {
			// Got it, now update row and run..
			$new_date = $this->generate_next_run($this_task);
			// Update next task run
			db()->query("UPDATE `".db('task_manager')."` SET `next_run`=".intval($new_date)." WHERE `id`=".intval($this_task['id']));
			$this->save_next_run_stamp();
			$this->_task_start_time[$this_task['id']]	= microtime(true);
			// Use "php_code" field as source to eval as the job
			$this_task["php_code"] = trim($this_task["php_code"]);
			if (!empty($this_task["php_code"])) {
				// Try to execute code from db
				@eval($this_task["php_code"]);
				// Save log
				$this->append_task_log($this_task, "", true);

			// Use file from disk to do the job (default method)
			} else {

				// Try to load and execute task file
				$task_file_path = PROJECT_TASKS_FILES_PATH. $this_task['file'];
				if (!file_exists($task_file_path)) {
					$task_file_path = FRAMEWORK_TASKS_FILES_PATH. $this_task['file'];
					if (!file_exists($task_file_path)) {
						return false;
					}
				}
			    
				require_once ($task_file_path);
			    
				$myobj = new task_item();
				$myobj->register_class($this);
				$myobj->pass_task($this_task);
				$myobj->run_task();
			}
		}
		// Release lock
		if ($this->USE_LOCKING) {
			unlink($this->LOCK_FILE_NAME);
		}
	}

	/**
	* Update next run variable in the systemvars cache
	*/
	function save_next_run_stamp() {
		$sql = "SELECT `next_run` FROM `".db('task_manager')."` WHERE `enabled` = 1 ORDER BY `next_run` ASC LIMIT 1";
		$this_task = db()->query_fetch($sql);
		if (!$this_task['next_run']) {
			// Fail safe...
			$this_task['next_run'] = $this->time_now + 3600;
		}
		// Update cache
		$cache_array = array();
		$cache = db()->query_fetch("SELECT * FROM `".db('cache')."` WHERE `key`='".$this->NEXT_RUN_CACHE_KEY."'");
		$cache_array = unserialize(stripslashes($cache['value']));
		$cache_array['task_next_run'] = $this_task['next_run'];
		db()->query("REPLACE INTO `".db('cache')."` (`key`,`value`) VALUES ('"._es($this->NEXT_RUN_CACHE_KEY)."','"._es(serialize($cache_array))."')");
		// Refresh system cache
		if (main()->USE_SYSTEM_CACHE)	cache()->refresh("db_cache");
	}

	/**
	* Generate next_run unix timestamp
	*/
	function generate_next_run($task = array()) {
		// Did we set a day?
		$day_set       = 1;
		$min_set       = 1;
		$day_increment = 0;

		$this->run_day    = $this->date_now['wday'];
		$this->run_minute = $this->date_now['minute'];
		$this->run_hour   = $this->date_now['hour'];
		$this->run_month  = $this->date_now['month'];
		$this->run_year   = $this->date_now['year'];
		
		if ($task['week_day'] == -1 and $task['month_day'] == -1)	{
			$day_set = 0;
		}
		if ($task['minute'] == -1)	{
			$min_set = 0;
		}
		if ($task['week_day'] == -1) {
			if ($task['month_day'] != -1) {
				$this->run_day = $task['month_day'];
				$day_increment = 'month';
			} else {
				$this->run_day = $this->date_now['mday'];
				$day_increment = 'anyday';
			}
		} else {
			// Calc. next week day from today
			$this->run_day = $this->date_now['mday'] + ($task['week_day'] - $this->date_now['wday']);
			$day_increment = 'week';
		}
		// If the date to run next is less
		// than today, best fetch the next time
		if ($this->run_day < $this->date_now['mday']) {
			switch ($day_increment) {
				case 'month':	$this->_add_month(); break;
				case 'week':	$this->_add_day(7);	break;
				default:		$this->_add_day(); break;
			}
		}
		// Sort out the hour...
		if ($task['hour'] == -1) {
			$this->run_hour = $this->date_now['hour'];
		} else {
			// If ! min and ! day then it's
			// every X hour
			if (!$day_set && !$min_set)	{
				$this->_add_hour($task['hour']);
			} else {
				$this->run_hour = $task['hour'];
			}
		}
		// Can we run the minute...
		if ($task['minute'] == -1) {
			$this->_add_minute();
		} else {
			if ($task['hour'] == -1 && !$day_set) {
				// Runs every X minute..
				$this->_add_minute($task['minute']);
			} else {
				// runs at hh:mm
				$this->run_minute = $task['minute'];
			}
		}
		
		if ($this->run_hour <= $this->date_now['hour'] and $this->run_day == $this->date_now['mday']) {
			if ($task['hour'] == -1) {
				// Every hour...
				if ($this->run_hour == $this->date_now['hour'] and $this->run_minute <= $this->date_now['min'])	{
 					$this->_add_hour();
 				}
 			} else {
 				// Every X hour, try again in x hours
 				if (!$day_set && !$min_set) {
 					$this->_add_hour($task['hour']);
 				// Specific hour, try tomorrow
 				} elseif (!$day_set) {
 					$this->_add_day();
 				} else {
 					// Oops, specific day...
 					switch ($day_increment) {
						case 'month':	$this->_add_month(); break;
						case 'week':	$this->_add_day(7);	break;
						default:		$this->_add_day();	break;
					}
 				}
 			}
		}
		// Return stamp...
		$next_run = gmmktime($this->run_hour, $this->run_minute, 0, $this->run_month, $this->run_day, $this->run_year);
		return $next_run;
	}

	/**
	* Append Task Log
	*/
	function append_task_log($task, $desc, $_internal = false) {
		if (!$_internal && empty($task['log'])) {
			return false;
		}
		$_task_exec_time	= (float)microtime(true) - (float)$this->_task_start_time[$task["id"]];
		// Do query
		db()->INSERT("task_logs", array(
			"log_title"	=> _es($task["title"]),
			"log_date"	=> time(),
			"log_ip"	=> _es(common()->get_ip()),
			"log_desc"	=> _es($desc),
			"log_time"	=> _es($_task_exec_time),
		));
	}

	/**
	* Add Month
	*/
	function _add_month() {
		if ($this->date_now['month'] == 12) {
			$this->run_month = 1;
			$this->run_year++;
		} else {
			$this->run_month++;
		}
	}

	/**
	* Add Day
	*/
	function _add_day($days = 1) {
		if ($this->date['mday'] >= (gmdate('t', $this->time_now) - $days)) {
			$this->run_day = ($this->date['mday'] + $days) - date('t', $this->time_now);
			$this->_add_month();
		} else {
			$this->run_day += $days;
		}
	}

	/**
	* Add Hour
	*/
	function _add_hour($hour = 1)	{
		if ($this->date_now['hour'] >= (24 - $hour)) {
			$this->run_hour = ($this->date_now['hour'] + $hour) - 24;
			$this->_add_day();
		} else {
			$this->run_hour += $hour;
		}
	}

	/**
	* Add Minute
	*/
	function _add_minute($mins = 1)	{
		if ($this->date_now['minute'] >= (60 - $mins)) {
			$this->run_minute = ($this->date_now['minute'] + $mins) - 60;
			$this->_add_hour();
		} else {
			$this->run_minute += $mins;
		}
	}
}
