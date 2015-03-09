<?php

class yf_manage_news {

	/**
	*/
	function _get_page_info($id = null) {
		$id = isset($id) ? $id : $_GET['id'];
		return db()->from('news')
			->where('title', _strtolower(urldecode($id)) )
			->or_where('id', (int)$id)->get();
	}

	/**
	*/
	function show() {
		return table(
			db()->from('news')->order_by('add_date', 'desc')
			, array(
				'filter' => true,
				'filter_params' => array(
					'title'	=> 'like',
				),
			))
			->text('id')
			->text('title')
			->date('add_date', array('format' => 'full', 'nowrap' => 1))
#			->btn('View', url('/@object/view/%d'))
			->btn_edit(array('no_ajax' => 1))
			->btn_delete()
			->btn_active()
			->footer_link('Add', url('/@object/add'));
		;
	}

	/**
	*/
	function add() {
		db()->insert_safe('news', array(
			'add_date'	=> time(),
			'active'	=> 0,
		));
		return js_redirect(url('/@object/edit/'.db()->insert_id()));
	}

	/**
	*/
	function edit() {
		$a = $this->_get_page_info();
		if (!$a) {
			return _e('No info');
		}
		$a = (array)$_POST + (array)$a;
		$a['back_link'] = url('/@object');
		$_this = $this;
		$cke_config = array(
			'toolbar' => array(
				array(
					'Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo', 'RemoveFormat', 'Format', 'Bold', 'Italic', 'Underline' ,
					'FontSize' ,'TextColor' , 'NumberedList', 'BulletedList', '-', 'Blockquote', 'Link', 'Unlink', 'Image', '-', 'SpecialChar', '-', 'Source', '-', 'Maximize'
				),
			),
			'language' => conf('language'),
			'removePlugins' => 'bidi,dialogadvtab,filebrowser,flash,horizontalrule,iframe,pagebreak,showborders,table,tabletools,templates,style',
			'format_tags' => 'p;h1;h2;h3;h4;h5;h6;pre;address;div',
			'extraAllowedContent' => 'a[*]{*}(*); img[*]{*}(*); div[*]{*}(*)',
		);
		return form($a)
			->validate(array(
				'__before__'=> 'trim',
				'title' => array('required'),
				'full_text' => 'required',
			))
			->db_update_if_ok('news', array('title','head_text','full_text','meta_keywords','meta_desc','active'), 'id='.$a['id'])
			->on_after_update(function() {
				common()->admin_wall_add(array('news updated: '.$a['id'], $a['id']));
			})
			->text('title')
			->textarea('full_text', array('id' => 'full_text', 'cols' => 200, 'rows' => 10, 'ckeditor' => array('config' => $cke_config)))
			->active_box()
			->save_and_back()
		;
	}

	/**
	*/
	function view() {
		$a = $this->_get_page_info();
		if (empty($a)) {
			return _e('No such page!');
		}
		$body = stripslashes($a['full_text']);
		$replace = array(
			'form_action'	=> url('/@object/edit/'.$a['id']),
			'back_link'		=> url('/@object'),
			'body'			=> $body,
		);
		return form($replace)
			->container('<h3>'.$a['title'].'</h3>', array('wide' => 1))
			->container($body, '', array(
				'id'	=> 'content_editable',
				'wide'	=> 1,
				'ckeditor' => array(
					'hidden_id'	=> 'full_text',
				),
			))
			->hidden('full_text')
			->hidden('title')
			->save_and_back();
	}

	/**
	*/
	function delete() {
		$id = (int)$_GET['id'];
		if ($a = db()->from('news')->whereid($id)->get()) {
			db()->from('news')->delete($id);
		}
		return js_redirect(url('/@object'));
	}

	/**
	*/
	function active() {
		$id = (int)$_GET['id'];
		if ($a = $this->_get_page_info()) {
			db()->update_safe('news', array('active' => (int)!$a['active']), 'id='.intval($a['id']));
		}
		if (main()->is_ajax()) {
			main()->NO_GRAPHICS = true;
			return print intval(!$a['active']);
		}
		return js_redirect(url('/@object'));
	}
}
