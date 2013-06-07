<?
require_once "Auth/OpenID/Consumer.php";
require_once "Auth/OpenID/FileStore.php";
require_once "Auth/OpenID/SReg.php";
//session_start();
//require_once "openid_functions.php";

$dbhost = 'localhost';
$dbuser = 'user';
$dbpass = 'pass';
$dbname = 'name';

//mysql_connect($dbhost, $dbuser, $dbpass) or die("Couldn't connect to ".$dbhost);
//mysql_select_db($dbname) or die("Couldn't connect to ".$dbname." on ".$dbhost);



$store_path = "tmp/";

if (!file_exists($store_path) &&
	!mkdir($store_path)) {
	 $openid_error = "Не удалось открыть директорию для временного хранения файлов.";
} else
{
	$store = new Auth_OpenID_FileStore($store_path); 
	
	$consumer = new Auth_OpenID_Consumer($store);
	  
	$trust_root = 'http://'.$_SERVER['HTTP_HOST'].str_replace("\\","",dirname($_SERVER['PHP_SELF'])); 
	$return_to = 'http://'.$_SERVER['HTTP_HOST'].str_replace("\\","",dirname($_SERVER['PHP_SELF'])).'?object=openid&action=complete';
}

?>