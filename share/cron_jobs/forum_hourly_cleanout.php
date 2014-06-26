<?php

// THIS TASKS OPERATIONS:
// Clean out forum 'dead' sessions, validations etc
class task_item {

	var $class		= "";
	var $task		= "";

	
	// ADD YOUR CODE HERE
	function run_task()	{
		// Check for correct call
		if (!defined("INSIDE_TASK_MANAGER")) {
			return false;
		}
		db()->query("DELETE FROM ".db('forum_sessions')." WHERE last_update < ".(time() - module('forum')->SETTINGS["SESSION_EXPIRE_TIME"]));
		// Log to log table - modify but dont delete
		$this->class->append_task_log($this->task, 'Forum module: Old sessions, validations deleted');
	}

	
	// DO NOT MODIFY!
	function register_class(&$class) {
		$this->class = $class;
	}
	
	
	// DO NOT MODIFY!
	function pass_task($this_task) {
		$this->task = $this_task;
	}
}

?>