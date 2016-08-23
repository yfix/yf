<?php

class yf_ck_file_browser {

	public $TOP_DIR = '/uploads/';
	public $WRITABLE_DIR = '/uploads/ck_browser/';
	public $ALLOWED_EXTS = [
		'jpg',
		'jpeg',
		'png',
		'gif',
	];
	public $MIN_FILE_SIZE = 50;
	protected $base = null;
	public $ENABLED_IMG_EDIT = true;
	public $ENABLED_IMG_DELETE = true;
	
	/**
	*/
	function _init() {
		$this->base = $this->_real(PROJECT_PATH . $this->TOP_DIR);
		_mkdir_m(PROJECT_PATH . $this->TOP_DIR);
		_mkdir_m(PROJECT_PATH . $this->WRITABLE_DIR);
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
	function show() {
		asset('jquery-jstree');
		$slick_view = isset($_GET['CKEditorFuncNum']);
		$body = tpl()->parse(__CLASS__.'/main', [
			'ck_funcnum' => (int)$_GET['CKEditorFuncNum'],
		]);
		return $slick_view ? print common()->show_empty_page($body) : $body;
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
		$upload_dir = $this->base .'/'. $_GET['id'];
		$file = $_FILES['file'];
		if (empty($file['tmp_name'])) {
			$error = 'File upload error';
		} else {
			$file_name = $file['name'];
			$file_path = $upload_dir.'/'.$file_name;
			if (file_exists($file_path)) {
				$error = 'File already exists';
			} else {
				move_uploaded_file($file['tmp_name'], $file_path);
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
	* Endpoint for pixlr editor for upload back edited image
	*/
	function edit() {
		if (!$this->ENABLED_IMG_EDIT) {
			return false;
		}
		$img_url= urldecode($_REQUEST['image']);
		$title	= ltrim(str_replace('|', '/', urldecode($_REQUEST['title'])), '/');
		$type	= strtolower($_REQUEST['type']);
		if (!strlen($img_url)
			|| parse_url($img_url, PHP_URL_HOST) !== 'apps.pixlr.com'
			|| !strlen($title)
			|| false !== strpos($title, '../')
			|| !in_array($type, $this->ALLOWED_EXTS)
		) {
			common()->message_error('Image upload from Pixlr error #1: wrong input');
			return common()->show_messages();
		}

		// Move image from url into temp file and analyze it
		$tmp_dir = '/tmp/pixlr_upload/';
		!file_exists($tmp_dir) && _mkdir_m($tmp_dir);
		$tmp_path = tempnam($tmp_dir, 'pixlr_upload_');
		file_put_contents($tmp_path, file_get_contents($img_url));
		if (!file_exists($tmp_path) || filesize($tmp_path) <= $this->MIN_FILE_SIZE) {
			common()->message_error('Image upload from Pixlr error #2: temp file error');
			return common()->show_messages();
		}
		$target = PROJECT_PATH . $this->TOP_DIR. $title. '.'. $type;
		if (!file_exists($target)) {
			common()->message_error('Image upload from Pixlr error #3: target not exists');
			return common()->show_messages();
		}

		// copy old and new file as revision into separate dir
		$revs_dir = PROJECT_PATH. 'uploads/.img_revisions/';
		!file_exists($revs_dir) && _mkdir_m($revs_dir);
		if (md5_file($target) != md5_file($tmp_path)) {
			$revid = date('YmdHis_'.str_pad(substr(microtime(true), 11, 2), 2, '0', STR_PAD_LEFT));
			$rev_path_old = $revs_dir. $revid. '__old__'. urlencode($title). '.'. $type;
			$rev_path_new = $revs_dir. $revid. '__new__'. urlencode($title). '.'. $type;
			file_put_contents($rev_path_old, file_get_contents($target));
			file_put_contents($rev_path_new, file_get_contents($tmp_path));
		}
		// Finally save new file
		file_put_contents($target, file_get_contents($tmp_path));
		unlink($tmp_path);
		common()->message_success('Image upload from Pixlr success!');
		$web_path = str_replace(PROJECT_PATH, MEDIA_PATH, $target);
		return common()->show_messages()
			. '<br />path: '._prepare_html($title).', size: '.filesize($target)
			. '<br /><a href="'.$web_path.'" target="_blank"><img src="'.$web_path.'" style="max-width: 200px; max-height: 200px;"></a>'
			. '<br /><br />'. a(['href' => '/@object/show/'.urlencode($title), 'title' => 'Go Next', 'target' => '']);
	}

	/**
	* Endpoint for delete image (with backuping old)
	*/
	function delete_img() {
		$res = [];
		if ($this->ENABLED_IMG_DELETE && is_ajax() && is_post() && !empty($_POST['path'])) {
			$fs_path = parse_url($_POST['path'], PHP_URL_PATH);
			if (substr($fs_path, 0, strlen($this->TOP_DIR)) === $this->TOP_DIR) {
				$fs_path = substr($fs_path, strlen($this->TOP_DIR));
			}
 			$fs_path = $this->_real($this->base . '/' . trim($fs_path, '/'));
			if (is_file($fs_path)) {
				if ($this->_backup_deleted_img($fs_path) && unlink($fs_path)) {
					$res = ['result' => 'OK'];
				} else {
					$res = ['result' => 'Error: cannot backup or delete image, maybe file or dir permissions?'];
				}
			}
		}
		return $this->_ajax_out($res);
	}

	/**
	* copy old file as revision into separate dir
	*/
	function _backup_deleted_img($fs_path) {
		if (!$fs_path || !file_exists($fs_path)) {
			return false;
		}
		$ext = pathinfo($fs_path, PATHINFO_EXTENSION);
		$path_wo_ext = substr($fs_path, strlen($this->base), -strlen('.'.$ext));
		$revs_dir = PROJECT_PATH. 'uploads/.img_revisions/';
		!file_exists($revs_dir) && _mkdir_m($revs_dir);
		$revid = date('YmdHis_'.str_pad(substr(microtime(true), 11, 2), 2, '0', STR_PAD_LEFT));
		$rev_path = $revs_dir. $revid. '__deleted__'. urlencode($path_wo_ext). '.'. $ext;
		return file_put_contents($rev_path, file_get_contents($fs_path));
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
		$id = str_replace('/', '/', $id);
		$id = trim($id, '/');
		$id = $this->_real($this->base . '/' . $id);
		return $id;
	}
	
	/**
	*/
	function _id($path) {
		$path = $this->_real($path);
		$path = substr($path, strlen($this->base));
		$path = str_replace('/', '/', $path);
		$path = trim($path, '/');
		return strlen($path) ? $path : '/';
	}
	
	/**
	*/
	function _lst($id, $with_root = false) {
		$dir = $this->_path($id);
		$res = [];
		foreach (glob(rtrim($dir, '/'). '/*') as $f) {
			if (!strlen($f) || !file_exists($f)) {
				continue;
			}
			$item = basename($f);
			if (is_dir($f)) {
				$res[] = [
					'text'		=> $item,
					'children'	=> true,
					'id'		=> $this->_id($f),
					'icon'		=> 'fa fa-folder fa-lg'
				];
			} else {
				if (filesize($f) <= $this->MIN_FILE_SIZE) {
					continue;
				}
				$ext = strtolower(pathinfo($item, PATHINFO_EXTENSION));
				if (!in_array($ext, $this->ALLOWED_EXTS)) {
					continue;
				}
				$res[] = [
					'text'		=> $item,
					'children'	=> false,
					'id'		=> $this->_id($f),
					'type'		=> 'file',
					'icon'		=> 'fa fa-file-image-o fa-lg fa-file-type-'.$ext,
				];
			}
		}
		if ($with_root && $this->_id($dir) === '/') {
			$res = [[
				'text'		=> basename($this->base),
				'children'	=> $res,
				'id'		=> '/',
				'icon'		=> 'fa fa-folder fa-lg',
				'state'		=> [
					'opened'	=> true,
					'disabled'	=> true,
				]
			]];
		}
		return $res;
	}
	
	/**
	*/
	function _data($id) {
		if (strpos($id, ':')) {
			$id = array_map([$this, 'id'], explode(':', $id));
			return [
				'type' => 'multiple',
				'content'=> 'Multiple selected: ' . implode(' ', $id)
			];
		}
		$dir = $this->_path($id);
		if (is_dir($dir)) {
			$form = form(true, [
				'action'		=> url('/@object/upload_file/'.urlencode($id)),
				'autocomplete'	=> 'off', 
				'enctype'		=> 'multipart/form-data',
				'class'			=> 'form-condensed form-no-labels ck_upload_form',
				'target'		=> 'file_upload_process_container',
				'no_label'		=> 1,
			])
			->file('file', t('upload image'), [
				'accept' => 'image/*',
				'style' => 'width:auto; background: inherit',
				'class_add' => 'btn btn-primary'
			])
			->save([
				'value' => t('Upload'),
				'class' => 'btn btn-primary'
			]);
			$images = [];
			$files = [];
			foreach (glob(rtrim($dir).'/*') as $f) {
				if (!is_file($f)) {
					continue;
				}
				$ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
				if (!in_array($ext, $this->ALLOWED_EXTS)) {
					continue;
				}
				if (($fsize = filesize($f)) <= $this->MIN_FILE_SIZE) {
					continue;
				}
				$sizes[$f] = $fsize;
				$files[$f] = filemtime($f);
			}
			// Sort files by date DESC
			arsort($files);
			foreach ((array)$files as $f => $mtime) {
				$ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
				list($w, $h) = getimagesize($f);
				$fsize = $sizes[$f];
				$fsize = round($fsize / 1024, 0, 2).'Kb';
				$uploads_path = str_replace('/', '|', ltrim(str_replace(PROJECT_PATH. ltrim($this->TOP_DIR, '/'), '', $f), '/'));
				$images[] = ''
					. '<div class="ck_select_image">'
						. '<a href="#" class="img-select" title="'._prepare_html(basename($f)).'">'
							. '<img src="'.str_replace(PROJECT_PATH, MEDIA_PATH, $f).'?m='.intval($mtime).'" data-uploads-path="'._prepare_html($uploads_path).'" />'
						. '</a>'
						. '<div class="img-details">'.$fsize.' '.$w.'x'.$h.' '.strtoupper($ext).'<br />'.date('Y-m-d H:i:s', $mtime).'</div>'
						. '<div class="img-actions">'
#							. a('#', 'Choose', 'fa fa-share', '', 'btn-info')
#							. a('#', 'View', 'fa fa-eye', '')
#							. a('#', 'Copy', 'fa fa-copy', '')
							. ($this->ENABLED_IMG_DELETE ? a('#', 'Delete', 'fa fa-trash', '', 'btn-danger btn-delete') : '')
							. ($this->ENABLED_IMG_EDIT ? a('#', 'Edit', 'fa fa-edit', '', 'btn-warning btn-edit') : '')
						. '</div>'
					. '</div>';
			}
			return [
				'type'		=> 'folder',
				'content'	=> ''
					. '<div>'.t('Current folder:').' '
						. '<b>' . $this->TOP_DIR. $id. '</b><br />'
						. $form. '<br />'
						. implode(PHP_EOL, $images)
					. '</div>',
			];
		} elseif (is_file($dir)) {
			$ext = strtolower(pathinfo($dir, PATHINFO_EXTENSION));
			$dat = [
				'type' => $ext,
				'content' => ''
			];
			switch($ext) {
				case 'jpg':
				case 'jpeg':
				case 'gif':
				case 'png':
				case 'bmp':
					$dat['content'] = MEDIA_PATH . $this->TOP_DIR . $id;
					$dat['info'] = round(filesize(PROJECT_PATH . $this->TOP_DIR . $id) / 1024, 0, 2).'Kb';
					break;
				default:
					$dat['content'] = t('File is not an image: '.$this->_id($dir));
					break;
			}
			return $dat;
		}
		throw new Exception('Not a valid selection: '. $dir);
	}

	/**
	*/
	function _create($id, $name, $mkdir = false) {
		$dir = $this->_path($id);
		if (preg_match('([^ a-zа-я-_0-9.]+)ui', $name) || !strlen($name)) {
			throw new Exception('Invalid name: ' . $name);
		}
		if ($mkdir) {
			_mkdir_m($dir . '/' . $name);
		}
		return ['id' => $this->_id($dir . '/' . $name)];
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
		$new = explode('/', $dir);
		array_pop($new);
		array_push($new, $name);
		$new = implode('/', $new);
		if ($dir !== $new) {
			if (is_file($new) || is_dir($new)) {
				throw new Exception('Path already exists: ' . $new);
			}
			rename($dir, $new);
		}
		return ['id' => $this->_id($new)];
	}

	/**
	*/
	function _remove($id) {
		$dir = $this->_path($id);
		if ($dir === $this->base) {
			throw new Exception('Cannot remove root');
		}
		if (is_dir($dir)) {
			foreach (glob(rtrim($dir, '/'). '/*') as $f) {
				$this->_remove($this->_id($dir . '/' . basename($f)));
			}
			rmdir($dir);
		}
		if (is_file($dir)) {
			unlink($dir);
		}
		return ['status' => 'OK'];
	}

	/**
	*/
	function _move($id, $par) {
		$dir = $this->_path($id);
		$par = $this->_path($par);
		$new = explode('/', $dir);
		$new = array_pop($new);
		$new = $par . '/' . $new;
		rename($dir, $new);
		return ['id' => $this->_id($new)];
	}

	/**
	*/
	function _copy($id, $par) {
		$dir = $this->_path($id);
		$par = $this->_path($par);
		$new = explode('/', $dir);
		$new = array_pop($new);
		$new = $par . '/' . $new;
		if (is_file($new) || is_dir($new)) {
			throw new Exception('Path already exists: ' . $new);
		}
		if (is_dir($dir)) {
			_mkdir_m($new);
			foreach (glob(rtrim($dir, '/'). '/*') as $f) {
				$this->_copy($this->_id($dir . '/' . basename($f)), $this->_id($new));
			}
		}
		if (is_file($dir)) {
			copy($dir, $new);
		}
		return ['id' => $this->_id($new)];
	}	
}
