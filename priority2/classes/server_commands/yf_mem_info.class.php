<?php

class yf_mem_info{

	/**
	*
	*/
	function _init () {
		$this->PARENT_OBJ	= _class(SERVER_COMMANDS_CLASS_NAME);
	}

	/**
	*
	*/
	function get_mem_info($server_info){
	
		$SERVER_OS = $this->PARENT_OBJ->SSH_OBJ->_get_remote_os($server_info);
		
		if($SERVER_OS == "FREEBSD"){
			$meminfo = $this->_freebsd_get_mem_info($server_info);
		}else{
			$meminfo = $this->_linux_get_mem_info($server_info);
		}
		
		return $meminfo;
	}
	
	/**
	*
	*/
	function _freebsd_get_mem_info ($server_info) {
		$s = $this->PARENT_OBJ->_grab_key($server_info, 'hw.physmem');
		
		//$pagesize = $this->_grab_key($server_info, 'hw.pagesize');
		
		/*
		vmstat on fbsd 4.4 or greater outputs kbytes not hw.pagesize
		I should probably add some version checking here, but for now
		we only support fbsd 4.4
		*/
		$pagesize = 1024;
		
		$results['ram'] = array();

		$pstat = _ssh_exec($server_info, 'vmstat');
		$lines = split("\n", $pstat);
		
		for ($i = 0, $max = sizeof($lines); $i < $max; $i++) {
			$ar_buf = preg_split("/\s+/", $lines[$i], 19);
			if ($i == 2) {
			
				//if(PHP_OS == 'NetBSD') {
				//	$results['ram']['free'] = $ar_buf[5];
				//} else {
					$results['ram']['free'] = $ar_buf[5] * $pagesize / 1024;
				//}
			}
		}
		
		$results['ram']['total'] = $s / 1024;
		$results['ram']['buffers'] = 0;
		$results['ram']['used'] = $results['ram']['total'] - $results['ram']['free'];
		$results['ram']['cached'] = 0;

		$results['ram']['percent'] = round(($results['ram']['used'] * 100) / $results['ram']['total']);

		//if (PHP_OS == 'OpenBSD' || PHP_OS == 'NetBSD') {
		//	$pstat = execute_program('swapctl', '-l -k');
		//} else {
			$pstat = _ssh_exec($server_info, 'swapinfo -k');
		//} 

		$lines = split("\n", $pstat);

		$results['swap']['total'] = 0;
		$results['swap']['used'] = 0;
		$results['swap']['free'] = 0;

		for ($i = 1, $max = sizeof($lines); $i < $max; $i++) {
		  $ar_buf = preg_split("/\s+/", $lines[$i], 6);

		  if ($ar_buf[0] != 'Total') {
			$results['swap']['total'] = $results['swap']['total'] + $ar_buf[1];
			$results['swap']['used'] = $results['swap']['used'] + $ar_buf[2];
			$results['swap']['free'] = $results['swap']['free'] + $ar_buf[3];

			$results['devswap'][$i - 1] = array();
			$results['devswap'][$i - 1]['dev'] = $ar_buf[0];
			$results['devswap'][$i - 1]['total'] = $ar_buf[1];
			$results['devswap'][$i - 1]['used'] = $ar_buf[2];
			$results['devswap'][$i - 1]['free'] = ($results['devswap'][$i - 1]['total'] - $results['devswap'][$i - 1]['used']);
			$results['devswap'][$i - 1]['percent'] = $ar_buf[2] > 0 ? round(($ar_buf[2] * 100) / $ar_buf[1]) : 0;
		  }
		} 
		$results['swap']['percent'] = round(($results['swap']['used'] * 100) / $results['swap']['total']);

	
		$pagesize = $this->PARENT_OBJ->_grab_key($server_info, "hw.pagesize");
		
		$results['ram']['cached'] = $this->PARENT_OBJ->_grab_key($server_info, "vm.stats.vm.v_cache_count") * $pagesize / 1024;
		$results['ram']['cached_percent'] = round( $results['ram']['cached'] * 100 / $results['ram']['total']);
		$results['ram']['app'] = $this->PARENT_OBJ->_grab_key($server_info, "vm.stats.vm.v_active_count") * $pagesize / 1024;
		$results['ram']['app_percent'] = round( $results['ram']['app'] * 100 / $results['ram']['total']);
		$results['ram']['buffers'] = $results['ram']['used'] - $results['ram']['app'] - $results['ram']['cached'];
		$results['ram']['buffers_percent'] = round( $results['ram']['buffers'] * 100 / $results['ram']['total']);

		return $results;
	}
	
	/**
	*
	*/
	function _linux_get_mem_info ($server_info) {
		$results['ram'] = array('total' => 0, 'free' => 0, 'used' => 0, 'percent' => 0);
		$results['swap'] = array('total' => 0, 'free' => 0, 'used' => 0, 'percent' => 0);
		$results['devswap'] = array();

		$bufr = _ssh_exec($server_info, "cat /proc/meminfo");
		
		if ( $bufr != "ERROR" ) {
		}else{
			return;
		}
	
	
		$bufe = explode("\n", $bufr);
		
		foreach( $bufe as $buf ) {
			preg_match('/^MemTotal:\s+(.*)\s*kB/i', $buf, $ar_buf)?$results['ram']['total'] = $ar_buf[1]:"";
			preg_match('/^MemFree:\s+(.*)\s*kB/i', $buf, $ar_buf)?$results['ram']['free'] = $ar_buf[1]:"";
			preg_match('/^Cached:\s+(.*)\s*kB/i', $buf, $ar_buf)?$results['ram']['cached'] = $ar_buf[1]:"";
			preg_match('/^Buffers:\s+(.*)\s*kB/i', $buf, $ar_buf)?$results['ram']['buffers'] = $ar_buf[1]:"";
		} 

		$results['ram']['used'] = $results['ram']['total'] - $results['ram']['free'];
		if(!empty($results['ram']['total'])){
			$results['ram']['percent'] = round(($results['ram']['used'] * 100) / $results['ram']['total']);
		}
		
	  
		// values for splitting memory usage
		if (isset($results['ram']['cached']) && isset($results['ram']['buffers'])) {
			$results['ram']['app'] = $results['ram']['used'] - $results['ram']['cached'] - $results['ram']['buffers'];
			
			if(!empty($results['ram']['total'])){
				$results['ram']['app_percent'] = round(($results['ram']['app'] * 100) / $results['ram']['total']);
				$results['ram']['buffers_percent'] = round(($results['ram']['buffers'] * 100) / $results['ram']['total']);
				$results['ram']['cached_percent'] = round(($results['ram']['cached'] * 100) / $results['ram']['total']);
			}
		}

		$bufr = _ssh_exec($server_info, "cat /proc/swaps");
		
		if ( $bufr != "ERROR" ) {
			$swaps = explode("\n", $bufr);
			for ($i = 1; $i < (sizeof($swaps)); $i++) {
				if( trim( $swaps[$i] ) != "" ) {
					$ar_buf = preg_split('/\s+/', $swaps[$i], 6);
					$results['devswap'][$i - 1] = array();
					$results['devswap'][$i - 1]['dev'] = $ar_buf[0];
					$results['devswap'][$i - 1]['total'] = $ar_buf[2];
					$results['devswap'][$i - 1]['used'] = $ar_buf[3];
					$results['devswap'][$i - 1]['free'] = ($results['devswap'][$i - 1]['total'] - $results['devswap'][$i - 1]['used']);
					$results['devswap'][$i - 1]['percent'] = round(($ar_buf[3] * 100) / $ar_buf[2]);
					$results['swap']['total'] += $ar_buf[2];
					$results['swap']['used'] += $ar_buf[3];
					$results['swap']['free'] = $results['swap']['total'] - $results['swap']['used'];
					if(!empty($results['swap']['total'])){
						$results['swap']['percent'] = round(($results['swap']['used'] * 100) / $results['swap']['total']);
					}
				}
			} 
		}
	
		return $results;
	}
}
