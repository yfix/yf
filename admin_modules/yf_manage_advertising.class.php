<?php

/*
		"advertising" => "
			`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	  		`ad` varchar(64) NOT NULL,
 	  		`langs` varchar(255) NOT NULL DEFAULT '',
	  		`cat_ids` text NOT NULL,
	  		`program_ids` text NOT NULL,
			`customer` text NOT NULL,
	  		`module_names` varchar(255) NOT NULL DEFAULT '',
	  		`user_countries` text NOT NULL,
	  		`is_logged_in` int(11) NOT NULL DEFAULT '0',
	  		`date_start` int(11) NOT NULL DEFAULT '0',
	  		`date_end` int(11) NOT NULL DEFAULT '0',
  			`html` text NOT NULL,
  			`user_id` int(11) NOT NULL DEFAULT '0',
  			`edit_user_id` int(11) NOT NULL DEFAULT '0',
  			`add_date` int(11) NOT NULL DEFAULT '0',
  			`edit_date` int(11) NOT NULL DEFAULT '0',
  			`active` tinyint(4) NOT NULL DEFAULT '0',
  			PRIMARY KEY (`id`)
			// ENGINE=MyISAM DEFAULT CHARSET=utf8 
		",

		"log_ads_changes" => "
			`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`ads_id` int(10) unsigned NOT NULL DEFAULT '0',
			`author_id` int(10) unsigned NOT NULL DEFAULT '0',
			`action` text NOT NULL,
			`date` int(10) unsigned NOT NULL DEFAULT '0',
			PRIMARY KEY (`id`)
			// ENGINE=InnoDB DEFAULT CHARSET=utf8;
		",

*/
class yf_manage_advertising {

	function _init() {
		if (!isset($GLOBALS["advertising"])) {
			$GLOBALS["advertising"] = main()->init_class("advertising", "modules/");
		}
	}

	/**
	*/
	function exit_advertising(){
		setcookie("advertise", "", time()-3600,'/');
		return js_redirect("/");
	}
	
	/**
	*/
	function show(){
		return  $this->listing();
	}

	/**
	*/
	function edit(){
		$_GET["id"] = intval($_GET["id"]);
		// Do save data
		if (!empty($_POST)) {
			// Position could not be empty
			if (empty($_POST["ad"])) {
				_re("Place is empty");
			}
			// Content html could not be empty
			if (empty($_POST["html"])) {
				_re("Html is empty");
			}
			if (!_ee()) {
				return $this->save();
			}
		}
		$info = db()->query_fetch("SELECT * FROM ".db('advertising')." WHERE id=".$_GET['id']);
		$editor =  db()->query_fetch("SELECT * FROM ".db('sys_admin')." WHERE id=".$info["edit_user_id"]);
		$replace = array(
			'form_action' 	=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$_GET["id"],
			'ad'			=> $info["ad"],
			'editor'		=> $editor["first_name"]." ".$editor["last_name"],
			'edit_date'		=> date("d/m/Y",$info["edit_date"]),
			'customer'		=> $info["customer"],
			'date_start'	=> $info["date_start"] ? $info["date_start"] : time(),
			'date_end'		=> $info["date_end"] ? $info["date_end"] : time(),
			'cur_date'		=> time(),
			'html'			=> stripslashes($info["html"]),
			'active'		=> $info["active"],
			'error_message'	=> _e(),
			'back_link'		=> "./?object=".$_GET['object']."&action=listing",
		);
		return form2($replace)
			->info("ad","Placeholder")
			->info("editor","Last editor")
			->info("edit_date","Edit date")
			->text("customer","Customer")
			->text("ad","Placeholder")
			->textarea("html", "Content")
			->date_box("date_start","", array("desc" => "Date start"))
			->date_box("date_end","", array("desc" => "Date end"))
			->active_box()
			->save_and_back();
	}

	/**
	*/
	function delete(){
		$_GET['id'] = intval($_GET['id']);
		// Do delete records
		if (!empty($_GET['id'])) {
			db()->query("DELETE FROM `".db('advertising')."` WHERE `id`=".$_GET['id']." LIMIT 1");
			common()->admin_wall_add(array('advertising deleted: '.$_GET['id'], $_GET['id']));
		}
		$log = array(
			"ads_id" 	=>	$_GET['id'],
			"author_id"	=>	$_SESSION["admin_id"],
			"action"	=> 'delete',
			"date"		=> time(),
		);
		db()->INSERT("log_ads_changes", $log);
		return js_redirect("./?object=".$_GET['object']."&action=listing");
	}

	/**
	*/
	function listing(){
		if($_GET['ad']){
			$sql = "SELECT * FROM ".db('advertising')." WHERE ad='"._es($_GET['ad'])."'";
		} else {
			$sql = "SELECT * FROM ".db('advertising');
		}
		return table2($sql)
			->text("id")
			->text("ad")
			->func("html", function($field, $params) { return _prepare_html($field); }, array('desc' => 'Content'))
			->date("date_end")
			->text("customer")
			->func("edit_user_id", function($field, $params) { 
				$author = db()->query_fetch("SELECT first_name, last_name FROM ".db('sys_admin')." WHERE id =".$field);
				return $author['first_name']." ".$author['last_name'];}, array('desc' => 'Editor'))
			->btn_active()
			->btn_edit()
			->btn_delete()
			->footer_link("Exit visual debug mode", "./?object=manage_advertising&action=exit_advertising")
			->footer_link("Add new", "./?object=".$_GET["object"]."&action=edit")
			->footer_link("Show all", "./?object=".$_GET["object"]."&action=listing");
	}

	/**
	*/
	function save(){
		$_GET['id'] = intval($_GET['id']);
		$update = array(
			'ad'			=> _es($_POST['ad']),
			'customer'		=> _es($_POST['customer']),
			'date_start'	=> strtotime($_POST['date_start']['month']."/".$_POST['date_start']['day']."/".$_POST['date_start']['year']),
			'date_end'		=> strtotime($_POST['date_end']['month']."/".$_POST['date_end']['day']."/".$_POST['date_end']['year']),
			'html'			=> (!empty($_POST['html']))? _es($_POST['html']) : '',
			'edit_user_id'	=> $_SESSION["admin_id"],
			'edit_date'		=> time(),
			'active'		=> intval($_POST['active']),
		);
		//Write update data into DB
		if($_GET['id']){
			db()->UPDATE("advertising", $update, "id=".intval($_GET['id']));
		} else {
			$update['add_date'] = time();
			db()->INSERT("advertising", $update);
			$max_id = db()->query_fetch_row("SELECT MAX(id) FROM ".db('advertising'));
		}
		$log = array(
			'ads_id'		=>	$_GET['id'] ? $_GET['id'] : $max_id[0],
			'author_id'		=>	$_SESSION["admin_id"],	
			'date'			=> time(),
			'action'		=> $_GET['id'] ? "edit" : "add",
		);
		db()->INSERT("log_ads_changes", $log);
		common()->admin_wall_add(array('advertising updated: '.$_GET['id'], $_GET['id']));
		// Return user back
		return js_redirect("./?object=".$_GET['object']."&action=listing&ad=".$_POST['ad']);
	} 
}