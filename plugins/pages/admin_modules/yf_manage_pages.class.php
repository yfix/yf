<?php

/**
*/
class yf_manage_pages {
/*
	`id` int(10) unsigned NOT NULL auto_increment,
	`locale` char(5) NOT NULL default \'\',
	`name` varchar(255) NOT NULL default \'\',
	`title` varchar(255) NOT NULL default \'\',
	`heading` varchar(255) NOT NULL default \'\',
	`text` longtext NOT NULL default \'\',
	`meta_keywords` text NOT NULL default \'\',
	`meta_desc` text NOT NULL default \'\',
	`date_created` datetime,
	`date_modified` datetime,
	`content_type` tinyint(2) unsigned NOT NULL default \'1\',
	`active` enum(\'1\',\'0\') NOT NULL default \'1\',
*/
	/**
	*/
	function show() {
		$filter_name = $_GET['object'].'__'.$_GET['action'];

		$sql = 'SELECT * FROM '.db('pages');
		return table($sql, array(
				'filter' => $_SESSION[$filter_name],
				'filter_params' => array(
					'name'	=> 'like',
				),
			))
			->text('name')
			->text('title')
			->func('text', function($field){ return _strlen($field); }, array('desc' => 'Length'))
			->btn_edit()
			->btn_delete()
			->btn('View', './?object='.$_GET['object'].'&action=view&id=%d')
			->btn_active()
			->footer_link('Add', './?object='.$_GET['object'].'&action=add');
	}

	/**
	*/
	function add() {
		if (empty($_POST['name'])) {
			return form(array('back_link' => './?object='.$_GET['object']))
				->text('name')
				->save_and_back();
		}
#		$name = preg_replace('/[^a-z0-9\_\-]/i', '_', _strtolower($_POST['name']));
#		$name = common()->_propose_url_from_name($_POST['name']);
#		$name = str_replace(array('__', '___'), '_', trim($name, '_-'));
		$name = $this->_name_url_cleanup($_POST['name']);
		if (strlen($name)) {
			db()->insert_safe('pages', array(
				'name'			=> $name,
				'title'			=> trim($_POST['name']),
				'date_created'	=> date('Y-m-d H:i:s'),
			));
			$page_id = db()->insert_id();
			common()->admin_wall_add(array('page added: '.$name, $page_id));
		}
		cache_del('pages_names');
		if (!empty($page_id)) {
			return js_redirect('./?object='.$_GET['object'].'&action=edit&id='.$page_id);
		} else {
			return _e('Cannot insert record!');
		}
	}

	/**
	*/
	function _name_url_cleanup($in) {
		$out = common()->_propose_url_from_name($in);
		$out = str_replace(array('__', '___'), '_', trim($out, '_-'));
		return $out;
	}

	/**
	*/
	function edit() {
		if (!isset($_GET['id'])) {
			return _e('No id');
		}
		$a = db()->get('SELECT * FROM '.db('pages').' WHERE name="'._es(_strtolower(urldecode($_GET['id']))).'" OR id='.intval($_GET['id']).' LIMIT 1');
		if (!$a) {
			return _e('No page info');
		}
		if (main()->is_post()) {
			if (isset($_POST['name'])) {
				$name = $this->_name_url_cleanup($_POST['name']);
#				$_POST['name'] = preg_replace('/[^a-z0-9\_\-]/i', '_', _strtolower($_POST['name']));
#				$_POST['name'] = str_replace(array('__', '___'), '_', $_POST['name']);
			}
			$sql = array();
			$fields = array(
				'name',
				'text',
				'title',
				'heading',
				'meta_keywords',
				'meta_desc',
				'active',
			);
			foreach ((array)$fields as $field) {
				if (isset($_POST[$field])) {
					$sql[$field] = trim($_POST[$field]);
				}
			}
			if ($sql['text']) {
				$sql['date_modified'] = date('Y-m-d H:i:s');
				db()->update_safe('pages', $sql, $a['id']);
				common()->admin_wall_add(array('page updated: '.$a['name'], $a['id']));
			}
			cache_del('pages_names');
			return js_redirect('./?object='.$_GET['object']);
		}
		$DATA = $a;
		foreach ((array)$_POST as $k => $v) {
			$DATA[$k] = $v;
		}
		$replace = array(
			'form_action'	=> './?object='.$_GET['object'].'&action='.$_GET['action'].'&id='.$a['id'],
			'name'			=> $DATA['name'],
			'text'			=> $DATA['text'],
			'title'			=> $DATA['title'],
			'heading'		=> $DATA['heading'],
			'meta_keywords'	=> $DATA['meta_keywords'],
			'meta_desc'		=> $DATA['meta_desc'],
			'active'		=> $DATA['active'],
			'back_url'		=> './?object='.$_GET['object'],
		);
		return form($replace)
			->text('name')
			->text('title')
			->textarea('text','',array('class' => 'span4','rows' => '10','ckeditor' => true, 'id' => 'text'))
#			->text('heading')
			->text('meta_keywords')
			->text('meta_desc')
			->active_box()
			->save_and_back();
	}

	/**
	*/
	function delete() {
		if (isset($_GET['id'])) {
			db()->query('DELETE FROM '.db('pages').' WHERE name="'._es(urldecode($_GET['id'])).'" OR id='.intval($_GET['id']));
			common()->admin_wall_add(array('page deleted: '.$_GET['id'], $_GET['id']));
		}
		cache_del('pages_names');
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo $page_name;
		} else {
			return js_redirect('./?object='.$_GET['object']);
		}
	}

	/**
	*/
	function active () {
		if (isset($_GET['id'])) {
			$a = db()->get('SELECT * FROM '.db('pages').' WHERE name="'._es(_strtolower(urldecode($_GET['id']))).'" OR id='.intval($_GET['id']));
		}
		if (!empty($a['id'])) {
			db()->update_safe('pages', array('active' => (int)!$a['active']), intval($a['id']));
			common()->admin_wall_add(array('page: '.$a['name'].' '.($a['active'] ? 'inactivated' : 'activated'), $a['id']));
			cache_del('pages_names');
		}
		if ($_POST['ajax_mode']) {
			main()->NO_GRAPHICS = true;
			echo ($a['active'] ? 0 : 1);
		} else {
			return js_redirect('./?object='.$_GET['object']);
		}
	}

	/**
	*/
	function view() {
		if (!empty($_GET['id'])) {
			$a = db()->get('SELECT * FROM '.db('pages').' WHERE name="'._es(_strtolower(urldecode($_GET['id']))).'" OR id='.intval($_GET['id']));
		}
		if (empty($a)) {
			return _e('No such page!');
		}
		$body = stripslashes($a['text']);
		$replace = array(
			'form_action'	=> './?object='.$_GET['object'].'&action=edit&id='.$a['id'],
			'back_link'		=> './?object='.$_GET['object'],
			'body'			=> $body,
		);
		return form($replace)
			->container('<h1>'._prepare_html($a['title']).'</h1>')
			->container($body, '', array(
				'id'	=> 'content_editable',
				'wide'	=> 1,
				'ckeditor' => array(
					'hidden_id'	=> 'text',
				),
			))
			->hidden('text')
			->save_and_back();
	}

	/**
	*/
	function _show_header() {
		$pheader = t('Pages');
		$subheader = _ucwords(str_replace('_', ' ', $_GET['action']));
		$cases = array (
			//$_GET['action'] => {string to replace}
			'show'			=> '',
			'edit'			=> '',
		);			 		
		if (isset($cases[$_GET['action']])) {
			$subheader = $cases[$_GET['action']];
		}
		return array(
			'header'	=> $pheader,
			'subheader'	=> $subheader ? _prepare_html($subheader) : '',
		);
	}

	/**
	*/
	function filter_save() {
		return _class('admin_methods')->filter_save();
	}

	/**
	*/
	function _show_filter() {
		if (!in_array($_GET['action'], array('show'))) {
			return false;
		}
		$filter_name = $_GET['object'].'__'.$_GET['action'];
		$r = array(
			'form_action'	=> './?object='.$_GET['object'].'&action=filter_save&id='.$filter_name,
			'clear_url'		=> './?object='.$_GET['object'].'&action=filter_save&id='.$filter_name.'&page=clear',
		);
		$order_fields = array(
			'name'		=> 'name',
			'active'	=> 'active',
		);
		return form($r, array(
				'selected'	=> $_SESSION[$filter_name],
			))
			->text('name')
			->text('title')
			->text('heading')
#			->locale_select_box()
#			->active_box()
			->select_box('order_by', $order_fields, array('show_text' => 1))
			->radio_box('order_direction', array('asc'=>'Ascending','desc'=>'Descending'))
			->save_and_clear();
		;
	}

}
