<?php
//-----------------------------------------------------------------------------
// THIS TASKS OPERATIONS:
// Cleanup Search Engine keywords
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
		db()->query("DELETE FROM `".db('search_keywords')."` WHERE `hits` <= 2");
		db()->query("OPTIMIZE TABLE `".db('search_keywords')."`");
		// Log to log table - modify but dont delete
		$this->class->append_task_log($this->task, 'SE keywords cleaned up');
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