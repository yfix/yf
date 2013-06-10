<?php

/**
* Manage shop sub module
*/
class yf_manage_shop_manufacturer {

	/** @var string Folder where product's images store */
	public $MAN_IMG_DIR		= "shop/manufacturer/";
	/** @var string fullsize image suffix (underscore at the beginning required)*/
	public $FULL_IMG_SUFFIX	= "_full";
	/** @var string Thumb image suffix (underscore at the beginning required)*/
	public $THUMB_SUFFIX		= "_small";
	/** @var string Image prefix */
//	public $IMG_PREFIX			= "product_";
	/** @var int Thumb size X */
	public $THUMB_X			= 100;
	/** @var int Thumb size Y */
	public $THUMB_Y			= 100;
	/**
	* Constructor
	*/
	
	function _init () {
		// Reference to the parent object
		$this->PARENT_OBJ	= module("manage_shop");
		$this->manufacturer_img_dir 	= INCLUDE_PATH. SITE_UPLOADS_DIR. $this->MAN_IMG_DIR;
		$this->manufacturer_img_webdir	= WEB_PATH. SITE_UPLOADS_DIR. $this->MAN_IMG_DIR;
	}
	/**
	* Show manufacturer
	*/
	function show_manufacturer() {
	
		$sql = "SELECT * FROM `".db('shop_manufacturer')."`";
		$filter_sql = $this->PARENT_OBJ->USE_FILTER ? $this->PARENT_OBJ->_create_filter_sql() : "";
		$sql .= strlen($filter_sql) ? " WHERE 1=1 ". $filter_sql : " ORDER BY `name` ASC ";
		list($add_sql, $pages, $total) = common()->divide_pages($sql);
		$orders_info = db()->query_fetch_all($sql.$add_sql);

		if (!empty($orders_info)) {
			foreach ((array)$orders_info as $v){
				$user_ids[] = $v["user_id"];
			}
			$user_infos = user($user_ids);
		}

		foreach ((array)$orders_info as $v){
			$items[] = array(
				"order_id"			=> $v["id"],
				"name"				=> $v["name"],
				"sort_order"		=> $v["sort_order"],
				"view_url"			=> "./?object=".$_GET["object"]."&action=view_manufacturer&id=".$v["id"],
				"delete_url"		=> "./?object=".$_GET["object"]."&action=delete_manufacturer&id=".$v["id"],
				"edit_url"			=> "./?object=".$_GET["object"]."&action=edit_manufacturer&id=".$v["id"],
			);
		}
		$replace = array(
			"items"			=> (array)$items,
			"pages"			=> $pages,
			"total"			=> intval($total),
			"filter"		=> $this->PARENT_OBJ->USE_FILTER ? $this->PARENT_OBJ->_show_filter() : "",
			"add_url"		=> "./?object=".$_GET['object']."&action=add_manufacturer",
		);
		return tpl()->parse($_GET["object"]."/manufacturer_main", $replace); 
	}

	/**
	*add manufacturer
	*/
	function add_manufacturer() {
		if (!empty($_POST)) {

			if (!$_POST["name"]) {
				_re("Product name must be filled");
			}

			if (!common()->_error_exists()) {
				// Save data
				$url			= _es(common()->_propose_url_from_name($_POST["name"]));
				$sql_array = array(
					"name"			=> _es($_POST["name"]),
					"url"			=> $url,
					"desc"	=> _es($_POST["desc"]),
					"sort_order"	=> intval($_POST["featured"]),
				);
				db()->UPDATE(db('shop_manufacturer'), $sql_array, "`id`=".$_GET["id"]);
				
				// Image upload
				if (!empty($_FILES)) {
					$man_id = $_GET["id"];
					$this->_upload_image ($man_id, $url);
				} 
			}
			return js_redirect("./?object=".$_GET["object"]."&action=show_manufacturer");
		}

		$thumb_path = $this->manufacturer_img_dir.$manufacturer_info["url"]."_".$manufacturer_info["id"].$this->THUMB_SUFFIX. ".jpg";
		if (!file_exists($thumb_path)) {
			$thumb_path = "";
		} else {
			$thumb_path = $this->manufacturer_img_webdir.$manufacturer_info["url"]."_".$manufacturer_info["id"].$this->THUMB_SUFFIX. ".jpg";
		}

		
		$replace = array(
			"name"				=> "",
			"sort_order"		=> "",
			"desc"				=> "",
			"thumb_path"		=> "",
			"delete_image_url"	=> "./?object=".$_GET['object']."&action=delete_image&id=".$manufacturer_info["id"],
			"form_action"		=> "./?object=".$_GET['object']."&action=edit_manufacturer&id=".$manufacturer_info["id"],
			"back_url"			=> "./?object=".$_GET["object"]."&action=show_manufacturer",
			
		);
		return tpl()->parse($_GET["object"]."/manufacturer_edit", $replace);
	}


	
	/**
	*edit manufacturer
	*/
	function edit_manufacturer() {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return "Empty ID!";
		}
		$manufacturer_info = db()->query_fetch("SELECT * FROM `".db('shop_manufacturer')."` WHERE `id`=".$_GET["id"]);

		if (!empty($_POST)) {

			if (!$_POST["name"]) {
				_re("Product name must be filled");
			}

			if (!common()->_error_exists()) {
				// Save data
				$url			= _es(common()->_propose_url_from_name($_POST["name"]));
				$sql_array = array(
					"name"			=> _es($_POST["name"]),
					"url"			=> $url,
					"desc"	=> _es($_POST["desc"]),
					"sort_order"	=> intval($_POST["featured"]),
				);
				db()->UPDATE(db('shop_manufacturer'), $sql_array, "`id`=".$_GET["id"]);
				
				// Image upload
				if (!empty($_FILES)) {
					$man_id = $_GET["id"];
					$this->_upload_image ($man_id, $url);
				} 
			}
			return js_redirect("./?object=".$_GET["object"]."&action=show_manufacturer");
		}

		$thumb_path = $this->manufacturer_img_dir.$manufacturer_info["url"]."_".$manufacturer_info["id"].$this->THUMB_SUFFIX. ".jpg";
		if (!file_exists($thumb_path)) {
			$thumb_path = "";
		} else {
			$thumb_path = $this->manufacturer_img_webdir.$manufacturer_info["url"]."_".$manufacturer_info["id"].$this->THUMB_SUFFIX. ".jpg";
		}

		
		$replace = array(
			"name"				=> $manufacturer_info["name"],
			"sort_order"		=> $manufacturer_info["sort_order"],
			"desc"				=> $manufacturer_info["desc"],
			"thumb_path"		=> $thumb_path,
			"delete_image_url"	=> "./?object=".$_GET['object']."&action=delete_image&id=".$manufacturer_info["id"],
			"form_action"		=> "./?object=".$_GET['object']."&action=edit_manufacturer&id=".$manufacturer_info["id"],
			"back_url"			=> "./?object=".$_GET["object"]."&action=show_manufacturer",
			
		);
		return tpl()->parse($_GET["object"]."/manufacturer_edit", $replace);
	}

	/**
	*view manufacturer
	*/
	function view_manufacturer() {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return "Empty ID!";
		}
		$manufacturer_info = db()->query_fetch("SELECT * FROM `".db('shop_manufacturer')."` WHERE `id`=".$_GET["id"]);
		$img_path = $this->manufacturer_img_dir.$manufacturer_info["url"]."_".$manufacturer_info["id"].$this->FULL_IMG_SUFFIX. ".jpg";
		if (!file_exists($img_path)) {
			$img_path = "";
		} else {
			$img_path = $this->manufacturer_img_webdir.$manufacturer_info["url"]."_".$manufacturer_info["id"].$this->FULL_IMG_SUFFIX. ".jpg";
		}
		$thumb_path = $this->manufacturer_img_dir.$manufacturer_info["url"]."_".$manufacturer_info["id"].$this->THUMB_SUFFIX. ".jpg";
		if (!file_exists($thumb_path)) {
			$thumb_path = "";
		} else {
			$thumb_path = $this->manufacturer_img_webdir.$manufacturer_info["url"]."_".$manufacturer_info["id"].$this->THUMB_SUFFIX. ".jpg";
		}
		$replace = array(
			"name"				=> $manufacturer_info["name"],
			"sort_order"		=> $manufacturer_info["sort_order"],
			"desc"				=> _prepare_html($manufacturer_info["desc"]),
			"thumb_path"		=> $thumb_path,
			"img_path"			=> $img_path,
			"delete_image_url"	=> "./?object=".$_GET['object']."&action=delete_image&id=".$manufacturer_info["id"],
			"form_action"		=> "./?object=".$_GET['object']."&action=edit_manufacturer&id=".$manufacturer_info["id"],
			"back_url"			=> "./?object=".$_GET["object"]."&action=show_manufacturer",
			
		);
		return tpl()->parse($_GET["object"]."/manufacturer_view", $replace);
	}
	
	/**
	* Delete manufacturer
	*/
	function delete_manufacturer() {
		$_GET["id"] = intval($_GET["id"]);
		// Get current info
		if (!empty($_GET["id"])) {
			$order_info = db()->query_fetch("SELECT * FROM `".db('shop_orders')."` WHERE `id`=".intval($_GET["id"]));
		}
		// Do delete order
		if (!empty($order_info["id"])) {
			db()->query("DELETE FROM `".db('shop_orders')."` WHERE `id`=".intval($_GET["id"])." LIMIT 1");
			db()->query("DELETE FROM `".db('shop_order_items')."` WHERE `order_id`=".intval($_GET["id"]));
		}
		// Return user back
		if ($_POST["ajax_mode"]) {
			main()->NO_GRAPHICS = true;
			echo $_GET["id"];
		} else {
			return js_redirect("./?object=".$_GET["object"]."&action=show_manufacturer");
		}
	}
	
	function upload_image () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return "Empty ID!";
		}

		$this->_upload_image($_GET["id"]);
		return js_redirect($_SERVER["HTTP_REFERER"]);
	}

	/**
	* Upload image
	*/
	function _upload_image ($man_id, $url) {

		$img_properties = getimagesize($_FILES['image']['tmp_name']);
		if (empty($img_properties) || !$man_id) {
			return false;
		}
		$img_path = $this->manufacturer_img_dir.$url."_".$man_id.$this->FULL_IMG_SUFFIX. ".jpg";
		$thumb_path = $this->manufacturer_img_dir.$url."_".$man_id.$this->THUMB_SUFFIX. ".jpg";
		// Do upload image
		$upload_result = common()->upload_image($img_path);
		if ($upload_result) {
			// Make thumb
			$resize_result = common()->make_thumb($img_path, $thumb_path, $this->THUMB_X, $this->THUMB_Y);
		}
		
		return true;
	}

	/**
	* Delete image
	*/
	function _delete_image ($man_id) {

		$image_files = $this->DIR_OBJ->scan_dir($this->manufacturer_img_dir, true, "/".$this->IMG_PREFIX.$man_id."_/img");
		foreach((array)$image_files as $filepath) {
			unlink($filepath);
		}
		return true;
	}

	/**
	* Delete image
	*/
	function delete_image () {
		$_GET["id"] = intval($_GET["id"]);
		if (empty($_GET["id"])) {
			return "Empty ID!";
		}

		$this->_delete_image($_GET["id"]);
		return js_redirect($_SERVER["HTTP_REFERER"]);
	}

}
