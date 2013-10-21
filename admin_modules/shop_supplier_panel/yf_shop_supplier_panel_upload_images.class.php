<?php

class yf_shop_supplier_panel_upload_images {

	public $ALLOWED_MIME_TYPES = array(
    	'application/zip'   => 'zip',
    	'application/rar'   => 'rar',
    	'application/tar'   => 'tar',
    );
	public $ALLOWED_IMAGE_TYPES = array(
    	'image/jpg'   => 'jpg',
    	'image/png'   => 'png',
    );
	public $MAX_IMAGE_SIZE = 8196;
	public $ARCHIVE_FOLDER = "/tmp/php_archive/";
/*
exec('unzip '.escapeshellargs('/tmp/uploaded_archive.zip').' /tmp/temp_sub_dir/')
exec('unrar '.escapeshellargs('/tmp/uploaded_archive.rar').' /tmp/temp_sub_dir/')
exec('tar -xvf '.escapeshellargs('/tmp/uploaded_archive.tar').' /tmp/temp_sub_dir/')
exec('tar -xvzf '.escapeshellargs('/tmp/uploaded_archive.tar.gz').' /tmp/temp_sub_dir/')
*/
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
        common()->upload_archive($this->ARCHIVE_FOLDER. $file['name']);
		//unzip

		$UNARCHIVE_PATH = $this->ARCHIVE_FOLDER.$SUPPLIER_ID.'_id_'.date("H_i_s");

		$zip = 'unzip -o '.escapeshellarg($this->ARCHIVE_FOLDER.$file['name']).' -d '.$UNARCHIVE_PATH;
		$rar = 'unrar '.escapeshellarg($this->ARCHIVE_FOLDER.$file['name']).' '.$UNARCHIVE_PATH;

		$ext = $this->ALLOWED_MIME_TYPES[$file['type']];
		exec($$ext, $result);

		$result_files = _class('dir')->scan_dir($UNARCHIVE_PATH, true, '-f /\.(jpg|jpeg|png)$/');
		foreach($result_files as $k => $v){
			$status[$k] = $this->search_product_by_filename($v);
		}
		return 'no rows to process';
	}

	/**
	*/
	function search_product_by_filename($folder) {
		print_r($folder);
	}
}
