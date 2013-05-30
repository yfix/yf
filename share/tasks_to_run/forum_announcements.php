<?php
//-----------------------------------------------------------------------------
// THIS TASKS OPERATIONS:
// Deactivate expired forum announcements
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
		_class("forum_announce", FORUM_MODULES_DIR)->_retire_expired();
		// Log to log table - modify but dont delete
		$this->class->append_task_log($this->task, 'Forum module:Announcements updated');
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