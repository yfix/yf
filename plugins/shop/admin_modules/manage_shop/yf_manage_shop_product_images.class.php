<?php

class yf_manage_shop_product_images{

	/**
	 */
	function product_image_delete () {
		$_GET['id'] = intval($_GET['id']);
		$_GET['key'] = intval($_GET['key']);
		if ($_GET['id']) {
			$product = module('manage_shop')->_product_get_info($_GET['id']);
		}
		if (!$product['id']) {
			return _e('No such product!');
		}
		if (empty($_GET['key'])) {
			return _e('Empty image key!');
		}
		$A = db()->get_all('SELECT * FROM '.db('shop_product_images').'
							WHERE product_id='.intval($_GET['id']).'
								AND id='.intval($_GET['key']).'
								AND active=1');
		if (count($A) == 0){
			return _e('Image not found');
		}
		module('manage_shop')->_product_check_first_revision('product_images', $_GET['id']);
		module('manage_shop')->_product_image_delete($_GET['id'], $_GET['key']);
		module('manage_shop')->_product_images_add_revision('deleted', $_GET['id'], $_GET['key']);
		module('manage_shop')->_product_cache_purge($_GET['id']);
		common()->message_success("Image deleted");
		common()->admin_wall_add(array('shop product image deleted: '.$_GET['id'], $_GET['id']));
		return js_redirect($_SERVER['HTTP_REFERER']);
	}

	/**
	 */
	function _product_image_delete ($id, $k) {
		db()->query('UPDATE '.db('shop_product_images').' SET active=0, is_default=0 WHERE product_id='.$id.' AND id='.$k);
		$A = db()->get_all('SELECT * FROM '.db('shop_product_images').' WHERE product_id='.$id.' AND active=1');
		if (count($A) == 0) {
			db()->query('UPDATE '.db('shop_products').' SET image=0 WHERE id='.$id);
		}
		return true;
	}

	/**
	 */
	function set_main_image(){
		if ($_GET['id']) {
			$product = module('manage_shop')->_product_get_info($_GET['id']);
		}
		if (!$product['id']) {
			return _e('No such product!');
		}
		$product_id = intval($_GET['id']);
		if (main()->is_post()) {
			module('manage_shop')->_product_check_first_revision('product_images', $product_id);
			db()->query('UPDATE `'.db('shop_product_images').'` SET `is_default`=\'0\' WHERE `product_id`='.$product_id);
			db()->query('UPDATE `'.db('shop_product_images').'` SET `is_default`=\'1\' WHERE `id`='.$_POST['main_image']);

			module('manage_shop')->_product_images_add_revision('changed_main', $_GET['id']);
			module('manage_shop')->_product_cache_purge($_GET['id']);
			common()->message_success("Main image changed");
		} else {
			$images = common()->shop_get_images($product_id);
			if(!$images){
				return js_redirect($_SERVER['HTTP_REFERER']);
			}
			$base_url = WEB_PATH;
			$media_host = ( defined( 'MEDIA_HOST' ) ? MEDIA_HOST : false );
			if( !empty( $media_host ) ) { $base_url = '//' . $media_host . '/'; }
			foreach((array)$images as $A) {
				$items[] = array(
					'img_path' 		=> $base_url . $A['big'],
					'thumb_path'	=> $base_url . $A['thumb'],
					'image_key'		=> $A['id'],
				);
			}
			$form_action ='./?object='.$_GET['object'].'&action='.$_GET['action'].'&id='.$product_id;
			$replace = array(
				'form_action'=> $form_action,
				'items'		=> $items,
			);
			return tpl()->parse($_GET['object'].'/set_image_items', $replace);
		}
	}


	/**
	 */
	function product_image_upload () {
		$_GET['id'] = intval($_GET['id']);
		if ($_GET['id']) {
			$product = module('manage_shop')->_product_get_info($_GET['id']);
		}
		if (!$product['id']) {
			return _e('No such product!');
		}
		module('manage_shop')->_product_check_first_revision('product_images', $_GET['id']);
		module('manage_shop')->_product_image_upload($_GET['id']);
		module('manage_shop')->_product_cache_purge($_GET['id']);
		common()->message_success("New image uploaded");
		common()->admin_wall_add(array('shop product image uploaded: '.$_GET['id'], $_GET['id']));
		return js_redirect($_SERVER['HTTP_REFERER']);
	}

	/**
	 */
	function _product_image_upload ($product_id) {
		$products_images_dir = module('manage_shop')->products_img_dir;

		$d = sprintf('%09s', $product_id);
		$replace = array(
			'{subdir1}' => substr($d, 0, -6),
			'{subdir2}' => substr($d, -6, 3),
			'{subdir3}' => substr($d, -3, 3),
			'%d'        => $product_id,
		);
		$url = 'uploads/shop/products/{subdir2}/{subdir3}/product_%d_%i_%s.jpg';
		$clean_image_url = 'uploads/shop/products/{subdir2}/{subdir3}/product_%d_%i.jpg';

		$url = str_replace(array_keys($replace), array_values($replace), $url);
		$clean_image_url = str_replace(array_keys($replace), array_values($replace), $clean_image_url);

		foreach ((array)$_FILES['image']['tmp_name'] as $v) {
			if (!strlen($v)) {
				continue;
			}
			$md5 = md5_file($v);
			$db_item = db()->query_fetch('SELECT id FROM '.db('shop_product_images').' WHERE product_id='.$product_id.' AND md5="'._es($md5).'" AND active=1');
			if (!empty($db_item)) {
				continue;
			}
			db()->begin();
			db()->insert(db('shop_product_images'), array(
				'product_id' 	=> $product_id,
				'md5'			=> $md5,
				'date_uploaded' => $_SERVER['REQUEST_TIME'],
			));
			$i = db()->insert_id();

			$img_properties = getimagesize($v);
			if (empty($img_properties) || !$product_id) {
				return false;
			}
			$img_path       = PROJECT_PATH.str_replace('%i', $i, $clean_image_url);
			$img_path_big   = PROJECT_PATH.str_replace('%i', $i, str_replace('%s','big',$url));
			$img_path_thumb = PROJECT_PATH.str_replace('%i', $i, str_replace('%s','thumb',$url));
			$watermark_path = PROJECT_PATH.SITE_WATERMARK_FILE;

			common()->make_thumb( $v, $img_path,       module( 'manage_shop' )->BIG_X,   module( 'manage_shop' )->BIG_Y                  );
			common()->make_thumb( $v, $img_path_thumb, module( 'manage_shop' )->THUMB_X, module( 'manage_shop' )->THUMB_Y                );
			common()->make_thumb( $v, $img_path_big,   module( 'manage_shop' )->BIG_X,   module( 'manage_shop' )->BIG_Y, $watermark_path );

			$A = db()->query_fetch('SELECT COUNT(*) AS `cnt` FROM '.db('shop_product_images').' WHERE product_id='.intval($product_id).' AND is_default=1 AND active=1');
			if ($A['cnt'] == 0) {
				$A = db()->query_fetch('SELECT `id` FROM '.db('shop_product_images').' WHERE `product_id`='.intval($product_id).' ORDER BY `id` DESC');
				db()->query('UPDATE '.db('shop_product_images').' SET `is_default`=1 WHERE `id`='.$A['id']);
			}
			db()->query('UPDATE '.db('shop_products').' SET `image`=1 WHERE `id`='.$product_id);
			db()->commit();
			module('manage_shop')->_product_images_add_revision('uploaded', $product_id, $i);
		}
		return $i;
	}

	/**
	*/
	function product_image_search () {
		$_GET['id'] = intval($_GET['id']);
		if ($_GET['id']) {
			$product = module('manage_shop')->_product_get_info($_GET['id']);
		}
		if (!$product['id']) {
			return _e('No such product!');
		}

		$sql = 'SELECT * FROM '.db('shop_products').' WHERE id = '.$_GET['id'];
		$product_info = db()->query_fetch($sql);

		if (empty($product_info)) {
			return js_redirect($_SERVER['HTTP_REFERER'], true, 'wrong product ID');
		}
		if (!empty($_POST['src'])) {
			$tmp_file = '/tmp/search_image_'.$_GET['id'];
			if (!copy($_POST['src'], $tmp_file)) {
				_re("Error. Bad image.");
			} else {
				$_FILES['image']['tmp_name'][] = $tmp_file;
				if(!empty($_POST['w']) && !empty($_POST['h'])){
					common()->crop_image($tmp_file, $tmp_file, $_POST['w'], $_POST['h'], $_POST['x'], $_POST['y']);
				}
			}
		}
		// Image upload
		if (!empty($_FILES)) {
			$this->product_image_upload();
			//Delete temprary file
			if (!empty($tmp_file)) {
				@unlink($tmp_file);
			}
		}
		$images = common()->shop_get_images($product_info['id']);
		$base_url = WEB_PATH;
		$media_host = ( defined( 'MEDIA_HOST' ) ? MEDIA_HOST : false );
		if (!empty($media_host)) {
			$base_url = '//' . $media_host . '/';
		}
		foreach((array)$images as $A) {
			$product_image_delete_url = './?object='.main()->_get('object').'&action=product_image_delete&id='.$product_info['id'].'&key='.$A['id'];
			$replace2 = array(
				'img_path' 		=> $base_url . $A['big'],
				'thumb_path'	=> $base_url . $A['thumb'],
				'del_url' 		=> $product_image_delete_url,
				'image_key'		=> $A['id'],
			);
			$items .= tpl()->parse('manage_shop/image_items', $replace2);
		}
		$search_url = 'http://yandex.com/images/search?text='.urlencode($product_info['name']);
		$cache_key = 'external_images_'.$_GET['id'];
		$search_results = cache_get($cache_key);
		if (empty($search_results)) {
			$search_results = file_get_contents($search_url);
			preg_match_all('/<a class="serp-item__link".*?c.hit\((.*?)\)/umis', $search_results, $search_results);
			$search_results = $search_results[1];
			foreach ($search_results as $key => $item) {
				$item = json_decode('['.html_entity_decode($item).']', true);
				$search_results[$key] = $item[1]['href'];
			}
			cache_set($cache_key, $search_results);
		}
		$replace = array(
			'form_action'    => './?object=manage_shop&action=product_image_search&id='.$product_info['id'],
			'search_url'     => $search_url,
			'search_results' => json_encode($search_results),
			'product_info'   => $product_info,
			'image'          => $items,
			'product_url'    => './?object='.main()->_get('object').'&action=product_edit&id='.$product_info['id'],
		);
		return tpl()->parse($_GET['object'].'/product_image_search', $replace);
	}
}
