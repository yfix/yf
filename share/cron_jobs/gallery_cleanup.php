<?php

// THIS TASKS OPERATIONS:
// Clean up gallery 'dead' db records, non-linked photos
class task_item {

	var $class		= "";
	var $task		= "";

	
	// ADD YOUR CODE HERE
	function run_task()	{
		// Check for correct call
		if (!defined("INSIDE_TASK_MANAGER")) {
			return false;
		}
		module("gallery")->_cleanup();
		// Log to log table - modify but dont delete
		$this->class->append_task_log($this->task, 'Gallery: cleanup finished');
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