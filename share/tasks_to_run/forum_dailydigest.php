<?php
//-----------------------------------------------------------------------------
// THIS TASKS OPERATIONS:
// Send daily digest emails
class task_item {

	var $class		= "";
	var $task		= "";

	//-----------------------------------------------------------------------------
	// ADD YOUR CODE HERE
	function run_task()	{
		// Check for correct call
		if (!defined("INSIDE_TASK_MANAGER")) {
			return false;
		}
		$this->FORUM_OBJ = module("forum");
		_class("forum_tracker", FORUM_MODULES_DIR)->_send_digest("daily", "topic");
		_class("forum_tracker", FORUM_MODULES_DIR)->_send_digest("daily", "forum");
		// Log to log table - modify but dont delete
		$this->class->append_task_log($this->task, 'Forum module: Daily Topic & Forum Digest Sent');
	}

	//-----------------------------------------------------------------------------
	// DO NOT MODIFY!
	function register_class(&$class) {
		$this->class = $class;
	}
	
	//-----------------------------------------------------------------------------
	// DO NOT MODIFY!
	function pass_task($this_task) {
		$this->task = $this_task;
	}
}
//-----------------------------------------------------------------------------
?>