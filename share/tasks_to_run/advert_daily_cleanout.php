<?php
//-----------------------------------------------------------------------------
// THIS TASKS OPERATIONS:
// Clean out forum 'dead' sessions, validations etc
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
		// Init banner rotator
//		$this->ADVERT_OBJ = module("banner_rotator");
		// Update days campaigns
		db()->query("UPDATE ".db('adv_orders')." SET amount_left = amount_left - 1 WHERE type='days' AND status=1");
		// Turn off expired campaigns
		db()->query("UPDATE ".db('adv_orders')." SET status=2 WHERE amount_left <= 0 AND status=1");

		// Only for debugging :-))
//		trigger_error("TASK MANAGER: advert here", E_USER_ERROR);

		// Log to log table - modify but dont delete
		$this->class->append_task_log($this->task, 'Advert module: Turn off expired or old orders, update amount_left for "days" pay_type');
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