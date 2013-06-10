<?php

class yf_xhprof {

	public $LOGS_PATH = "/var/log/xhprof/";

	/**
	*
	*/
	function __construct ($path_to_lib = "") {
		
		if(empty($path_to_lib)){
			$path_to_lib = YF_PATH."libs/xhprof/xhprof_lib/";
		}
		
		$this->path_to_lib = $path_to_lib;
	}
	
	
	/**
	*
	*/
	function start () {
		if(!function_exists("xhprof_enable")){
			return;
		}
		
		xhprof_enable();
	}
	
	/**
	*
	*/
	function stop () {
		if(!function_exists("xhprof_enable")){
			return;
		}
		
		$xhprof_data = xhprof_disable();
		include_once $this->path_to_lib."utils/xhprof_lib.php";
		include_once $this->path_to_lib."utils/xhprof_runs.php";
		
		$xhprof_runs = new XHProfRuns_Default();
		$run_id = $xhprof_runs->save_run($xhprof_data, "xhprof_test");
		
		return "<a href='http://localhost/xhprof_html?run=".$run_id."&source=xhprof_test' target='_blank'>Profiling</a>"; 
	}
	
	

}