<?php

class yf_manage_shop_hook_side_column {

	/***/
	function _hook_side_column () {
		if ($_GET['action'] == 'product_edit') {
			return $this->_product_revisions() . $this->_product_images_revisions();
		} elseif ($_GET['action'] == 'product_revisions') {
			return $this->_product_revisions_similar();
		} elseif ($_GET['action'] == 'view_order') {
			return $this->_order_revisions();
		}
		return '';
	}

	/***/
	function _product_revisions () {
		$product_id = intval($_GET['id']);
		$product_info = module('manage_shop')->_product_get_info($product_id);
		if (!$product_info) {
			return false;
		}
		$sql = 'SELECT * FROM '.db('shop_product_revisions').' WHERE item_id='.intval($product_id).' ORDER BY add_date DESC';
		return table($sql, array(
				'caption' => t('Product revisions'),
				'no_records_html' => '',
			))
			->date('add_date', array('format' => 'full', 'nowrap' => 1))
			->admin('user_id', array('desc' => 'admin'))
			->text('action')
			->btn_view('', './?object=manage_shop&action=product_revisions&id=%d')
		;
	}

	/***/
	function _product_images_revisions () {
		$product_id = intval($_GET['id']);
		$product_info = module('manage_shop')->_product_get_info($product_id);
		if (!$product_info) {
			return false;
		}
		$sql = 'SELECT * FROM '.db('shop_product_images_revisions').' WHERE product_id='.intval($product_id).' ORDER BY add_date DESC';
		return table($sql, array(
				'caption' => t('Product images revisions'),
				'no_records_html' => '',
			//	'btn_no_text' => 1,
			//	'no_header' => 1
			))
			->date('add_date', array('format' => 'short', 'nowrap' => 1))
			->admin('user_id', array('desc' => 'admin'))
			->image('image_id', 'Image', array('width' => '30px', 'img_path_callback' => function($_p1, $_p2, $row) {
				$dirs = sprintf('%06s', $row['product_id']);
				$dir2 = substr($dirs, -3, 3);
				$dir1 = substr($dirs, -6, 3);
				$m_path = $dir1.'/'.$dir2.'/';
				$image = SITE_IMAGES_DIR.$m_path.'product_'.$row['product_id'].'_'.$row['image_id'].'.jpg';
				return $image; 
            }))
			->text('action')
			->btn_view('', './?object=manage_shop&action=product_images_revisions&id=%d')
//			->footer_link('All revisions history', './?object=manage_shop&action=product_images_revisions')
		;
	}

	/***/
	function _product_revisions_similar () {
		$rev = db()->get('SELECT * FROM '.db('shop_product_revisions').' WHERE id='.intval($_GET['id']));
		$product_id = intval($rev['item_id']);
		$product_info = module('manage_shop')->_product_get_info($product_id);
		if (!$product_info) {
			return false;
		}
		$sql = 'SELECT * FROM '.db('shop_product_revisions').' WHERE item_id='.intval($product_id).' ORDER BY add_date DESC';
		return table($sql, array(
				'caption' => t('Product revisions'),
				'no_records_html' => '',
				'tr' => array(
					$rev['id'] => array('class' => 'success'),
				),
			))
			->date('add_date', array('format' => 'full', 'nowrap' => 1))
			->admin('user_id', array('desc' => 'admin'))
			->text('action')
			->btn_view('', './?object=manage_shop&action=product_revisions&id=%d')
		;
	}

	/***/
	function _order_revisions () {
// TODO
	}
}