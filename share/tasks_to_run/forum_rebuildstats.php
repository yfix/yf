<?php
//-----------------------------------------------------------------------------
// THIS TASKS OPERATIONS:
// Rebuilds topics, posts, forum, members, last reg. member counts
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
		_class("forum_sync", FORUM_MODULES_DIR)->_sync_board();
		// Log to log table - modify but dont delete
		$this->class->append_task_log($this->task, 'Forum module: Statistics rebuilt');
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