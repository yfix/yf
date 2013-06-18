<?php
define("DEBUG_MODE", true);
define("INCLUDE_PATH", "../../");
define("YF_PATH", INCLUDE_PATH."yf/");
require (INCLUDE_PATH."common_vars.php");
require YF_PATH."classes/yf_main.class.php";
$GLOBALS['main'] = new yf_main("user");
$SQL_UPDATES_DIR	= INCLUDE_PATH."_UPDATES/";
$GLUED_SQL_FILE		= "./glued_updates.sql";
$START_DATE = "2005-11-28";
if ($dh = @opendir($SQL_UPDATES_DIR)) {
	$fh = fopen($GLUED_SQL_FILE, "w");
	// Get all sql files inside current dir
	while (($f = @readdir($dh)) !== false) {
		if ($f == "." || $f == ".." || is_dir($f)) {
			continue;
		}
		$cur_ext = common()->get_file_ext($f);
		// Skip non-images
		if ($cur_ext != "sql") {
			continue;
		}
		$cur_sql_date = substr($f, 0, 10);
		// Skip less dates than specified
		if (!empty($START_DATE) && $cur_sql_date < $START_DATE) {
			continue;
		}
		// Get current SQL contents
		$cur_sql = "\r\n/******* START SQL UPDATE FOR ".$cur_sql_date." **/\r\n\r\n";
		$cur_sql .= file_get_contents($SQL_UPDATES_DIR.$f);
		$cur_sql .= "\r\n/******* END SQL UPDATE FOR ".$cur_sql_date." **/\r\n\r\n";
		fwrite($fh, $cur_sql, strlen($cur_sql));
		// Log to browser
		echo $f." glued<br />";
	}
	@fclose($fh);
	@closedir($dh);
}
echo $body;
if (DEBUG_MODE) {
	echo "<br>";
	echo common()->show_debug_info();
}
?>