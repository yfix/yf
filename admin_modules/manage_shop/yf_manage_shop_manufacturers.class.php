<?php

class manage_shop_manufacturers{

	function _purge_caches() {
		_class( '_shop_manufacturers', 'modules/shop/' )->_refresh_cache();
	}

	/**
	*/
	function manufacturers () {
		return table('SELECT * FROM '.db('shop_manufacturers'), array(
				'custom_fields' => array('num_products' => 'SELECT manufacturer_id, COUNT(*) AS num FROM '.db('shop_products').' GROUP BY manufacturer_id'),
				'filter' => $_SESSION[$_GET['object'].'__manufacturers'],
				'hide_empty' => 1,
			))
			->image('id', 'uploads/shop/manufacturers/%d.jpg', array('width' => '50px'))
			->text('name')
			->text('url')
			->text('num_products')
			->text('meta_keywords')
			->text('meta_desc')
			->btn_edit('', './?object='.main()->_get('object').'&action=manufacturer_edit&id=%d',array('no_ajax' => 1))
			->btn_delete('', './?object='.main()->_get('object').'&action=manufacturer_delete&id=%d')
			->footer_add('', './?object='.main()->_get('object').'&action=manufacturer_add')
		;
	}

	/**
	*/
	function manufacturer_add () {
		if (main()->is_post()) {
			if (!$_POST['name']) {
				_re('Product name must be filled');
			}
			if (!common()->_error_exists()) {
				$sql_array = array(
					'name'          => $_POST['name'],
					'url'           => common()->_propose_url_from_name($_POST['name']),
					'desc'          => $_POST['desc'],
					'meta_keywords' => $_POST['meta_keywords'],
					'meta_desc'     => $_POST['meta_desc'],
					'sort_order'    => intval($_POST['sort_order']),
				);
				db()->insert(db('shop_manufacturers'), db()->es($sql_array));
				common()->admin_wall_add(array('shop manufacturer added: '.$_POST['name'], db()->insert_id()));
				if (!empty($_FILES)) {
					$man_id = $_GET['id'];
					module('manage_shop')->_upload_image ($man_id, $url);
				}
				$this->_purge_caches();
			}
			return js_redirect('./?object='.main()->_get('object').'&action=manufacturers');
		}

		$thumb_path = module('manage_shop')->manufacturer_img_dir.$manufacturer_info['url'].'_'.$manufacturer_info['id'].module('manage_shop')->THUMB_SUFFIX. '.jpg';
		if (!file_exists($thumb_path)) {
			$thumb_path = '';
		} else {
			$thumb_path = module('manage_shop')->manufacturer_img_webdir.$manufacturer_info['url'].'_'.$manufacturer_info['id'].module('manage_shop')->THUMB_SUFFIX. '.jpg';
		}
		$replace = array(
			'name'				=> '',
			'sort_order'		=> '',
			'desc'				=> '',
			'thumb_path'		=> '',
			'delete_image_url'	=> './?object='.main()->_get('object').'&action=delete_image&id='.$manufacturer_info['id'],
			'form_action'		=> './?object='.main()->_get('object').'&action=manufacturer_add',
			'back_url'			=> './?object='.main()->_get('object').'&action=manufacturers',
		);
		return form($replace)
			->text('name')
			->textarea('desc','Description')
			->text('url')
			->text('meta_keywords')
			->text('meta_desc')
			->integer('sort_order')
			->save_and_back();
	}

	/**
	*/
	function manufacturer_edit () {
		$_GET['id'] = intval($_GET['id']);
		if (empty($_GET['id'])) {
			return _e('Empty ID!');
		}
		$manufacturer_info = db()->query_fetch('SELECT * FROM '.db('shop_manufacturers').' WHERE id='.$_GET['id']);
		if (main()->is_post()) {
			if (!$_POST['name']) {
				_re('Product name must be filled');
			}
			if (!common()->_error_exists()) {
				$sql_array = array(
					'name'          => $_POST['name'],
					'url'           => $_POST['url'],
					'desc'          => $_POST['desc'],
					'meta_keywords' => $_POST['meta_keywords'],
					'meta_desc'     => $_POST['meta_desc'],
					'sort_order'    => intval($_POST['sort_order']),
				);
//				module('manage_revisions')->check_revision(__FUNCTION__, $_GET['id'], 'shop_manufacturers');
				db()->update('shop_manufacturers', db()->es($sql_array), 'id='.$_GET['id']);
				module('manage_revisions')->new_revision(__FUNCTION__, $_GET['id'], 'shop_manufacturers');
				common()->admin_wall_add(array('shop manufacturer updated: '.$_POST['name'], $_GET['id']));
				if (!empty($_FILES)) {
					$man_id = $_GET['id'];
					$this->_upload_image($man_id, $url);
				}
				$this->_purge_caches();
			}
			return js_redirect('./?object='.main()->_get('object').'&action=manufacturers');
		}
		$thumb_path = module('manage_shop')->manufacturer_img_dir.$manufacturer_info['url'].'_'.$manufacturer_info['id'].module('manage_shop')->THUMB_SUFFIX. '.jpg';
		if (!file_exists($thumb_path)) {
			$thumb_path = '';
		} else {
			$thumb_path = module('manage_shop')->manufacturer_img_webdir.$manufacturer_info['url'].'_'.$manufacturer_info['id'].module('manage_shop')->THUMB_SUFFIX. '.jpg';
		}
		$replace = array(
			'name'             => $manufacturer_info['name'],
			'desc'             => $manufacturer_info['desc'],
			'url'              => $manufacturer_info['url'],
			'meta_keywords'    => $manufacturer_info['meta_keywords'],
			'meta_desc'        => $manufacturer_info['meta_desc'],
			'sort_order'       => $manufacturer_info['sort_order'],
			'thumb_path'       => $thumb_path,
			'delete_image_url' => './?object='.main()->_get('object').'&action=delete_image&id='.$manufacturer_info['id'],
			'form_action'      => './?object='.main()->_get('object').'&action=manufacturer_edit&id='.$manufacturer_info['id'],
			'back_url'         => './?object='.main()->_get('object').'&action=manufacturers',
		);
		return form($replace)
			->text('name')
			->textarea('desc','Description')
			->text('url')
			->text('meta_keywords')
			->text('meta_desc')
	//		->integer('sort_order')
			->save_and_back();
	}

	/**
	*/
	function manufacturer_delete () {
		$_GET['id'] = intval($_GET['id']);
		if (!empty($_GET['id'])) {
			$info = db()->query_fetch('SELECT * FROM '.db('shop_manufacturers').' WHERE id='.intval($_GET['id']));
		}
		if (!empty($info['id'])) {
			db()->query('DELETE FROM '.db('shop_manufacturers').' WHERE id='.intval($_GET['id']).' LIMIT 1');
			common()->admin_wall_add(array('shop manufacturer deleted: '.$_GET['id'], $_GET['id']));
			$this->_purge_caches();
		}
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo $_GET['id'];
		} else {
			return js_redirect('./?object='.main()->_get('object').'&action=manufacturers');
		}
	}

	/**
	*/
	function upload_image () {
		$_GET['id'] = intval($_GET['id']);
		if (empty($_GET['id'])) {
			return _e('Empty ID!');
		}
		$this->_upload_image($_GET['id']);
		return js_redirect($_SERVER['HTTP_REFERER']);
	}

	/**
	*/
	function _upload_image ($man_id, $url) {
		$img_properties = getimagesize($_FILES['image']['tmp_name']);
		if (empty($img_properties) || !$man_id) {
			return false;
		}
		$img_path = module('manage_shop')->manufacturer_img_dir.$url.'_'.$man_id.module('manage_shop')->FULL_IMG_SUFFIX. '.jpg';
		$thumb_path = module('manage_shop')->manufacturer_img_dir.$url.'_'.$man_id.module('manage_shop')->THUMB_SUFFIX. '.jpg';
		$upload_result = common()->upload_image($img_path);
		if ($upload_result) {
			$resize_result = common()->make_thumb($img_path, $thumb_path, module('manage_shop')->THUMB_X, module('manage_shop')->THUMB_Y);
		}
		return true;
	}

}
