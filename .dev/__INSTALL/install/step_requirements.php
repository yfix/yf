<?php

require dirname(__FILE__)."/header.php";

if(isset($_POST["next"])){
	header("Refresh: 0;URL=".$_SESSION['INSTALL']['_WEB_PATH']."install.php?step=".$next_step);
}

if(isset($_POST["prev"])){
	header("Refresh: 0;URL=".$_SESSION['INSTALL']['_WEB_PATH']."install.php?step=".$back_step);
}

if (!isset($_POST["prev"]) && !isset($_POST["next"])) {
	// Ugly hack...
	ob_start();
	ti('Yes');
	$t_yes = ob_get_contents();
	ob_end_clean();
	ob_start();
	ti('No');
	$t_no = ob_get_contents();
	ob_end_clean();

	$result = array(
		"yes"	=> '<b class="green">'.$t_yes.'</b>',
		"no"	=> '<b class="red">'.$t_no.'</b>',
	);

	$log_text .= "\n//********* Requirements test **********//\n";
// Test the minimum PHP version	
	if (version_compare(PHP_VERSION, '4.3') < 0){
			$php_version = $result["no"];
	}else{
			$php_version = $result["yes"];
	}
	$log_text .= "PHP version >= 4.3: ".strip_tags($php_version)."\n";

// Check for register_globals being enabled
	if (@ini_get('register_globals') == '1' || strtolower(@ini_get('register_globals')) == 'on'){
		$register_globals_result = $result["no"];
	}else{
		$register_globals_result = $result["yes"];
	}	
	$log_text .= "PHP setting Register Globals is disabled: ".strip_tags($register_globals_result)."\n";

// Check for getimagesize
	if (@function_exists('getimagesize')){
		$getimagesize_result = $result["yes"];
	}else{
		$getimagesize_result = $result["no"];
	}
	$log_text .= "PHP function getimagesize is available: ".strip_tags($getimagesize_result)."\n";

// Check for mysql_query
	if (@function_exists('mysql_query')){
		$mysql_query_result = $result["yes"];
	}else{
		$mysql_query_result = $result["no"];
	}
	$log_text .= "PHP function mysql query is available: ".strip_tags($mysql_query_result)."\n";

// Check for curl_init
	if (@function_exists('curl_init')){
		$curl_init_result = $result["yes"];
	}else{
		$curl_init_result = $result["no"];
	}
	$log_text .= "PHP function curl init is available:".strip_tags($curl_init_result)."\n";
	
// check writable by storing a simple file	
	$fp = @fopen('./test_lock', 'wb');
	if ($fp !== false){
		$writable_result = $result["yes"];
	}else{
		$writable_result = $result["no"];
	}
	$log_text .= "Install path writable: ".strip_tags($writable_result)."\n";
	@fclose($fp);
	@unlink('./test_lock');
	add_log($log_text);
	
	include $_SESSION['INSTALL']["install_path"]."template/header.stpl";
	include $_SESSION['INSTALL']["install_path"]."template/step_".$_GET["step"].".stpl";
	include $_SESSION['INSTALL']["install_path"]."template/footer.stpl";
}
?>
