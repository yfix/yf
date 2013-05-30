<?php
//--------------------------------------------------------------------------
define(INCLUDE_PATH, "./");
require "./db_setup.php";
require "./classes/db.class.php";
$GLOBALS['db'] = &new db;
//--------------------------------------------------------------------------
//$test_query = "SELECT * FROM `".dbt_translate."`";
//$test_query = "SELECT * FROM `".dbt_forum_forums."` WHERE `status`='a' AND `language`=2 ORDER BY `category` ASC, `order` ASC";
//$test_query = "SELECT t1.id, t1.subject, t1.text, t2.name, MATCH (t1.text) AGAINST ('Запрос') AS relevance FROM `".dbt_forum_posts."` AS t1 LEFT JOIN `".dbt_forum_forums."` AS t2 ON t1.forum = t2.id WHERE MATCH (t1.text) AGAINST ('Запрос') AND t1.created > ".(time() - 3600*24*300/* 300 days before*/);
$user_text = "Как динамически создать документ";
$test_query = "SELECT t1.*, MATCH (`text`) AGAINST ('\"".$user_text."\"' IN BOOLEAN MODE) AS `relevance` FROM `".dbt_forum_posts."` AS t1 WHERE MATCH (`text`) AGAINST ('\"".$user_text."\"' IN BOOLEAN MODE) AND `created` > ".(time() - 3600*24*300/* 300 days before*/);
//--------------------------------------------------------------------------
?>
<form action="<?=$_SERVER['PHP_SELF']?>" method="post">
	<textarea name="query_string" cols="100" rows="15"><?=$_POST['query_string'] ? stripslashes($_POST['query_string']) : $test_query?></textarea><br>
	<input type="submit" value="EXPLAIN!">
</form><br>
<?php
//--------------------------------------------------------------------------
if (strlen($_POST['query_string'])) {
	$_POST['query_string'] = stripslashes($_POST['query_string']);
	if (substr(trim(strtoupper($_POST['query_string'])),0,6) == "SELECT") {
		$body .= "</pre>\r\n";
		$body .= "
		<table width=100% border=1 cellpadding=2 cellspacing=1>
		<tr>
			<td><b>table</b></td>
			<td><b>type</b></td>
			<td><b>possible_keys</b></td>
			<td><b>key</b></td>
			<td><b>key_len</b></td>
			<td><b>ref</b></td>
			<td><b>rows</b></td>
			<td><b>Extra</b></td>
		</tr>\r\n";
		$Q = $GLOBALS['db']->query("EXPLAIN ".$_POST['query_string']);
		while ($A = @$GLOBALS['db']->fetch_array($Q)) {
			$body .= "
			<tr>
				<td>".$A["table"]."&nbsp;</td>
				<td>".$A["type"]."&nbsp;</td>
				<td>".$A["possible_keys"]."&nbsp;</td>
				<td>".$A["key"]."&nbsp;</td>
				<td>".$A["key_len"]."&nbsp;</td>
				<td>".$A["ref"]."&nbsp;</td>
				<td>".$A["rows"]."&nbsp;</td>
				<td>".$A["Extra"]."&nbsp;</td>
			</tr>\r\n";
		}
		$body .= "</table>\r\n<BR><hr>\r\n";
		$body .= "\r\n<pre>";
	} else $body = "NOT A \"SELECT\" QUERY!";
	echo $body;
}
//--------------------------------------------------------------------------
?>