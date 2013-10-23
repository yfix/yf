<?php

class yf_shop_supplier_panel_upload_images {

	public $ALLOWED_MIME_TYPES = array(
    	'application/zip'   => 'zip',
    	'application/rar'   => 'rar',
    	'application/x-tar' => 'tar',
    	'application/x-gzip'=> 'gz',
    );
	public $MAX_IMAGE_SIZE = 8196;
	
	function _init(){
		$this->ARCHIVE_FOLDER = PROJECT_PATH."uploads/tmp/";
	}

	/**
	*/
	function upload_images() {
		$SUPPLIER_ID = module('shop_supplier_panel')->SUPPLIER_ID;
		if (empty($_FILES)) {
			return form('',array('enctype' => 'multipart/form-data'))
				->file("archive")
				->save('', "Upload");
		}
		$file = $_FILES['archive'];
		$uploaded = common()->upload_archive($this->ARCHIVE_FOLDER. $file['name']);
		if(!$uploaded){
			return js_redirect('./?object='.$_GET['object'].'&action='.$_GET['action']);
		}
		$EXTRACT_PATH = $this->ARCHIVE_FOLDER.$SUPPLIER_ID.'_id_'.date("H_i_s");
		if (!file_exists($EXTRACT_PATH)) {
			mkdir($EXTRACT_PATH, 0777, true);
		}
		$full_archive_name = escapeshellarg($this->ARCHIVE_FOLDER.$file['name']);

		$zip = 'unzip -o '.$full_archive_name.' -d '.$EXTRACT_PATH;
		$rar = 'unrar '.$full_archive_name.' '.$EXTRACT_PATH;
		$tar = 'tar -xvf '.$full_archive_name.' -C '.$EXTRACT_PATH;
		$gz = 'tar -xzf '.$full_archive_name.' -C '.$EXTRACT_PATH;

		$ext = $this->ALLOWED_MIME_TYPES[$file['type']];
		exec($$ext, $result);

		$result_files = _class('dir')->scan_dir($EXTRACT_PATH, true, '-f /\.(jpg|jpeg|png)$/');
		foreach($result_files as $k => $v){
			$status = $this->search_product_by_filename($v);
			$items[] = array(
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
		unlink($this->ARCHIVE_FOLDER.$file['name']);
		return tpl()->parse("shop_supplier_panel/upload_archive", $replace);
	}

	/**
	*/
	function search_product_by_filename($folder) {
		$supplier_id = module('shop_supplier_panel')->SUPPLIER_ID;
		$filename = basename($folder);
		$ext = pathinfo($filename, PATHINFO_EXTENSION);
		preg_match('/[A-Za-z0-9._]*/i', $filename, $result);
		if(strlen($result[0]) !== strlen($filename)){
			return "Wrong_filename";
		}
		preg_match('/^[0-9]*/i', $filename, $articul);
		if(!empty($articul[0])){
			$sql = 'SELECT id FROM '.db('shop_products').' 
					WHERE articul='.intval($articul[0]).'
						AND supplier_id='.$supplier_id;
			$product = db()->query_fetch($sql);
		}else{
			return "Articul_not_found";
		}
		if(!empty($product)){
			$thumb_name = $this->resize_and_save_image($folder, $product['id']);
		}else{
			return "Product_not_found";
		}
		return array(
			'status'=>"Success",
			'img'	=> $thumb_name,
			'id'	=> $product['id'],
		);
	}

	/**
	*/
	function resize_and_save_image($img, $id){
		$dirs = sprintf('%06s',$id);
		$dir2 = substr($dirs,-3,3);
		$dir1 = substr($dirs,-6,3);
		$ext = pathinfo($img, PATHINFO_EXTENSION);
		$new_path = $this->ARCHIVE_FOLDER.$dir1.'/'.$dir2.'/';
		if (!file_exists($new_path)) {
			mkdir($new_path, 0777, true);
			$num =1;
		}else{
			$num = _class('dir')->scan_dir($new_path, true, '-f /\.(jpg|jpeg|png)$/');
			$num = count($num);
		}
		$thumb_name = $new_path.'product_'.$id.'_'.$num.module('manage_shop')->THUMB_SUFFIX.'.jpg';
		$big_name = $new_path.'product_'.$id.'_'.$num.module('manage_shop')->FULL_IMG_SUFFIX.'.jpg';
		common()->make_thumb($img, $thumb_name, 216, 216);
		common()->make_thumb($img, $big_name, 710, 750);

		return $thumb_name;
	}
}
