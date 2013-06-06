<?php

/**
* Print view methods here
*
* @package		YF
* @author		Yuri Vysotskiy <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_forum_print {

	/**
	* Show Topic
	*/
// TODO: move this into template
	function _show_topic() {

		$_posts_per_page = !empty(module('forum')->USER_SETTINGS["POSTS_PER_PAGE"]) ? module('forum')->USER_SETTINGS["POSTS_PER_PAGE"] : module('forum')->SETTINGS["NUM_POSTS_ON_PAGE"];

		if (!module('forum')->SETTINGS["ALLOW_PRINT_TOPIC"]) {
			return module('forum')->_show_error("Print topic is disabled");
		}

		main()->NO_GRAPHICS = true;

		$topic_id = intval($_GET["id"]);

        // Get topic info
		$topic_info = db()->query_fetch("SELECT * FROM `".db('forum_topics')."` WHERE `id`=".intval($topic_id)." LIMIT 1");
		if (empty($topic_info)) {
			return "";
		}
        ?>
<html>
<head>
<title><?php echo $topic_info["name"]?></title>
<style type="text/css">
<!--
td, p, div
{
	font: 10pt verdana;
}
.smallfont
{
	font-size: 11px;
}
.tborder
{
	border: 1px solid #808080;
}
.thead
{
	background-color: #EEEEEE;
}
.page
{
	background-color: #FFFFFF;
	color: #000000;
}
-->
</style>
</head>
<body class="page">
        <?php

        echo "<a href='".process_url("./?object=forum&action=view_topic&id=".$topic_id)."'><b>".$topic_info["name"]."</b></a><br/>\r\n";
		// Prepare SQL query
		$sql = "SELECT * FROM `".db('forum_posts')."` WHERE `topic`=".$topic_id;
		$order_by = " ORDER BY `created` ASC ";
		list($add_sql, $pages, $topic_num_posts) = common()->divide_pages($sql, null, null, $_posts_per_page);

		if (!empty($pages))
		  {
			echo "<br /><small>Pages: ".$pages."</small>\r\n";
		  }


        echo "<BR>";
		// Init bb codes module
		$BB_OBJ = main()->init_class("bb_codes", "classes/");
		// Process posts
		$Q = db()->query($sql. $order_by. $add_sql);
		while ($post_info = db()->fetch_assoc($Q))
		  {
           ?>
<table class="tborder" cellpadding="6" cellspacing="1" border="0" width="100%">
  <tr>
	<td class="page">
		<table cellpadding="0" cellspacing="0" border="0" width="100%">
		  <tr valign="bottom">
			<td style="font-size:14pt"><?php echo _prepare_html($post_info["user_name"])?></td>
			<td class="smallfont" align="right"><?php echo _format_date($post_info["created"], "long")?></td>
		  </tr>
		</table>
		<hr/>
		<div><?php echo $BB_OBJ->_process_text($post_info["text"])?></div>
	</td>
  </tr>
</table>
<br/>
           <?php
		  }
        echo "</body></html>";
	}
}
