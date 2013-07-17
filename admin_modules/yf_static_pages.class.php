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
	function _init() {
	}

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
				"name"		=> _es($name),
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
	function activate () {
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
		return stripslashes($page_info["text"]);
	}

	/**
	*/
	function print_view () {
		if (!empty($_GET['id'])) {
			$page_info = db()->query_fetch("SELECT * FROM ".db('static_pages')." WHERE name='"._es(_strtolower($_GET["id"]))."' OR id=".intval($_GET['id']));
		}
		$this->PAGE_NAME	= _prepare_html($page_info["name"]);
		$this->PAGE_TITLE	= _prepare_html($page_info["title"]);
		if (empty($page_info)) {
			_re(t("No such page!"));
			$body = _e();
		} else {
			$text = $this->ALLOW_HTML_IN_TEXT ? $page_info["text"] : _prepare_html($page_info["text"]);
			$body = common()->print_page($text);
		}
		return $body;
	}

	/**
	*/
	function pdf_view () {
		if (!empty($_GET['id'])) {
			$page_info = db()->query_fetch("SELECT * FROM ".db('static_pages')." WHERE name='"._es(_strtolower($_GET["id"]))."' OR id=".intval($_GET['id']));
		}
		$this->PAGE_NAME	= _prepare_html($page_info["name"]);
		$this->PAGE_TITLE	= _prepare_html($page_info["title"]);
		if (empty($page_info)) {
			_re(t("No such page!"));
			$body = _e();
		} else {
			$text = $this->ALLOW_HTML_IN_TEXT ? $page_info["text"] : _prepare_html($page_info["text"]);
			$body = common()->pdf_page($text, "page_".$page_info["name"]);
		}
		return $body;
	}

	/**
	*/
	function email_page () {
		if (!empty($_GET['id'])) {
			$page_info = db()->query_fetch("SELECT * FROM ".db('static_pages')." WHERE name='"._es(_strtolower($_GET["id"]))."' OR id=".intval($_GET['id']));
		}
		$this->PAGE_NAME	= _prepare_html($page_info["name"]);
		$this->PAGE_TITLE	= _prepare_html($page_info["title"]);
		if (empty($page_info)) {
			_re(t("No such page!"));
			$body = _e();
		} else {
			$body = common()->email_page($page_info["text"]);
		}
		return $body;
	}

	/**
	*/
	function _box ($name = "", $selected = "") {
		if (empty($name) || empty($this->_boxes[$name])) return false;
		else return eval("return common()->".$this->_boxes[$name].";");
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
