<?php

class yf_shop_supplier_panel_upload_images {

	public $ALLOWED_MIME_TYPES = array(
    	'application/zip'   => 'zip',
    	'application/x-rar' => 'rar',
    	'application/x-tar' => 'tar',
    	'application/x-gzip'=> 'gz',
    );
	public $MAX_IMAGE_SIZE = 8196;
	
	function _init(){
		$this->ARCHIVE_FOLDER = PROJECT_PATH."uploads/tmp/";
//		$this->SAVE_PATH = PROJECT_PATH."uploads/tmp/";
		$this->SAVE_PATH = PROJECT_PATH."uploads/shop/products/";
		$this->SUPPLIER_ID = (int)db()->get_one('SELECT supplier_id FROM '.db('shop_admin_to_supplier').' WHERE admin_id='.intval(main()->ADMIN_ID));

	}

	/**
	*/
	function upload_images() {
		$SUPPLIER_ID = $this->SUPPLIER_ID;
		$ADMIN_INFO = db()->query_fetch('SELECT * FROM '.db('sys_admin').' WHERE id='.intval(main()->ADMIN_ID));
		$SUPPLIER_INFO = db()->query_fetch('SELECT * FROM '.db('shop_suppliers').' WHERE id='.intval($SUPPLIER_ID));
		if (empty($_FILES)) {
			if(!$SUPPLIER_ID){
				$suppliers = db()->get_2d('SELECT id, name FROM '.db('shop_suppliers'));
				$form = form('',array('enctype' => 'multipart/form-data'))
					->file("archive")
					->select_box('supplier', $suppliers, array('desc' => 'Supplier', 'show_text' => 1))
					->save('', "Upload");
			} else{
				$form = form('',array('enctype' => 'multipart/form-data'))
					->file("archive")
					->save('', "Upload");
			}
			return $form;
		}
		if($_POST['supplier']){
			 $SUPPLIER_ID = $_POST['supplier'];
		}
		$file = $_FILES['archive'];
		$new_name = md5(rand().microtime()).'.'.pathinfo($file['name'], PATHINFO_EXTENSION);
		rename($file['name'], $new_name);
		$uploaded = common()->upload_archive($this->ARCHIVE_FOLDER. $new_name);
		if(!$uploaded){
			return js_redirect('./?object='.$_GET['object'].'&action='.$_GET['action']);
		}
		$EXTRACT_PATH = $this->ARCHIVE_FOLDER.$SUPPLIER_ID.'_id_'.date("H_i_s");
		if (!file_exists($EXTRACT_PATH)) {
			mkdir($EXTRACT_PATH, 0777, true);
		}
		$full_archive_name = escapeshellarg($this->ARCHIVE_FOLDER.$new_name);

		$zip = 'unzip -o '.$full_archive_name.' -d '.$EXTRACT_PATH;
		$rar = 'unrar e '.$full_archive_name.' '.$EXTRACT_PATH;
		$tar = 'tar -xvf '.$full_archive_name.' -C '.$EXTRACT_PATH;
		$gz = 'tar -xzf '.$full_archive_name.' -C '.$EXTRACT_PATH;

		$ext = $this->ALLOWED_MIME_TYPES[$file['type']];
		passthru($$ext);
		$result_files = _class('dir')->scan_dir($EXTRACT_PATH, true, '-f /\.(jpg|jpeg|png)$/', '/__MACOSX/');
		foreach($result_files as $k => $v){
			$status = $this->search_product_by_filename($v, $SUPPLIER_ID);
			$items[] = array(
				"number"	=> $k,
				"filename"	=> str_replace($EXTRACT_PATH, '', $v),
				"status"	=> is_array($status)? $status['status'] : $status,
				"image"		=> is_array($status)? str_replace(PROJECT_PATH, WEB_PATH, $status['img']): "",
				"edit_url"	=> is_array($status)? "./?object=manage_shop&action=product_edit&id=".$status['id'] : "",
			);
			$replace =array(
				"items" => $items,
			);	
		}
		_class('dir')->delete_dir($EXTRACT_PATH, true);
		unlink($this->ARCHIVE_FOLDER.$new_name);
		common()->admin_wall_add(array('archive with images uploaded by '.$SUPPLIER_INFO['name'].' '.$ADMIN_INFO['first_name'].' '.$ADMIN_INFO['last_name']));

		return tpl()->parse("shop_supplier_panel/upload_archive", $replace);
	}

	/**
	*/
	function search_product_by_filename($folder, $supplier_id = false) {

		$filename = basename($folder);
		$ext = pathinfo($filename, PATHINFO_EXTENSION);

		$filename = ltrim($filename, ' .-_+=/\|,!@#%~&*()');
		if(!strpos($filename,'_')){
			return "Wrong filename";
		}
		$articul = explode('_', $filename);
		if(!empty($articul[0])){
			$articul = _es(strip_tags($articul[0]));
			$sql = 'SELECT id FROM '.db('shop_products').' 
					WHERE articul="'.$articul.'"
						AND supplier_id='.$supplier_id;
			$product = db()->query_fetch($sql);
		}else{
			return "Articul_not_found";
		}
		if(empty($product)){
			return "Product_not_found";
		}
		$md5 = md5_file($folder);
		$db_item = db()->query_fetch("SELECT id FROM ".db('shop_product_images')." WHERE product_id=".$product['id']." AND md5='".$md5."'");
		if(!empty($db_item)){
			return "Dublicate image";
		}
		$thumb_name = $this->resize_and_save_image($folder, $product['id'], $md5);
		return array(
			'status'=>"Success",
			'img'	=> $thumb_name,
			'id'	=> $product['id'],
		);
	}

	/**
	*/
	function resize_and_save_image($img, $id, $md5){
		$dirs = sprintf('%06s',$id);
		$dir2 = substr($dirs,-3,3);
		$dir1 = substr($dirs,-6,3);
		$ext = pathinfo($img, PATHINFO_EXTENSION);
		$new_path = $this->SAVE_PATH.$dir1.'/'.$dir2.'/';
		if (!file_exists($new_path)) {
			mkdir($new_path, 0777, true);
		}

		db()->insert(db('shop_product_images'), array(
			'product_id' 	=> $id,
			'md5'			=> $md5,
			'date_uploaded' => $_SERVER['REQUEST_TIME'],
		));
		$i = db()->insert_id();

		$real_name = $new_path.'product_'.$id.'_'.$i.'.jpg';
		$thumb_name = $new_path.'product_'.$id.'_'.$i.module('manage_shop')->THUMB_SUFFIX.'.jpg';
		$big_name = $new_path.'product_'.$id.'_'.$i.module('manage_shop')->FULL_IMG_SUFFIX.'.jpg';
		$watermark_name = PROJECT_PATH.SITE_WATERMARK_FILE;

		common()->make_thumb($img, $real_name, module("manage_shop")->BIG_X, module("manage_shop")->BIG_Y);
		common()->make_thumb($img, $thumb_name, module("manage_shop")->THUMB_X, module("manage_shop")->THUMB_Y, $watermark_name);
		common()->make_thumb($img, $big_name, module("manage_shop")->BIG_X, module("manage_shop")->BIG_Y, $watermark_name);

		$A = db()->query_fetch("SELECT COUNT(*) AS cnt FROM ".db('shop_product_images')." WHERE product_id=".$id." AND is_default=1");
		if ($A['cnt'] == 0) {
			$A = db()->query_fetch("SELECT id FROM ".db('shop_product_images')." WHERE product_id=".$id." ORDER BY id");
			db()->query("UPDATE ".db('shop_product_images')." SET is_default='1' WHERE id=".$A['id']);
		}			
		return $thumb_name;

	}
}
