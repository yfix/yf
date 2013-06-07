<?php

// Fast process debug info
function _fast_debug_info () {
	$body .= "<hr>DEBUG INFO:\r\n";
	$body .= "<br />exec time: <b>". round(microtime(true) - main()->_time_start, 5)."</b> sec";
/*
	// Included files
	$body .= "<div align=\"left\" style=\"margin-left:10px;\"><b>".("included_files")."</b><br /><br />\r\n";
	$total_size = 0;
	$counter	= 1;
	$included_files = get_included_files();
	$body .= "<table border=\"0\" cellspacing=\"0\" cellpadding=\"0\">\r\n";
	$body .= "<tr><td>&nbsp;</td><td><i>".("name")."</i></td><td><i>".("size")."</i></td><td><i>".("time")."</i></td></tr>\r\n";
	foreach ((array)$included_files as $file_name) {
		$cur_size = file_exists($file_name) ? filesize($file_name) : "";
		$total_size += $cur_size;
		$cf = strtolower(str_replace(DIRECTORY_SEPARATOR, "/", $file_name));
		$cur_include_time = debug('include_files_exec_time::'.$cf);
		$total_include_time += (float)$cur_include_time;
		$body .= "<tr><td align='right'>".$counter++
			.". &nbsp;</td><td nowrap>".$file_name."</td><td>&nbsp; <b>".$cur_size
			."&nbsp;</b></td><td>&nbsp; <b>".round($cur_include_time, 5)
			."</b></td></tr>\r\n";
	}
	$body .= "</table><br />\r\n<i>".("total_included_size")
		."</i>: <b>".$total_size."&nbsp;</b> bytes, <i>".("total_include_time")
		."</i>: <b>".round($total_include_time, 5)
		."</b> sec<br /><br /></div>\r\n";
*/	
	echo $body;
}
