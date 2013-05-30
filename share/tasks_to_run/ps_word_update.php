<?php
//-----------------------------------------------------------------------------
// THIS TASKS OPERATIONS:
// Update number of searches in db('searches') table
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
		_class("ps_word", USER_MODULES_DIR)->_refresh_stats();
		// Log to log table - modify but dont delete
		$this->class->append_task_log($this->task, 'Popular Searches: Updated number of search results for all keywords');
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