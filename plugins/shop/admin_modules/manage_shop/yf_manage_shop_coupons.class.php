<?php
class yf_manage_shop_coupons {

	/**
	*/
	function _init() {
	}

	/**
	*/
	function coupons() {
		return table('SELECT * FROM '.db('shop_coupons'), array(
//				'filter' => $_SESSION[$_GET['object'].'__coupons'],
			))
			->text('code')
            ->user('user_id')
            ->text('total_sum', array('nowrap' => 1))                
            ->date('time_start', array('format' => 'full', 'nowrap' => 1))
            ->date('time_end', array('format' => 'full', 'nowrap' => 1))
            ->link('cat_id', './?object=category_editor&action=edit_item&id=%d', _class('cats')->_get_items_names_cached('shop_cats'))
            ->link('order_id', './?object=manage_shop&action=view_order&id=%d')
            ->text('status')
			->btn_edit('', './?object='.main()->_get('object').'&action=coupon_edit&id=%d',array('no_ajax' => 1))
			->btn_view('', './?object='.main()->_get('object').'&action=coupon_view&id=%d',array('no_ajax' => 1))
			->btn_delete('', './?object='.main()->_get('object').'&action=coupon_delete&id=%d')
			->footer_add('', './?object='.main()->_get('object').'&action=coupon_add',array('no_ajax' => 1)) 
		;
	}

    
	/**
	*/
	function coupon_delete () {
		$_GET['id'] = intval($_GET['id']);
		if (!empty($_GET['id'])) {
			$info = db()->query_fetch('SELECT * FROM '.db('shop_coupons').' WHERE id='.intval($_GET['id']));
		}
		if (!empty($info['id'])) {
			db()->query('DELETE FROM '.db('shop_coupons').' WHERE id='.intval($_GET['id']).' LIMIT 1');
			common()->admin_wall_add(array('coupon deleted: '.$_GET['id'], $_GET['id']));
		}
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo $_GET['id'];
		} else {
			return js_redirect('./?object='.main()->_get('object').'&action=coupons');
		}
	}
    
	/**
	*/
	function coupon_add () {
		if (main()->is_post()) {
			if (!$_POST['code']) {
				_re('Code must be entered');                
			} else {
                $_POST['code'] = $this->_cleanup_code($_POST['code']);
                $cnt = db()->get_one("SELECT COUNT(`id`) AS `cnt` FROM `".db('shop_coupons')."` WHERE `code`='".$_POST['code']."'");
                if ($cnt != 0) {
    				_re('Code already exists');
                }
            }
			if (!common()->_error_exists()) {
				$sql_array = array(
					'code'          => $this->_cleanup_code($_POST['code']),
					'user_id'       => intval($_POST['user_id']),
					'sum'           => intval($_POST['sum']),
					'status'        => intval($_POST['status']),
					'cat_id'        => intval($_POST['cat_id']),
					'order_id'      => intval($_POST['order_id']),
                    'time_start'    => strtotime($_POST['time_start']),
                    'time_end'      => strtotime($_POST['time_end']),
				);
				db()->insert(db('shop_coupons'), db()->es($sql_array));
				common()->admin_wall_add(array('shop coupon added: '.$this->_cleanup_code($_POST['code']), db()->insert_id()));
    			return js_redirect('./?object='.main()->_get('object').'&action=coupons');            
			}
		}

		$replace = array(
			'form_action'		=> './?object='.main()->_get('object').'&action=coupon_add',
			'back_url'			=> './?object='.main()->_get('object').'&action=coupons',
		);
		return form($replace)
			->text('code')
            ->integer('user_id')
            ->integer('sum')                
            ->integer('status')
            ->integer('cat_id')                
            ->integer('order_id')
            ->datetime_select('time_start',      null, array( 'with_time' => 1 ) )
            ->datetime_select('time_end',        null, array( 'with_time' => 1 ) )
			->save_and_back();
	}

	/**
	*/
	function coupon_edit () {
		$_GET['id'] = intval($_GET['id']);
		if (empty($_GET['id'])) {
			return _e('Empty ID!');
		}
		$coupon_info = db()->query_fetch('SELECT * FROM '.db('shop_coupons').' WHERE id='.$_GET['id']);
		if (main()->is_post()) {
			if (!$_POST['code']) {
				_re('Code must be entered');                
			} else {
                $_POST['code'] = $this->_cleanup_code($_POST['code']);
                $cnt = db()->get_one("SELECT COUNT(`id`) AS `cnt` FROM `".db('shop_coupons')."` WHERE `code`='".$_POST['code']."' AND `id`!=".$_GET['id']);
                if ($cnt != 0) {
    				_re('Code already exists');
                }
            }
			if (!common()->_error_exists()) {
				$sql_array = array(
					'code'          => $this->_cleanup_code($_POST['code']),
					'user_id'       => intval($_POST['user_id']),
					'sum'           => intval($_POST['sum']),
					'status'        => intval($_POST['status']),
					'cat_id'        => intval($_POST['cat_id']),
					'order_id'      => intval($_POST['order_id']),
                    'time_start'    => strtotime($_POST['time_start']),
                    'time_end'      => strtotime($_POST['time_end']),
				);
				db()->update('shop_coupons', db()->es($sql_array), 'id='.$_GET['id']); 
				common()->admin_wall_add(array('shop coupon updated: '.$this->_cleanup_code($_POST['code']), $_GET['id']));
    			return js_redirect('./?object='.main()->_get('object').'&action=coupons');            
			}
		}
		$replace = array(
			'code'             => $coupon_info['code'],
			'user_id'          => $coupon_info['user_id'],
			'sum'              => $coupon_info['sum'],
			'status'           => $coupon_info['status'],
			'cat_id'           => $coupon_info['cat_id'],
			'order_id'         => $coupon_info['order_id'],
			'time_start'       => date('d.m.Y I:s',$coupon_info['time_start']),
			'time_end'         => date('d.m.Y I:s',$coupon_info['time_end']),
			'form_action'      => './?object='.main()->_get('object').'&action=coupon_edit&id='.$coupon_info['id'],
			'back_url'         => './?object='.main()->_get('object').'&action=coupons',
		);
		return form($replace)
			->text('code')
            ->integer('user_id')
            ->integer('sum')                
            ->integer('status')
            ->integer('cat_id')                
            ->integer('order_id')
            ->datetime_select('time_start',      null, array( 'with_time' => 1 ) )
            ->datetime_select('time_end',        null, array( 'with_time' => 1 ) )
			->save_and_back();
	}
 
    
	/**
	*/
	function coupon_view () {
		$_GET['id'] = intval($_GET['id']);
		if (!empty($_GET['id'])) $info = db()->query_fetch('SELECT * FROM '.db('shop_coupons').' WHERE id='.intval($_GET['id']));
		if (empty($info['id'])) return js_redirect('./?object='.main()->_get('object').'&action=coupons');
		
        $out = form2($info, array('dd_mode' => 1, 'big_labels' => true))
			->info('code')
            ->user_info('user_id')                
            ->info_date('time_start', array('format' => 'full'))                
            ->info_date('time_end', array('format' => 'full'))
			->info('sum')
			->info('status')
            ->info('cat_id')
        ;
        $out .= table("SELECT * FROM ".db('shop_coupons_log')." WHERE `code`='".$info['code']."' ORDER BY `time` DESC")
            ->date('time', array('format' => 'full', 'nowrap' => 1))
			->text('action')
		;

        return $out;       
	}
        
    function _cleanup_code($code) {
        return preg_replace('/[^0-9]+/ims', '', strip_tags($code));
    }    
}
