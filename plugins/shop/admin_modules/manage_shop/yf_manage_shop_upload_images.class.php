<?php

class yf_manage_shop_upload_images {

        public $ALLOWED_MIME_TYPES = array(
            'application/zip' => 'zip',
            'application/x-rar' => 'rar',
            'application/x-tar' => 'tar',
            'application/x-gzip'=> 'gz',
    );
        public $MAX_IMAGE_SIZE = 8196;
        
        function _init(){
                $this->ARCHIVE_FOLDER = PROJECT_PATH."uploads/tmp/";
//                $this->SAVE_PATH = PROJECT_PATH."uploads/tmp/";
                $this->SAVE_PATH = PROJECT_PATH."uploads/shop/products/";
                $this->SUPPLIER_ID = (int)db()->get_one('SELECT supplier_id FROM '.db('shop_admin_to_supplier').' WHERE admin_id='.intval(main()->ADMIN_ID));
                set_time_limit(0);
        }

        /**
        */
        function upload_images() {
                $SUPPLIER_ID = $this->SUPPLIER_ID;
                $ADMIN_INFO = db()->query_fetch('SELECT * FROM '.db('sys_admin').' WHERE id='.intval(main()->ADMIN_ID));
                $SUPPLIER_INFO = db()->query_fetch('SELECT * FROM '.db('shop_suppliers').' WHERE id='.intval($SUPPLIER_ID));
                if (empty($_FILES) && empty($_POST['server_path'])) {
                        if(!$SUPPLIER_ID){
                                $suppliers = db()->get_2d('SELECT id, name FROM '.db('shop_suppliers'));
                                $form = form('',array('enctype' => 'multipart/form-data'))
                                        ->file("archive")
                                        ->select_box('supplier', $suppliers, array('desc' => 'Supplier', 'show_text' => 1))
                                        ->text("server_path","from server path")
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
                if($_POST['server_path']){
                        $server_path = strip_tags(ltrim($_POST['server_path'], ' .-_+=|,!@#%~&*();:\'"'));
                        if(file_exists($server_path) && is_readable($server_path)){
                                $archive_name = $server_path;
                                $file_type = mime_content_type($archive_name);
                                $uploaded = true;
                        }
                }else{
                        $file = $_FILES['archive'];
                        file_put_contents($this->ARCHIVE_FOLDER.date("d-m-Y").".log", "\n[".$file['name']."]\n", FILE_APPEND);
                        $new_name = md5(rand().microtime()).'.'.pathinfo($file['name'], PATHINFO_EXTENSION);
                        rename($file['name'], $new_name);
                        $archive_name = $this->ARCHIVE_FOLDER. $new_name;
                        $file_type = $file['type'];
                        $uploaded = common()->upload_archive($archive_name);
                }
                if(!$uploaded){
                        return js_redirect('./?object='.$_GET['object'].'&action='.$_GET['action']);
                }
                $EXTRACT_PATH = $this->ARCHIVE_FOLDER.$SUPPLIER_ID.'_id_'.date("H_i_s");
                if (!file_exists($EXTRACT_PATH)) {
                        mkdir($EXTRACT_PATH, 0777, true);
                }
                $full_archive_name = escapeshellarg($archive_name);

//                $zip = 'unzip -o '.$full_archive_name.' -d '.$EXTRACT_PATH;
//                $rar = 'unrar e '.$full_archive_name.' '.$EXTRACT_PATH;
                $tar = 'tar -xvf '.$full_archive_name.' -C '.$EXTRACT_PATH;
                $gz = 'tar -xzf '.$full_archive_name.' -C '.$EXTRACT_PATH;

                $ext = $this->ALLOWED_MIME_TYPES[$file_type];
                if($ext == 'rar') common()->rar_extract($archive_name, $EXTRACT_PATH);
                if($ext == 'zip') common()->zip_extract($archive_name, $EXTRACT_PATH);
                if($ext == 'tar' || $ext == 'gz') exec($$ext);

                $result_files = _class('dir')->scan_dir($EXTRACT_PATH, true, '-f /\.(jpg|jpeg|png|gif|bmp)$/', '/__MACOSX/');
                foreach($result_files as $k => $v){
                        $status = $this->search_product_by_filename($v, $SUPPLIER_ID);
                        $result = is_array($status)? $status['status'] : $status;
                        $filename = str_replace($EXTRACT_PATH, '', $v);
                        $product_id = is_array($status)? $status['id'] : "???";
                        $items[] = array(
                                "number"        => $k,
                                "filename"        => $filename,
                                "status"        => $result,
                                "image"                => is_array($status)? str_replace(PROJECT_PATH, WEB_PATH, $status['img']): "",
                                "edit_url"        => is_array($status)? "./?object=manage_shop&action=product_edit&id=".$status['id'] : "",
                        );
                        $log_str = $product_id." | ".$result." | ".$filename.";\n";
                        file_put_contents($this->ARCHIVE_FOLDER.date("d-m-Y").".log", $log_str, FILE_APPEND);
                }
                $replace =array(
                        "items" => $items,
                );        
                _class('dir')->delete_dir($EXTRACT_PATH, true);
                unlink($this->ARCHIVE_FOLDER.$new_name);
                common()->admin_wall_add(array('archive with images uploaded by '.$SUPPLIER_INFO['name'].' '.$ADMIN_INFO['first_name'].' '.$ADMIN_INFO['last_name']));

                return tpl()->parse("manage_shop/upload_archive", $replace);
        }

        /**
        */
        function search_product_by_filename($folder, $supplier_id = false) {

                $filename = basename($folder);
                $ext = pathinfo($filename, PATHINFO_EXTENSION);
                $filename = ltrim($filename, ' .-_+=/\|,!@#%~&*()');
                if(strpos($filename,'_')){
                        $articul = explode('_', $filename);
                }elseif(strpos($filename,'.')){
                        $articul = explode('.', $filename);
                }else{
                        return "Wrong filename";
                }
                if(!empty($articul[0])){
                        $articul = _es(strip_tags($articul[0]));
                        $sql = 'SELECT id FROM '.db('shop_products').'
                                WHERE articul IN ("'.$articul.'","'.pathinfo($filename, PATHINFO_FILENAME).'") 
                                    AND supplier_id='.$supplier_id;
/*
                        $sql = 'SELECT id FROM '.db('shop_products').'
                                        WHERE id= '.$articul;
*/
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
				module('manage_shop')->_product_check_first_revision('product_images', $product['id']);
                $thumb_name = $this->resize_and_save_image($folder, $product['id'], $md5);
                return array(
                        'status'=>"Success",
                        'img'        => $thumb_name,
                        'id'        => $product['id'],
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
                db()->begin();
                db()->insert(db('shop_product_images'), array(
                        'product_id'    => $id,
                        'md5'           => $md5,
                        'date_uploaded' => $_SERVER['REQUEST_TIME'],
                ));
                $i = db()->insert_id();

                $real_name = $new_path.'product_'.$id.'_'.$i.'.jpg';
                $thumb_name = $new_path.'product_'.$id.'_'.$i.module('manage_shop')->THUMB_SUFFIX.'.jpg';
                $big_name = $new_path.'product_'.$id.'_'.$i.module('manage_shop')->FULL_IMG_SUFFIX.'.jpg';
                $watermark_name = PROJECT_PATH.SITE_WATERMARK_FILE;

                common()->make_thumb($img, $real_name, module("manage_shop")->BIG_X, module("manage_shop")->BIG_Y);
                common()->make_thumb($img, $thumb_name, module("manage_shop")->THUMB_X, module("manage_shop")->THUMB_Y);
                common()->make_thumb($img, $big_name, module("manage_shop")->BIG_X, module("manage_shop")->BIG_Y, $watermark_name);

                $A = db()->query_fetch("SELECT COUNT(*) AS cnt FROM ".db('shop_product_images')." WHERE product_id=".$id." AND is_default=1 AND active=1");
                if ($A['cnt'] == 0) {
                        $A = db()->query_fetch("SELECT id FROM ".db('shop_product_images')." WHERE product_id=".$id." ORDER BY id DESC");
                        db()->query("UPDATE ".db('shop_product_images')." SET is_default=1 WHERE id=".$A['id']);
                }                        
                db()->query("UPDATE `".db('shop_products')."` SET `image`='1' WHERE `id`=".$id);
                db()->commit();
                module('manage_shop')->_product_images_add_revision('import', $id, $i);

                return $thumb_name;
        }

}