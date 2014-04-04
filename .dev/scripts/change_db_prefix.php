<?php

$old_prefix = "test_";
$new_prefix = "sp_";

require "./db_setup.php";

function db_connect() {
	if (isset($GLOBALS["_db_connection"])) {
		return $GLOBALS["_db_connection"];
	}

	$GLOBALS["_db_connection"] = mysql_connect(DB_HOST, DB_USER, DB_PSWD, true);

	if (!$GLOBALS["_db_connection"]) {
		echo mysql_errno() . ": " . mysql_error(). "\n";
		exit("Error connecting to main db");
	}
	mysql_select_db(DB_NAME, $GLOBALS["_db_connection"]) || exit("Can't select db");
	return $GLOBALS["_db_connection"];
}

function &db_query($sql = "", $db_connection = false) {
	if (!$db_connection) {
		db_connect();
		$db_connection = $GLOBALS["_db_connection"];
	}
	
	$result = mysql_query($sql, $db_connection) or mysql_error();
	
	if(!$result){
		$code = mysql_errno($db_connection);
		if($code == 2006){
			mysql_close($db_connection);
			echo "mysql reconnect main\n";
			sleep(10);
			
			if (isset($GLOBALS["_db_connection"])) {
				unset($GLOBALS["_db_connection"]);
			}
			db_main_connect();
			$db_connection = $GLOBALS["_db_connection"];
			
			$result = mysql_query($sql, $db_connection) or mysql_error();
		}
	}
	
	return $result;
}

function db_fetch($res = null) {
	if (!is_resource($res)) {
		echo mysql_error($GLOBALS["_db_connection"]);
		debug_backtrace();
		exit();
	}
	return mysql_fetch_assoc($res);
}

//---------------
$Q = db_query("SHOW TABLES");
while ($A = db_fetch($Q)) {
	$old_table = current($A);
	$new_table = "";
	if (substr($old_table, 0, strlen($old_prefix)) == $old_prefix) {
		$new_table = $new_prefix. substr($old_table, strlen($old_prefix));
	}
	if ($new_table) {
		$sql = "RENAME TABLE ".$old_table." TO ".$new_table;
		echo $sql."\n";
		db_query($sql);
	}
}