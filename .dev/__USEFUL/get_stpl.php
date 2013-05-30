<? 
$default_file = !strlen($_POST["file_name"]) ? "./templates/silver/projects/add_main.stpl" : $_POST["file_name"];
?>
<form action="<?=$_SERVER["PHP_SELF"]?>" method="post">
	<input type="text" name="file_name" value="<?=$default_file?>" size="50">
	<input type="submit" value="PARSE!">
</form><br>
<?
//--------------------------------------------------------------------------
if (count($_POST)) {
	$file_name = $_POST["file_name"];
	$string = @file_get_contents($file_name);
	$pattern = "/\{([^\{\"]+)\}/ims";
	preg_match_all($pattern, $string, $matches);
	if (is_array($matches[1])) {
		sort($matches[1]);
		$new_array = array_unique($matches[1]);
		$body .= "\t\$replace = array(\r\n";
		foreach ((array)$new_array as $name) {
			$body .= "\t\t[\"".$name."\"] =>\t\"\",\r\n";
		}
		$body .= "\t);";
	} else $body = "<span style='color:red;'>NO VALID STPL TAGS FOUND!</span>";
	echo "<pre>".$body."</pre>";
}
//--------------------------------------------------------------------------
?>