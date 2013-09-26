<?php
class yf_manage_shop_suppliers{

	/**
	*/
	function suppliers () {
		return table('SELECT * FROM '.db('shop_suppliers'))
			->image('id', 'uploads/shop/suppliers/%d.jpg', array('width' => '50px'))
			->text('name')
			->text('url')
			->text('meta_keywords')
			->text('meta_desc')
			->btn_edit('', './?object=manage_shop&action=supplier_edit&id=%d')
			->btn_delete('', './?object=manage_shop&action=supplier_delete&id=%d')
			->footer_add('', './?object=manage_shop&action=supplier_add')
		;
	}	

	/**
	*/
	function supplier_add () {
		if (!empty($_POST)) {
			if (!$_POST['name']) {
				_re('Product name must be filled');
			}
			if (!common()->_error_exists()) {
				$sql_array = array(
					'name'			=> $_POST['name'],
					'url'			=> common()->_propose_url_from_name($_POST['name']),
					'desc'			=> $_POST['desc'],
					'sort_order'	=> intval($_POST['featured']),
				);
				db()->insert(db('shop_suppliers'), db()->es($sql_array));
				common()->admin_wall_add(array('shop supplier added: '.$_POST['name'], db()->insert_id()));
				if (!empty($_FILES)) {
					$man_id = $_GET['id'];
					module('manage_shop')->_upload_image ($man_id, $url);
				} 
			}
			return js_redirect('./?object=manage_shop&action=suppliers');
		}

		$thumb_path = module('manage_shop')->supplier_img_dir.$supplier_info['url'].'_'.$supplier_info['id'].module('manage_shop')->THUMB_SUFFIX. '.jpg';
		if (!file_exists($thumb_path)) {
			$thumb_path = '';
		} else {
			$thumb_path = module('manage_shop')->supplier_img_webdir.$supplier_info['url'].'_'.$supplier_info['id'].module('manage_shop')->THUMB_SUFFIX. '.jpg';
		}
		$replace = array(
			'name'				=> '',
			'sort_order'		=> '',
			'desc'				=> '',
			'thumb_path'		=> '',
			'delete_image_url'	=> './?object=manage_shop&action=delete_image&id='.$supplier_info['id'],
			'form_action'		=> './?object=manage_shop&action=supplier_add',
			'back_url'			=> './?object=manage_shop&action=suppliers',
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
	function supplier_edit () {
		$_GET['id'] = intval($_GET['id']);
		if (empty($_GET['id'])) {
			return 'Empty ID!';
		}
		$supplier_info = db()->query_fetch('SELECT * FROM '.db('shop_suppliers').' WHERE id='.$_GET['id']);
		if (!empty($_POST)) {
			if (!$_POST['name']) {
				_re('Product name must be filled');
			}
			if (!common()->_error_exists()) {
				$sql_array = array(
					'name'		=> $_POST['name'],
					'url'		=> common()->_propose_url_from_name($_POST['name']),
					'desc'		=> $_POST['desc'],
					'sort_order'=> intval($_POST['featured']),
				);
				db()->update('shop_suppliers', db()->es($sql_array), 'id='.$_GET['id']);
				common()->admin_wall_add(array('shop supplier updated: '.$_POST['name'], $_GET['id']));
				if (!empty($_FILES)) {
					$man_id = $_GET['id'];
					$this->_upload_image($man_id, $url);
				} 
			}
			return js_redirect('./?object=manage_shop&action=suppliers');
		}
		$thumb_path = module('manage_shop')->supplier_img_dir.$supplier_info['url'].'_'.$supplier_info['id'].module('manage_shop')->THUMB_SUFFIX. '.jpg';
		if (!file_exists($thumb_path)) {
			$thumb_path = '';
		} else {
			$thumb_path = module('manage_shop')->supplier_img_webdir.$supplier_info['url'].'_'.$supplier_info['id'].module('manage_shop')->THUMB_SUFFIX. '.jpg';
		}
		$replace = array(
			'name'				=> $supplier_info['name'],
			'sort_order'		=> $supplier_info['sort_order'],
			'desc'				=> $supplier_info['desc'],
			'thumb_path'		=> $thumb_path,
			'delete_image_url'	=> './?object=manage_shop&action=delete_image&id='.$supplier_info['id'],
			'form_action'		=> './?object=manage_shop&action=supplier_edit&id='.$supplier_info['id'],
			'back_url'			=> './?object=manage_shop&action=suppliers',
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
	function supplier_delete () {
		$_GET['id'] = intval($_GET['id']);
		if (!empty($_GET['id'])) {
			$info = db()->query_fetch('SELECT * FROM '.db('shop_suppliers').' WHERE id='.intval($_GET['id']));
		}
		if (!empty($info['id'])) {
			db()->query('DELETE FROM '.db('shop_suppliers').' WHERE id='.intval($_GET['id']).' LIMIT 1');
			common()->admin_wall_add(array('shop supplier deleted: '.$info['name'], $_GET['id']));
		}
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo $_GET['id'];
		} else {
			return js_redirect('./?object=manage_shop&action=suppliers');
		}
	}	
}