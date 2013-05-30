<?php
// Add your rules here
$GLOBALS["data_handlers"] = array_merge((array)$GLOBALS["data_handlers"], array(
	"org_types"	=> '
		$Q = $GLOBALS["db"]->query("SELECT `id`,`name` FROM `".dbt_org_types."` WHERE `active`=\'1\'");
		while ($A = @$GLOBALS["db"]->fetch_assoc($Q)) $data[$A["id"]] = $A["name"];
	',
	"music_styles"	=> '
		$Q = $GLOBALS["db"]->query("SELECT * FROM `".dbt_music_styles."` ORDER BY `id` ASC");
		while ($A = @$GLOBALS["db"]->fetch_assoc($Q)) $data[$A["id"]] = $A["name"];
	',
	"mail_folders"	=> '
		$Q = $GLOBALS["db"]->query("SELECT * FROM `".dbt_mail_folders."` WHERE `active`=\'1\' ORDER BY `order` ASC");
		while ($A = @$GLOBALS["db"]->fetch_assoc($Q)) $data[$A["id"]] = $A;
	',
	"font_size"	=> '
		$Q = $GLOBALS["db"]->query("SELECT * FROM `".dbt_font_size."`");
		while ($A = @$GLOBALS["db"]->fetch_assoc($Q)) $data[$A["id"]] = $A["value"];
	',
	"font_type"	=> '
		$Q = $GLOBALS["db"]->query("SELECT * FROM `".dbt_font_type."`");
		while ($A = @$GLOBALS["db"]->fetch_assoc($Q)) $data[$A["id"]] = $A["value"];
	',
	"account_types"	=> '
		$data = array(
			2 => "band", 
			3 => "fan",	
			4 => "org"
		);
	',
	"privacy_types" => '
		$data = array(
			0	=> "Public",
			1	=> "Diary",
			2	=> "Friends",
		);
	',
	"mode_types" => '
		$data = array(
			1	=> "Playing (Music)",
			2	=> "Reading (Books)",
			3	=> "Watching (DVD/Video)",
			4	=> "Playing (Video Games)",
		);
	',
));
?>