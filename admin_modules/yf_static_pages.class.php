<?php

/**
* Static/HTML pages content editor
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_static_pages {

	/**
	*/
	function show() {
		$sql = "SELECT * FROM ".db('static_pages');
		return common()->table2($sql)
			->text("name")
			->btn_edit()
			->btn_delete()
			->btn('View', './?object='.$_GET['object'].'&action=view&id=%d')
			->btn_active()
			->footer_link('Add', './?object='.$_GET['object'].'&action=add')
			->render();
	}

	/**
	*/
	function add() {
		if (empty($_POST['name'])) {
			return common()->form2(array('back_link' => './?object='.$_GET['object']))
				->text('name')
				->save_and_back()
				->render();
		}
		$name = preg_replace("/[^a-z0-9\_\-]/i", "_", _strtolower($_POST['name']));
		$name = str_replace(array("__", "___"), "_", $name);
		if (strlen($name)) {
			db()->INSERT("static_pages", array(
				"name"	=> _es($name),
			));
		}
		if (main()->USE_SYSTEM_CACHE) {
			cache()->refresh("static_pages_names");
		}
		if (!empty($name)) {
			return js_redirect("./?object=".$_GET["object"]."&action=edit&id=".urlencode($name));
		} else {
			return _e(t("Can't insert record!"));
		}
	}

	/**
	*/
	function edit() {
		if (!isset($_GET['id'])) {
			return _e('No id');
		}
		$page_info = db()->get("SELECT * FROM ".db('static_pages')." WHERE name='"._es(_strtolower(urldecode($_GET['id'])))."' OR id=".intval($_GET['id'])." LIMIT 1");
		if (!$page_info) {
			return _e('No page info');
		}
		if (!empty($_POST)) {
			if (isset($_POST['name'])) {
				$_POST['name'] = preg_replace("/[^a-z0-9\_\-]/i", "_", _strtolower($_POST['name']));
				$_POST['name'] = str_replace(array("__", "___"), "_", $_POST['name']);
			}
			$sql_array = array(
				"name"			=> $_POST["name"],
				"text"			=> $_POST["text"],
				"page_title"	=> $_POST["page_title"],
				"page_heading"	=> $_POST["page_heading"],
				"meta_keywords"	=> $_POST["meta_keywords"],
				"meta_desc"		=> $_POST["meta_desc"],
				"active"		=> intval((bool)$_POST['active']),
			);
			if ($sql_array["text"]) {
				db()->UPDATE("static_pages", db()->es($sql_array), "id=".intval($page_info['id']));
			}
			if (main()->USE_SYSTEM_CACHE) {
				cache()->refresh("static_pages_names");
			}
			return js_redirect("./?object=".$_GET["object"]);
		}
		$DATA = $page_info;
		foreach ((array)$_POST as $k => $v) {
			$DATA[$k] = $v;
		}
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".urlencode($page_name),
			"name"			=> $DATA["name"],
			"text"			=> $DATA["text"],
			"page_title"	=> $DATA["page_title"],
			"page_heading"	=> $DATA["page_heading"],
			"meta_keywords"	=> $DATA["meta_keywords"],
			"meta_desc"		=> $DATA["meta_desc"],
			"active"		=> $DATA['active'],
			"back_url"		=> "./?object=".$_GET["object"],
		);
		return common()->form2($replace)
			->text("name")
			->textarea("text","",array('class' => 'span4','rows' => '10'))
			->text("page_title")
			->text("page_heading")
			->text("meta_keywords")
			->text("meta_desc")
			->active_box()
			->save_and_back()
			->render();
	}

	/**
	*/
	function delete() {
		if (isset($_GET['id'])) {
			db()->query("DELETE FROM ".db('static_pages')." WHERE name='"._es(urldecode($_GET['id']))."' OR id=".intval($_GET['id']));
		}
		if (main()->USE_SYSTEM_CACHE) {
			cache()->refresh("static_pages_names");
		}
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo $page_name;
		} else {
			return js_redirect("./?object=".$_GET["object"]);
		}
	}

	/**
	*/
	function active () {
		if (isset($_GET['id'])) {
			$page_info = db()->query_fetch("SELECT * FROM ".db('static_pages')." WHERE name='"._es(_strtolower(urldecode($_GET['id'])))."' OR id=".intval($_GET['id']));
		}
		if (!empty($page_info["id"])) {
			db()->UPDATE("static_pages", array("active" => (int)!$page_info["active"]), "id=".intval($page_info["id"]));
			if (main()->USE_SYSTEM_CACHE) {
				cache()->refresh("static_pages_names");
			}
		}
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo ($page_info["active"] ? 0 : 1);
		} else {
			return js_redirect("./?object=".$_GET["object"]);
		}
	}

	/**
	*/
	function view() {
		if (!empty($_GET['id'])) {
			$page_info = db()->query_fetch("SELECT * FROM ".db('static_pages')." WHERE name='"._es(_strtolower(urldecode($_GET["id"])))."' OR id=".intval($_GET['id']));
		}
		if (empty($page_info)) {
			return _e('No such page!');
		}
		$body .= '<script src="'.WEB_PATH.'ckeditor/ckeditor.js"></script>';
		$body .= "
	<script>
CKEDITOR.appendTo( 'container_id',
{ /* Configuration options to be used. */ }
'Editor content to be used.'
);
		CKEDITOR.on( 'instanceCreated', function( event ) {
			var editor = event.editor,	element = editor.element;
				editor.on( 'configLoaded', function() {
					// Remove unnecessary plugins to make the editor simpler.
//					editor.config.removePlugins = 'colorbutton,find,flash,font,' +
//						'forms,iframe,image,newpage,removeformat,' +
//						'smiley,specialchar,stylescombo,templates';

					// Rearrange the layout of the toolbar.
					editor.config.toolbarGroups = [
						{ name: 'editing',		groups: [ 'basicstyles', 'links' ] },
						{ name: 'undo' },
						{ name: 'clipboard',	groups: [ 'selection', 'clipboard' ] },
						{ name: 'about' }
					];
				});
		});
	</script>
		";
// TODO: embed visual editor code
		$body .= '<div id="text_content" contenteditable="true">'.stripslashes($page_info["text"]).'</div>';
		return $body;
	}

	/**
	*/
	function _show_header() {
		$pheader = t("Static pages");
		$subheader = _ucwords(str_replace("_", " ", $_GET["action"]));
		$cases = array (
			//$_GET["action"] => {string to replace}
			"show"			=> "",
			"edit"			=> "",
		);			 		
		if (isset($cases[$_GET["action"]])) {
			$subheader = $cases[$_GET["action"]];
		}
		return array(
			"header"	=> $pheader,
			"subheader"	=> $subheader ? _prepare_html($subheader) : "",
		);
	}
}
