<?php

/**
* Execution of planned tasks
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_task_loader {

	/** @var bool Use shutdown */
	public $USE_SHUTDOWN = false;

	/**
	* Do run sheduled tasks
	*/
	function show () {
		main()->NO_GRAPHICS = true;
		if (main()->USE_TASK_MANAGER) {
			// Set long time limit
			@set_time_limit(1200);
			// Init Task manager object
			$TASK_MGR_OBJ = &main()->init_class("task_manager", "classes/");
			// Check shutdown functions
			if ($this->USE_SHUTDOWN) {
				register_shutdown_function(array(&$TASK_MGR_OBJ, 'run_task'));
			} else {
				$TASK_MGR_OBJ->run_task();
			}
		}
		// Print out the 'blank' gif for the browser
		if ($TASK_MGR_OBJ->type != 'cron') {
			@header("Content-Type: image/gif");
			print base64_decode( "R0lGODlhAQABAID/AMDAwAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==" );
		}
	}
}
