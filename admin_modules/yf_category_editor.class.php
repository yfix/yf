<?php

/**
* Categories editor
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_category_editor {

	/** @var int */
	public $ITEMS_PER_PAGE		= 50;
	/** @var bool */
	public $PROPOSE_SHORT_URL	= 1;

	/**
	*/
	function _init () {
		$this->_boxes = array(
			"active"		=> 'radio_box("active",			$this->_statuses,			$selected, false, 2, "", false)',
			"featured"		=> 'radio_box("featured",		$this->_statuses,			$selected, false, 2, "", false)',
			"parent_id"		=> 'select_box("parent_id",		$this->_items_for_parent,	$selected, false, 2, "", false)',
			"item_order"	=> 'select_box("item_order",	$this->_item_orders,		$selected, false, 2, "", false)',
			"groups"		=> 'multi_select("groups",		$this->_groups,				$selected, false, 2, " size=7 ", false)',
		);
		$this->_statuses = array(
			"0" => "<span class='negative'>NO</span>",
			"1" => "<span class='positive'>YES</span>",
		);
		$this->_user_groups[""] = "-- ALL --";
		$Q = db()->query("SELECT id,name FROM ".db('user_groups')." WHERE active='1'");
		while ($A = db()->fetch_assoc($Q)) {
			$this->_user_groups[$A['id']] = $A['name'];
		}
		$this->_admin_groups[""] = "-- ALL --";
		$Q = db()->query("SELECT id,name FROM ".db('admin_groups')." WHERE active='1'");
		while ($A = db()->fetch_assoc($Q)) {
			$this->_admin_groups[$A['id']] = $A['name'];
		}
	}

	/**
	*/
	function show() {
		$Q = db()->query("SELECT cat_id, COUNT(*) AS num FROM ".db('category_items')." GROUP BY cat_id");
		while ($A = db()->fetch_assoc($Q)) {
			$num_items[$A["cat_id"]] = $A["num"];
		}
		$sql = "SELECT * FROM ".db('categories')." ORDER BY type DESC, active ASC";
		$Q = db()->query($sql);
		while ($A = db()->fetch_assoc($Q)) {
			$replace2 = array(
				"bg_class"		=> !(++$i % 2) ? "bg1" : "bg2",
				"name"			=> _prepare_html($A["name"]),
				"desc"			=> _prepare_html($A["desc"]),
				"type"			=> _prepare_html($A["type"]),
				"stpl_name"		=> _prepare_html($A["stpl_name"]),
				"method_name"	=> _prepare_html($A["method_name"]),
				"custom_fields"	=> _prepare_html($A["custom_fields"]),
				"active"		=> intval($A["active"]),
				"num_items"		=> intval($num_items[$A["id"]]),
				"edit_link"		=> "./?object=".$_GET["object"]."&action=edit&id=".$A["id"],
				"delete_link"	=> "./?object=".$_GET["object"]."&action=delete&id=".$A["id"],
				"active_link"	=> "./?object=".$_GET["object"]."&action=activate&id=".$A["id"],
				"clone_link"	=> "./?object=".$_GET["object"]."&action=clone_cat&id=".$A["id"],
				"items_link"	=> "./?object=".$_GET["object"]."&action=show_items&id=".$A["id"],
				"export_link"	=> "./?object=".$_GET["object"]."&action=export&id=".$A["id"],
			);
			$items .= tpl()->parse($_GET["object"]."/item", $replace2);
		}
		$replace = array(
			"items"			=> $items,
			"form_action"	=> "./?object=".$_GET["object"]."&action=add",
		);
		$body .= tpl()->parse($_GET["object"]."/main", $replace);
/*
		$body .= table2($sql)
			->link('name', './?object='.$_GET['object'].'&action=show_items&id=%d')
			->text('desc')
			->text('custom_fields')
			->text('type')
			->text('items')
			->btn_edit()
			->btn_delete()
			->btn_clone('', './?object='.$_GET['object'].'&action=clone_cat&id=%d')
			->btn('Export', './?object='.$_GET['object'].'&action=export&id=%d')
			->btn_active()
			->footer_add();
*/
		return $body;
	}

	/**
	*/
	function add() {
		if ($_POST) {
			if (!common()->_error_exists()) {
				db()->INSERT("categories", array(
					"name"			=> _es($_POST["name"]),
					"desc"			=> _es($_POST["desc"]),
					"stpl_name"		=> _es($_POST["stpl_name"]),
					"method_name"	=> _es($_POST["method_name"]),
					"custom_fields"	=> _es($_POST["custom_fields"]),
					"active"		=> (int)((bool)$_POST["active"]),
					"type"			=> _es($_POST["type"]),
				));
				if (main()->USE_SYSTEM_CACHE) {
					cache()->refresh("category_sets");
				}
				return js_redirect("./?object=".$_GET["object"]);
			}
		}
		foreach ((array)$cat_info as $k => $v) {
			$DATA[$k] = isset($_POST[$k]) ? $_POST[$k] : $v;
		}
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"]."&action=".$_GET["action"],
			"name"			=> _prepare_html($DATA["name"]),
			"desc"			=> _prepare_html($DATA["desc"]),
			"type"			=> _prepare_html($DATA["type"]),
			"stpl_name"		=> _prepare_html($DATA["stpl_name"]),
			"method_name"	=> _prepare_html($DATA["method_name"]),
			"custom_fields"	=> _prepare_html($DATA["custom_fields"]),
			"active_box"	=> $this->_box("active", $DATA["active"]),
			"back_link"		=> "./?object=".$_GET["object"]."&action=show",
			"for_edit"		=> 0,
		);
		return common()->form2($replace)
			->radio_box("type", array('user' => 'User', 'admin' => 'Admin'))
			->text("name")
			->textarea("desc", "Description")
			->text("stpl_name")
			->text("method_name")
//			->text("custom_fields")
			->active_box()
			->save_and_back();
	}

	/**
	*/
	function edit() {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return _e("No id!");
		}
		$cat_info = db()->query_fetch("SELECT * FROM ".db('categories')." WHERE id=".intval($_GET["id"]));
		if (empty($cat_info["id"])) {
			return _e("No such category!");
		}
		if ($_POST) {
			if (!common()->_error_exists()) {
				db()->UPDATE("categories", array(
					"name"			=> _es($_POST["name"]),
					"desc"			=> _es($_POST["desc"]),
					"stpl_name"		=> _es($_POST["stpl_name"]),
					"method_name"	=> _es($_POST["method_name"]),
					"custom_fields"	=> _es($_POST["custom_fields"]),
					"active"		=> (int)((bool)$_POST["active"]),
				), "id=".intval($_GET["id"]));
				if (main()->USE_SYSTEM_CACHE) {
					cache()->refresh("category_sets");
				}
				return js_redirect("./?object=".$_GET["object"]);
			}
		}
		foreach ((array)$cat_info as $k => $v) {
			$DATA[$k] = isset($_POST[$k]) ? $_POST[$k] : $v;
		}
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$_GET["id"],
			"name"			=> _prepare_html($DATA["name"]),
			"desc"			=> _prepare_html($DATA["desc"]),
			"type"			=> _prepare_html($DATA["type"]),
			"stpl_name"		=> _prepare_html($DATA["stpl_name"]),
			"method_name"	=> _prepare_html($DATA["method_name"]),
			"custom_fields"	=> _prepare_html($DATA["custom_fields"]),
			"active"		=> $DATA["active"],
			"active_box"	=> $this->_box("active", $DATA["active"]),
			"back_link"		=> "./?object=".$_GET["object"]."&action=show",
			"for_edit"		=> 1,
		);
		return common()->form2($replace)
			->info("type")
			->text("name")
			->textarea("desc", "Description")
			->text("stpl_name")
			->text("method_name")
			->text("custom_fields")
			->active_box()
			->save_and_back()
			->render();
	}

	/**
	*/
	function delete() {
		$_GET["id"] = intval($_GET["id"]);
		if (!empty($_GET["id"])) {
			$cat_info = db()->query_fetch("SELECT * FROM ".db('categories')." WHERE id=".intval($_GET["id"]));
		}
		if (!empty($cat_info["id"])) {
			db()->query("DELETE FROM ".db('categories')." WHERE id=".intval($_GET["id"])." LIMIT 1");
			db()->query("DELETE FROM ".db('category_items')." WHERE cat_id=".intval($_GET["id"]));
		}
		if (main()->USE_SYSTEM_CACHE) {
			cache()->refresh("category_sets");
			cache()->refresh("category_items");
		}
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo $_GET["id"];
		} else {
			return js_redirect("./?object=".$_GET["object"]);
		}
	}

	/**
	*/
	function clone_cat() {
		$_GET["id"] = intval($_GET["id"]);
		if (!empty($_GET["id"])) {
			$cat_info = db()->query_fetch("SELECT * FROM ".db('categories')." WHERE id=".intval($_GET["id"]));
		}
		if (empty($cat_info["id"])) {
			return _e("No such category!");
		}
		$sql = $cat_info;
		unset($sql["id"]);
		$sql["name"] = $sql["name"]."_clone";

		db()->INSERT("categories", $sql);
		$NEW_CAT_ID = db()->INSERT_ID();

		$old_items = $this->_recursive_get_cat_items($cat_info["id"]);
		foreach ((array)$old_items as $_id => $_info) {
			unset($_info["id"]);
			unset($_info["level"]);
			$_info["cat_id"] = $NEW_CAT_ID;

			db()->INSERT("category_items", $_info);
			$NEW_ITEM_ID = db()->INSERT_ID();

			$_old_to_new[$_id] = $NEW_ITEM_ID;
			$_new_to_old[$NEW_ITEM_ID] = $_id;
		}
		foreach ((array)$_new_to_old as $_new_id => $_old_id) {
			$_old_info = $old_items[$_old_id];
			$_old_parent_id = $_old_info["parent_id"];
			if (!$_old_parent_id) {
				continue;
			}
			$_new_parent_id = intval($_old_to_new[$_old_parent_id]);
			db()->UPDATE("category_items", array("parent_id" => $_new_parent_id), "id=".intval($_new_id));
		}
		if (main()->USE_SYSTEM_CACHE) {
			cache()->refresh("category_sets");
			cache()->refresh("category_items");
		}
		return js_redirect("./?object=".$_GET["object"]);
	}

	/**
	*/
	function activate() {
		if (!empty($_GET["id"])) {
			$cat_info = db()->query_fetch("SELECT * FROM ".db('categories')." WHERE id=".intval($_GET["id"]));
		}
		if (!empty($cat_info)) {
			db()->UPDATE("categories", array("active" => (int)!$cat_info["active"]), "id=".intval($cat_info["id"]));
		}
		if (main()->USE_SYSTEM_CACHE) {
			cache()->refresh("category_sets");
		}
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo ($cat_info["active"] ? 0 : 1);
		} else {
			return js_redirect("./?object=".$_GET["object"]);
		}
	}

	/**
	*/
	function show_items() {
		$orig_id = $_GET["id"];
		$_GET["id"] = intval($_GET["id"]);
		if (!$_GET["id"] && $orig_id) {
			$cat_info = db()->get("SELECT * FROM ".db('categories')." WHERE name='".db()->es($orig_id)."'");
			if ($cat_info) {
				$_GET["id"] = $cat_info['id'];
			}
		}
		if (empty($_GET["id"])) {
			return _e("No id!");
		}
		if (!$cat_info) {
			$cat_info = db()->query_fetch("SELECT * FROM ".db('categories')." WHERE id=".intval($_GET["id"]));
		}
		if (empty($cat_info)) {
			return _e("No such category!");
		}
		$cat_items = $this->_recursive_get_cat_items($_GET["id"]);
		if ($_POST) {
			foreach ((array)$cat_items as $A) {
				if (!isset($_POST["name"][$A["id"]])) {
					continue;
				}
				db()->update("category_items", array(
					"name"		=> _es($_POST["name"][$A["id"]]),
					"url"		=> _es($_POST["url"][$A["id"]]),
					"other_info"=> _es($_POST["other_info"][$A["id"]]),
					"order"		=> intval($_POST["order"][$A["id"]]),
				), "id=".intval($A["id"]));
			}
			if (main()->USE_SYSTEM_CACHE) {
				cache()->refresh("category_items");
			}
			return js_redirect("./?object=".$_GET["object"]."&action=show_items&id=".$_GET["id"]);
		}
		// Slice items according to the current page
		$total = count($cat_items);
		$PER_PAGE = !empty($this->ITEMS_PER_PAGE) ? $this->ITEMS_PER_PAGE : conf('admin_per_page');
		list(,$pages,) = common()->divide_pages(null, null, null, $PER_PAGE, $total);
		// Get a slice from the whole array
		if (count($cat_items) > $PER_PAGE) {
			$cat_items = array_slice($cat_items, (empty($_GET["page"]) ? 0 : intval($_GET["page"]) - 1) * $PER_PAGE, $PER_PAGE);
		}
		if ($cat_info["type"] == "admin") {
			$this->_groups	= $this->_admin_groups;
			$this->_methods = $this->_admin_methods;
		} else {
			$this->_groups	= $this->_user_groups;
			$this->_methods = $this->_user_methods;
		}
		foreach ((array)$cat_items as $A) {
			if (empty($A)) {
				continue;
			}
			if (empty($A["url"]) && $this->PROPOSE_SHORT_URL) {
				$A["url"] = common()->_propose_url_from_name($A["name"]);
			}
			$groups = array();
			foreach (explode(",",$A["user_groups"]) as $k => $v) {
				if (empty($this->_groups[$v])) {
					continue;
				}
				$groups[] = $this->_groups[$v];
			}
			$groups = implode("<br />",$groups);

			$replace2 = array(
				"bg_class"		=> !(++$i % 2) ? "bg1" : "bg2",
				"item_id"		=> intval($A["id"]),
				"name"			=> _prepare_html($A["name"]),
				"desc"			=> _prepare_html($A["desc"]),
				"url"			=> _prepare_html($A["url"]),
				"other_info"	=> _prepare_html($A["other_info"]),
				"item_type"		=> $this->_item_types[$A["type_id"]],
				"groups"		=> $groups,
				"active"		=> intval($A["active"]),
				"order"			=> intval($A["order"]),
				"level_pad"		=> $A["level"] * 20,
				"edit_link"		=> "./?object=".$_GET["object"]."&action=edit_item&id=".$A["id"],
				"delete_link"	=> "./?object=".$_GET["object"]."&action=delete_item&id=".$A["id"],
				"active_link"	=> "./?object=".$_GET["object"]."&action=activate_item&id=".$A["id"],
				"clone_link"	=> "./?object=".$_GET["object"]."&action=clone_item&id=".$A["id"],
			);
			$items .= tpl()->parse($_GET["object"]."/category_items_item", $replace2);
		}
		$replace = array(
			"save_form_action"	=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$_GET["id"],
			"items"				=> $items,
			"pages"				=> $pages,
			"num_items"			=> intval($total),
			"category_name"		=> _prepare_html($cat_info["name"]),
			"add_item_link"		=> "./?object=".$_GET["object"]."&action=add_item&id=".$_GET["id"],
			"back_link"			=> "./?object=".$_GET["object"],
			"drag_link"			=> "./?object=".$_GET["object"]."&action=drag_items&id=".$_GET["id"],
		);
		return tpl()->parse($_GET["object"]."/category_items_main", $replace);
	}

	/**
	*/
	function drag_items() {
		$orig_id = $_GET["id"];
		$_GET["id"] = intval($_GET["id"]);
		if (!$_GET["id"] && $orig_id) {
			$cat_info = db()->get("SELECT * FROM ".db('categories')." WHERE name='".db()->es($orig_id)."'");
			if ($cat_info) {
				$_GET["id"] = $cat_info['id'];
			}
		}
		if (empty($_GET["id"])) {
			return _e("No id!");
		}
		if (!$cat_info) {
			$cat_info = db()->query_fetch("SELECT * FROM ".db('categories')." WHERE id=".intval($_GET["id"]));
		}
		if (empty($cat_info)) {
			return _e("No such category!");
		}
		$items = $this->_show_category_contents(array(
			'cat_info' => $cat_info,
		));
		if ($_POST) {
			$cur_items = $this->_auto_update_items_orders($cat_info["id"]);
			foreach ((array)$_POST["items"] as $order_id => $info) {
				$item_id = (int)$info["item_id"];
				if (!$item_id || !isset($items[$item_id])) {
					continue;
				}
				$parent_id = (int)$info['parent_id'];
				$new_data = array(
					"order"		=> intval($order_id),
					"parent_id"	=> intval($parent_id),
				);
				$old_info = $cur_items[$item_id];
				$old_data = array(
					"order"		=> intval($old_info['order']),
					"parent_id"	=> intval($old_info['parent_id']),
				);
				if ($new_data != $old_data) {
					db()->update('category_items', $new_data, 'id='.$item_id);
				}
			}
			main()->NO_GRAPHICS = true;
			return false;
		}
		foreach ((array)$items as $id => $item) {
			$item['edit_link']		= './?object='.$_GET['object'].'&action=edit_item&id='.$id;
			$item['delete_link']	= './?object='.$_GET['object'].'&action=delete_item&id='.$id;
			$item['active_link']	= './?object='.$_GET['object'].'&action=activate_item&id='.$id;
			$item['clone_link']		= './?object='.$_GET['object'].'&action=clone_item&id='.$id;
			$items[$id] = tpl()->parse($_GET['object'].'/drag_item', $item);
		}
		$replace = array(
			'items' 		=> implode("\n", (array)$items),
			'form_action'	=> './?object='.$_GET['object'].'&action='.$_GET['action'].'&id='.$_GET['id'],
			'add_link'		=> './?object='.$_GET['object'].'&action=add_item&id='.$_GET['id'],
			'back_link'		=> './?object='.$_GET['object'].'&action=show_items&id='.$_GET['id'],
		);
		return tpl()->parse($_GET['object'].'/drag_main', $replace);
	}

	/**
	*/
	function _show_category_contents ($params = array()) {
		$ICONS_PATH		= "uploads/icons/";
		$MEDIA_PATH		= WEB_PATH;
		$force_stpl_name= isset($params["force_stpl_name"]) ? $params["force_stpl_name"] : false;
		$STPL_MAIN 		= !empty($force_stpl_name) ? $force_stpl_name : $_GET['object'].'/drag_main';
		$STPL_ITEM		= !empty($force_stpl_name) ? $force_stpl_name.'_item' : $_GET['object'].'/drag_item';

		$cat_info		= $params["cat_info"];
		$cat_id			= $cat_info["id"];
		if (empty($cat_id)) {
			return _e("No id");
		}
		$cat_items = $this->_auto_update_items_orders($cat_id);
		if (empty($cat_items)) {
			return false;
		}
		// Update field 'have_children'
		foreach ((array)$cat_items as $id => $info) {
			$cat_items[$id]['have_children'] = 0;
		}
		foreach ((array)$cat_items as $id => $info) {
			$cat_items[$info['parent_id']]['have_children']++;
		}

		$cat_items_to_display = array_values($cat_items);
		$num_cat_items = count($cat_items_to_display);

		$_prev_level = 0;
		$_next_level = 0;
		$item_counter = 0;
		foreach ((array)$cat_items_to_display as $i => $item_info) {
			$item_counter++;
			$_next_info	= isset($cat_items_to_display[$i + 1]) ? $cat_items_to_display[$i + 1] : array();
			$_next_level = isset($_next_info["level"]) ? (int)$_next_info["level"] : 0;
			// Prepare icon path = WEB_PATH. $this->ICONS_PATH. $item_info["icon"];
			$icon_path = "";
			if ($item_info["icon"] && file_exists(PROJECT_PATH. $ICONS_PATH. $item_info["icon"])) {
				$icon_path = $MEDIA_PATH. $ICONS_PATH. $item_info["icon"];
			}
			// Icon class from bootstrap icon class names 
			$icon_class = "";
			if ($item_info["icon"] && (strpos($item_info["icon"], ".") === false)) {
				$icon_class = $item_info["icon"];
			}
			$items[$item_info["id"]] = array(
				"item_id"		=> intval($item_info['id']),
				"parent_id"		=> intval($item_info['parent_id']),
				"name"			=> _prepare_html(t($item_info['name'])),
				"level_num"		=> intval($item_info["level"]),
				"prev_level"	=> intval($_prev_level),
				"next_level"	=> intval($_next_level),
				"icon_path"		=> $icon_path,
				"icon_class"	=> $icon_class,
				"is_first_item"	=> (int)($item_counter == 1),
				"is_last_item"	=> (int)($item_counter == $num_cat_items),
				"have_children"	=> intval((bool)$item_info["have_children"]),
				"next_level_diff"=> intval(abs($item_info["level"] - $_next_level)),
				"link"			=> "",
				"active"		=> intval($item_info["active"]),
				"order"			=> intval($item_info['order']),
			);
			// Save current level for the next iteration
			$_prev_level = $item_info["level"];
		}
		return $items;
	}

	/**
	*/
	function _auto_update_items_orders($cat_info) {
		$cat_items = $this->_recursive_get_cat_items($cat_info["id"]);
		$new_order = 1;
		foreach ((array)$cat_items as $item_id => $info) {
			if ($info['order'] != $new_order) {
				db()->update('category_items', array('order' => $new_order), 'id='.$item_id);
				$cat_items[$item_id]['order'] = $new_order;
			}
			$new_order++;
		}
		return $cat_items;
	}

	/**
	*/
	function _recursive_get_cat_items($cat_id = 0, $skip_item_id = 0, $parent_id = 0, $level = 0) {
		if (!isset($this->_category_items_from_db)) {
			$Q = db()->query("SELECT * FROM ".db('category_items')." WHERE cat_id=".intval($cat_id)." ORDER BY `order` ASC");
			while ($A = db()->fetch_assoc($Q)) {
				$this->_category_items_from_db[$A["id"]] = $A;
			}
		}
		if (empty($this->_category_items_from_db)) {
			return "";
		}
		$items_ids		= array();
		$items_array	= array();
		foreach ((array)$this->_category_items_from_db as $item_info) {
			if ($item_info["parent_id"] != $parent_id) {
				continue;
			}
			if ($skip_item_id == $item_info["id"]) {
				continue;
			}
			$items_array[$item_info["id"]] = $item_info;
			$items_array[$item_info["id"]]["level"] = $level;

			$tmp_array = $this->_recursive_get_cat_items($cat_id, $skip_item_id, $item_info["id"], $level + 1);
			foreach ((array)$tmp_array as $sub_item_info) {
				if ($sub_item_info["id"] == $item_info["id"]) {
					continue;
				}
				$items_array[$sub_item_info["id"]] = $sub_item_info;
			}
		}
		return $items_array;
	}

	/**
	*/
	function add_item() {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return _e(t("No id!"));
		}
		$cat_info = db()->query_fetch("SELECT * FROM ".db('categories')." WHERE id=".intval($_GET["id"]));
		if (empty($cat_info["id"])) {
			return _e(t("No such category!"));
		}
		if ($_POST) {
			if (is_array($_POST["groups"]))	{
				$_POST["groups"] = implode(",",$_POST["groups"]);
			}
			$_POST["groups"]	= str_replace(array(" ","\t","\r","\n"), "", $_POST["groups"]);

			$OTHER_INFO = array();
			foreach (explode(",", $cat_info["custom_fields"]) as $cur_field_name) {
				if (!strlen($cur_field_name)) {
					continue;
				}
				$OTHER_INFO[] =	$cur_field_name."=".$_POST["custom__".$cur_field_name];
			}
			db()->INSERT("category_items", array(
				"cat_id"		=> intval($_GET["id"]),
				"type_id"		=> intval($_POST["type_id"]),
				"parent_id"		=> intval($_POST["parent_id"]),
				"name"			=> _es($_POST["name"]),
				"desc"			=> _es($_POST["desc"]),
				"url"			=> _es($_POST["url"]),
				"icon"			=> _es($_POST["icon"]),
				"user_groups"	=> _es($_POST["groups"]),
				"other_info"	=> _es(implode(";", $OTHER_INFO)),
				"featured"		=> intval($_POST["featured"]),
				"order"			=> intval($_POST["item_order"]),
				"active"		=> intval($_POST["active"]),
			));
			if (main()->USE_SYSTEM_CACHE)	{
				cache()->refresh("category_items");
			}
			return js_redirect("./?object=".$_GET["object"]."&action=show_items&id=".$cat_info["id"]);
		}
		$this->_items_for_parent[0] = "-- TOP --";
		foreach ((array)$this->_recursive_get_cat_items($_GET["id"]) as $cur_item_id => $cur_item_info) {
			if (empty($cur_item_id)) continue;
			$this->_items_for_parent[$cur_item_id] = str_repeat("&nbsp;", $cur_item_info["level"] * 6)." &#9492; ".$cur_item_info["name"];
		}
		if ($cat_info["type"] == "admin") {
			$this->_groups	= $this->_admin_groups;
			$this->_methods = $this->_admin_methods;
		} else {
			$this->_groups	= $this->_user_groups;
			$this->_methods = $this->_user_methods;
		}
		$other_info_array = $this->_convert_atts_string_into_array($item_info["other_info"]);
		foreach (explode(",", $cat_info["custom_fields"]) as $cur_field_name) {
			if (empty($cur_field_name)) {
				continue;
			}
			$CUSTOM_FIELDS[] = array(
				"name"	=> _prepare_html($cur_field_name),
				"value"	=> _prepare_html($other_info_array[$cur_field_name]),
			);
		}
		$replace = array(
			"form_action"		=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$_GET["id"],
			"category_name"		=> _prepare_html($cat_info["name"]),
			"name"				=> _prepare_html($DATA["name"]),
			"desc"				=> nl2br(_prepare_html($DATA["desc"])),
			"other_info"		=> _prepare_html($DATA["other_info"]),
			"custom_fields"		=> $CUSTOM_FIELDS,
			"url"				=> _prepare_html($DATA["url"]),
			"icon"				=> _prepare_html($DATA["icon"]),
			"order"				=> intval($DATA["order"]),
			"type_id_box"		=> $this->_box("type_id", ""),
			"parent_id_box"		=> $this->_box("parent_id", ""),
			"groups_box"		=> $this->_box("groups", array(""=>"-- ALL --")),
			"methods_box"		=> $this->_box("methods", ""),
			"active_box"		=> $this->_box("active", $DATA["active"]),
			"featured_box"		=> $this->_box("featured", ""),
			"back_link"			=> "./?object=".$_GET["object"]."&action=show_items&id=".intval($cat_info["id"]),
			"for_edit"			=> 0,
			"edit_modules_link"	=> "./?object=".$cat_info["type"]."_modules",
			"edit_groups_link"	=> "./?object=".$cat_info["type"]."_groups",
		);
		return tpl()->parse($_GET["object"]."/edit_item_form", $replace);
	}

	/**
	*/
	function edit_item() {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return _e(t("No id!"));
		}
		$item_info = db()->query_fetch("SELECT * FROM ".db('category_items')." WHERE id=".intval($_GET["id"]));
		if (empty($item_info["id"])) {
			return _e(t("No such category item!"));
		}
		$cat_info = db()->query_fetch("SELECT * FROM ".db('categories')." WHERE id=".intval($item_info["cat_id"]));
		if (empty($cat_info["id"])) {
			return _e(t("No such category!"));
		}
		if ($_POST) {
			if (is_array($_POST["groups"]))	{
				$_POST["groups"] = implode(",",$_POST["groups"]);
			}
			$_POST["groups"] = str_replace(array(" ","\t","\r","\n"), "", $_POST["groups"]);

			$OTHER_INFO = array();
			foreach (explode(",", $cat_info["custom_fields"]) as $cur_field_name) {
				if (!strlen($cur_field_name)) {
					continue;
				}
				$OTHER_INFO[] =	$cur_field_name."=".$_POST["custom__".$cur_field_name];
			}
			db()->UPDATE("category_items", array(
				"parent_id"		=> intval($_POST["parent_id"]),
				"name"			=> _es($_POST["name"]),
				"desc"			=> _es($_POST["desc"]),
				"url"			=> _es($_POST["url"]),
				"icon"			=> _es($_POST["icon"]),
				"user_groups"	=> _es($_POST["groups"]),
				"other_info"	=> _es(implode(";", $OTHER_INFO)),
				"featured"		=> intval($_POST["featured"]),
				"type_id"		=> intval($_POST["type_id"]),
				"order"			=> intval($_POST["item_order"]),
				"active"		=> intval($_POST["active"]),
			), "id=".intval($item_info["id"]));
			if (main()->USE_SYSTEM_CACHE)	{
				cache()->refresh("category_items");
			}
			return js_redirect("./?object=".$_GET["object"]."&action=show_items&id=".$cat_info["id"]);
		}
		$this->_items_for_parent[0] = "-- TOP --";
		foreach ((array)$this->_recursive_get_cat_items($cat_info["id"], $_GET["id"]) as $cur_item_id => $cur_item_info) {
			if (empty($cur_item_id)) continue;
			$this->_items_for_parent[$cur_item_id] = str_repeat("&nbsp; &nbsp; &nbsp; ", $cur_item_info["level"])." &#9492; &nbsp; ".$cur_item_info["name"];
		}
		$item_info["user_groups"]	= explode(",",str_replace(array(" ","\t","\r","\n"), "", $item_info["user_groups"]));
		foreach ((array)$item_info["user_groups"] as $v) $tmp[$v] = $v;
		$item_info["user_groups"] = $tmp;

		if ($cat_info["type"] == "admin") {
			$this->_groups	= $this->_admin_groups;
			$this->_methods = $this->_admin_methods;
		} else {
			$this->_groups	= $this->_user_groups;
			$this->_methods = $this->_user_methods;
		}
		if (empty($item_info["url"]) && $this->PROPOSE_SHORT_URL) {
			$item_info["url"] = common()->_propose_url_from_name($item_info["name"]);
		}
		$other_info_array = $this->_convert_atts_string_into_array($item_info["other_info"]);
		foreach (explode(",", $cat_info["custom_fields"]) as $cur_field_name) {
			if (empty($cur_field_name)) {
				continue;
			}
			$CUSTOM_FIELDS[] = array(
				"name"	=> _prepare_html($cur_field_name),
				"value"	=> _prepare_html($other_info_array[$cur_field_name]),
			);
		}
		$replace = array(
			"form_action"		=> "./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".$_GET["id"],
			"category_name"		=> _prepare_html($cat_info["name"]),
			"name"				=> _prepare_html($item_info["name"]),
			"desc"				=> nl2br(_prepare_html($item_info["desc"])),
			"other_info"		=> _prepare_html($item_info["other_info"]),
			"custom_fields"		=> $CUSTOM_FIELDS,
			"url"				=> _prepare_html($item_info["url"]),
			"icon"				=> _prepare_html($item_info["icon"]),
			"order"				=> intval($item_info["order"]),
			"active"			=> $item_info["active"],
			"type_id_box"		=> $this->_box("type_id",	$item_info["type_id"]),
			"parent_id_box"		=> $this->_box("parent_id", $item_info["parent_id"]),
			"groups_box"		=> $this->_box("groups",	$item_info["user_groups"]),
			"methods_box"		=> $this->_box("methods",	""),
			"active_box"		=> $this->_box("active", 	$item_info["active"]),
			"featured_box"		=> $this->_box("featured",	$item_info["featured"]),
			"back_link"			=> "./?object=".$_GET["object"]."&action=show_items&id=".intval($cat_info["id"]),
			"for_edit"			=> 1,
			"edit_modules_link"	=> "./?object=".$cat_info["type"]."_modules",
			"edit_groups_link"	=> "./?object=".$cat_info["type"]."_groups",
		);
		return tpl()->parse($_GET["object"]."/edit_item_form", $replace);
	}

	/**
	*/
	function _convert_atts_string_into_array($string = "") {
		$output_array = array();
		foreach (explode(";", trim($string)) as $tmp_string) {
			list($try_key, $try_value) = explode("=", trim($tmp_string));
			$try_key = trim(trim(trim($try_key), '"'));
			$try_value = trim(trim(trim($try_value), '"'));
			if (strlen($try_key) && strlen($try_value)) {
				$output_array[$try_key] = $try_value;
			}
		}
		return $output_array;
	}

	/**
	*/
	function activate_item() {
		if (!empty($_GET["id"])) {
			$item_info = db()->query_fetch("SELECT * FROM ".db('category_items')." WHERE id=".intval($_GET["id"]));
		}
		if (!empty($item_info)) {
			db()->UPDATE("category_items", array("active" => (int)!$item_info["active"]), "id=".intval($item_info["id"]));
		}
		if (main()->USE_SYSTEM_CACHE)	{
			cache()->refresh("category_items");
		}
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo ($item_info["active"] ? 0 : 1);
		} else {
			return js_redirect("./?object=".$_GET["object"]."&action=show_items&id=".$item_info["cat_id"]);
		}
	}

	/**
	*/
	function delete_item() {
		$_GET["id"] = intval($_GET["id"]);
		if (!empty($_GET["id"])) {
			$item_info = db()->query_fetch("SELECT * FROM ".db('category_items')." WHERE id=".intval($_GET["id"]));
		}
		if (!empty($item_info)) {
			db()->query("DELETE FROM ".db('category_items')." WHERE id=".intval($_GET["id"]));
		}
		if (main()->USE_SYSTEM_CACHE)	{
			cache()->refresh("category_items");
		}
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo $_GET["id"];
		} else {
			return js_redirect("./?object=".$_GET["object"]."&action=show_items&id=".$item_info["cat_id"]);
		}
	}

	/**
	*/
	function clone_item() {
		$_GET["id"] = intval($_GET["id"]);
		if (!empty($_GET["id"])) {
			$item_info = db()->query_fetch("SELECT * FROM ".db('category_items')." WHERE id=".intval($_GET["id"]));
		}
		$sql = $item_info;
		unset($sql["id"]);
		db()->INSERT("category_items", $sql);
		if (main()->USE_SYSTEM_CACHE)	{
			cache()->refresh("category_items");
		}
		return js_redirect("./?object=".$_GET["object"]."&action=show_items&id=".$item_info["cat_id"]);
	}

	/**
	*/
	function export() {
		// If no ID set - mean that simply export all categories with items
		$_GET["id"] = intval($_GET["id"]);
		if ($_GET["id"]) {
			$cat_info = db()->query_fetch("SELECT * FROM ".db('categories')." WHERE id=".intval($_GET["id"]));
		}
		$params = array(
			"single_table"	=> "",
			"tables"		=> array(db('categories'), db('category_items')),
			"full_inserts"	=> 1,
			"ext_inserts"	=> 1,
			"export_type"	=> "insert",
			"silent_mode"	=> true,
		);
		if ($cat_info["id"]) {
			$params["where"] = array(
				db('categories')		=> "id=".intval($cat_info["id"]),
				db('category_items')	=> "cat_id=".intval($cat_info["id"]),
			);
		}
		$EXPORTED_SQL = module("db_manager")->export($params);

		$replace = array(
			"sql_text"	=> _prepare_html($EXPORTED_SQL, 0),
			"back_link"	=> "./?object=".$_GET["object"],
		);
		return tpl()->parse("db_manager/export_text_result", $replace);
	}

	/**
	*/
	function _box ($name = "", $selected = "") {
		if (empty($name) || empty($this->_boxes[$name])) return false;
		else return eval("return common()->".$this->_boxes[$name].";");
	}
}
