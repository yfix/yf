<?php

class ck_file_browser {

	protected $base = null;
	
	/**
	*/
	function _init () {
		$this->TOP_DIR = '/uploads/';
//		$this->WRITABLE_DIR = '/uploads/ck_browser/';
//		$this->TOP_DIR = '/uploads/ck_browser/';
		$this->base = $this->_real(INCLUDE_PATH . $this->TOP_DIR);
	}

	/**
	*/
	function _ajax_out($rslt) {
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($rslt);
		if (is_ajax()) {
			exit();
		}
	}
	
	/**
	*/
	function show () {
		asset('jquery-jstree');
		$funcNum = $_GET['CKEditorFuncNum'];
		$items = array();
		_mkdir_m(INCLUDE_PATH . $this->TOP_DIR);
		_mkdir_m(INCLUDE_PATH . $this->WRITABLE_DIR);
		no_graphics(true);
		$body = tpl()->parse(__CLASS__.'/main', array(
			'ck_funcnum' => $funcNum,
		));
		return print common()->show_empty_page($body);
#		exit;
	}

	/**
	*/
	function get_node() {
		$node = isset($_GET['id']) && $_GET['id'] !== '#' ? $_GET['id'] : '/';
		$rslt = $this->_lst($node, (isset($_GET['id']) && $_GET['id'] === '#'));
		return $this->_ajax_out($rslt);
	}

	/**
	*/
	function get_content() {
		$node = isset($_GET['id']) && $_GET['id'] !== '#' ? $_GET['id'] : '/';
		$rslt = $this->_data($node);
		return $this->_ajax_out($rslt);
	}

	/**
	*/
	function create_node() {
		$node = isset($_GET['id']) && $_GET['id'] !== '#' ? $_GET['id'] : '/';
		$rslt = $this->_create($node, isset($_GET['text']) ? $_GET['text'] : '', (!isset($_GET['type']) || $_GET['type'] !== 'file'));
		return $this->_ajax_out($rslt);
	}

	/**
	*/
	function rename_node() {
		$node = isset($_GET['id']) && $_GET['id'] !== '#' ? $_GET['id'] : '/';
		$rslt = $this->_rename($node, isset($_GET['text']) ? $_GET['text'] : '');
		return $this->_ajax_out($rslt);
	}

	/**
	*/
	function delete_node() {
		$node = isset($_GET['id']) && $_GET['id'] !== '#' ? $_GET['id'] : '/';
		$rslt = $this->_remove($node);
		return $this->_ajax_out($rslt);
	}

	/**
	*/
	function move_node() {
		$node = isset($_GET['id']) && $_GET['id'] !== '#' ? $_GET['id'] : '/';
		$parn = isset($_GET['parent']) && $_GET['parent'] !== '#' ? $_GET['parent'] : '/';
		$rslt = $this->_move($node, $parn);
		return $this->_ajax_out($rslt);
	}

	/**
	*/
	function copy_node() {
		$node = isset($_GET['id']) && $_GET['id'] !== '#' ? $_GET['id'] : '/';
		$parn = isset($_GET['parent']) && $_GET['parent'] !== '#' ? $_GET['parent'] : '/';
		$rslt = $this->_copy($node, $parn);
		return $this->_ajax_out($rslt);
	}

	/**
	*/
	function upload_file() {
		$error = '';
		$upload_dir = $this->base .'/'. $_GET['dir'];
		if (empty($_FILES['file']['tmp_name'])) {
			$error = 'File upload error';
		} else {
			$file_name = $_FILES['file']['name'];
			$file_path = $upload_dir.'/'.$file_name;
			if (file_exists($file_path)) {
				$error = 'File already exists';
			} else {
				move_uploaded_file($_FILES['file']['tmp_name'], $file_path);
				if (!file_exists($file_path)) {
					$error = 'Cannot upload file to this dir';
				}
			}
		}
		if ($error) {
			echo '<script>alert("'._prepare_html( t($error) ).'");</script>';
		} else {
			echo '<script>try { parent.refreshjstree(); } catch(e) { console.log(e) }</script>';
		}
	}

	/**
	*/
	function _real($path) {
		$temp = realpath($path);
		if (!$temp) {
			throw new Exception('Path does not exist: ' . $path);
		}
		if ($this->base && strlen($this->base)) {
			if (strpos($temp, $this->base) !== 0) {
				throw new Exception('Path is not inside base ('.$this->base.'): ' . $temp);
			}
		}
		return $temp;
	}
	
	/**
	*/
	function _path($id) {
		$id = str_replace('/', DIRECTORY_SEPARATOR, $id);
		$id = trim($id, DIRECTORY_SEPARATOR);
		$id = $this->_real($this->base . DIRECTORY_SEPARATOR . $id);
		return $id;
	}
	
	/**
	*/
	function _id($path) {
		$path = $this->_real($path);
		$path = substr($path, strlen($this->base));
		$path = str_replace(DIRECTORY_SEPARATOR, '/', $path);
		$path = trim($path, '/');
		return strlen($path) ? $path : '/';
	}
	
	/**
	*/
	function _lst($id, $with_root = false) {
		$dir = $this->_path($id);
		$lst = @scandir($dir);
		if (!$lst) {
			throw new Exception('Could not list path: ' . $dir);
		}
		$res = array();
		foreach ($lst as $item) {
			if ($item == '.' || $item == '..' || $item === null) {
				continue;
			}
//			$tmp = preg_match('([^ a-zа-я-_0-9.]+)ui', $item);
//			if($tmp === false || $tmp === 1) { continue; }
			if (is_dir($dir . DIRECTORY_SEPARATOR . $item)) {
				$res[] = array(
					'text'		=> $item,
					'children'	=> true,
					'id'		=> $this->_id($dir . DIRECTORY_SEPARATOR . $item),
					'icon'		=> 'folder'
				);
			} else {
				$res[] = array(
					'text'		=> $item,
					'children'	=> false,
					'id'		=> $this->_id($dir . DIRECTORY_SEPARATOR . $item),
					'type'		=> 'file',
					'icon'		=> 'file file-'.substr($item, strrpos($item,'.') + 1)
				);
			}
		}
		if ($with_root && $this->_id($dir) === '/') {
			$res = array(array(
				'text' => basename($this->base),
				'children' => $res,
				'id' => '/',
				'icon'=>'folder',
				'state' => array(
					'opened' => true,
					'disabled' => true
				)
			));
		}
		return $res;
	}
	
	/**
	*/
	function _data($id) {
		if (strpos($id, ':')) {
			$id = array_map(array($this, 'id'), explode(':', $id));
			return array('type'=>'multiple', 'content'=> 'Multiple selected: ' . implode(' ', $id));
		}
		$dir = $this->_path($id);
		if (is_dir($dir)) {
			$form = form(true, array(
				'action'		=> url('/@object/upload_file/?dir='.urlencode($id)),
				'autocomplete'	=> 'off', 
				'enctype'		=> 'multipart/form-data',
				'class'			=> 'form-condensed form-no-labels',
				'target'		=> 'file_upload_process_container',
				'no_label'		=> 1,
			))
			->file('file', t('upload image'), array('accept' => 'image/*', 'style' => 'width:auto; background: inherit', 'class_add' => 'btn btn-primary'))
			->save(array('value' => t('Upload'), 'class' => 'btn btn-primary'))
			;
			$content = t('Current folder:') . ' <b>' . $this->TOP_DIR . $id . '</b><br />' . $form;
			return array(
				'type' => 'folder',
				'content' => $content
			);
		}
		if (is_file($dir)) {
			$ext = strpos($dir, '.') !== FALSE ? substr($dir, strrpos($dir, '.') + 1) : '';
			$dat = array('type' => $ext, 'content' => '');
			switch($ext) {
				case 'jpg':
				case 'jpeg':
				case 'gif':
				case 'png':
				case 'bmp':
					$dat['content'] = WEB_PATH . $this->TOP_DIR . $id;
					$dat['info']	= round(filesize(INCLUDE_PATH . $this->TOP_DIR . $id)/1024,0,2).'Kb';
					break;
				default:
					$dat['content'] = t('File is not an image: '.$this->_id($dir));
					break;
			}
			return $dat;
		}
		throw new Exception('Not a valid selection: ' . $dir);
	}

	/**
	*/
	function _create($id, $name, $mkdir = false) {
		$dir = $this->_path($id);
		if (preg_match('([^ a-zа-я-_0-9.]+)ui', $name) || !strlen($name)) {
			throw new Exception('Invalid name: ' . $name);
		}
		if ($mkdir) {
			mkdir($dir . DIRECTORY_SEPARATOR . $name);
		}
		return array('id' => $this->_id($dir . DIRECTORY_SEPARATOR . $name));
	}

	/**
	*/
	function _rename($id, $name) {
		$dir = $this->_path($id);
		if ($dir === $this->base) {
			throw new Exception('Cannot rename root');
		}
		if (preg_match('([^ a-zа-я-_0-9.]+)ui', $name) || !strlen($name)) {
			throw new Exception('Invalid name: ' . $name);
		}
		$new = explode(DIRECTORY_SEPARATOR, $dir);
		array_pop($new);
		array_push($new, $name);
		$new = implode(DIRECTORY_SEPARATOR, $new);
		if($dir !== $new) {
			if (is_file($new) || is_dir($new)) {
				throw new Exception('Path already exists: ' . $new);
			}
			rename($dir, $new);
		}
		return array('id' => $this->_id($new));
	}

	/**
	*/
	function _remove($id) {
		$dir = $this->_path($id);
		if ($dir === $this->base) {
			throw new Exception('Cannot remove root');
		}
		if (is_dir($dir)) {
			foreach(array_diff(scandir($dir), array('.', '..')) as $f) {
				$this->_remove($this->_id($dir . DIRECTORY_SEPARATOR . $f));
			}
			rmdir($dir);
		}
		if (is_file($dir)) {
			unlink($dir);
		}
		return array('status' => 'OK');
	}

	/**
	*/
	function _move($id, $par) {
		$dir = $this->_path($id);
		$par = $this->_path($par);
		$new = explode(DIRECTORY_SEPARATOR, $dir);
		$new = array_pop($new);
		$new = $par . DIRECTORY_SEPARATOR . $new;
		rename($dir, $new);
		return array('id' => $this->_id($new));
	}

	/**
	*/
	function _copy($id, $par) {
		$dir = $this->_path($id);
		$par = $this->_path($par);
		$new = explode(DIRECTORY_SEPARATOR, $dir);
		$new = array_pop($new);
		$new = $par . DIRECTORY_SEPARATOR . $new;
		if (is_file($new) || is_dir($new)) {
			throw new Exception('Path already exists: ' . $new);
		}
		if (is_dir($dir)) {
			mkdir($new);
			foreach(array_diff(scandir($dir), array('.', '..')) as $f) {
				$this->_copy($this->_id($dir . DIRECTORY_SEPARATOR . $f), $this->_id($new));
			}
		}
		if (is_file($dir)) {
			copy($dir, $new);
		}
		return array('id' => $this->_id($new));
	}	
}
