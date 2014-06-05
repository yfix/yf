<?php

/**
* Templates handling class
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_template_editor {

	/***/
	private	$_preload_complete = false;
	/***/
	public $CACHE_NAME = 'themes_num_stpls';

	/**
	*/
	function __get ($name) {
		if (!$this->_preload_complete) {
			$this->_preload_data();
		}
		return $this->$name;
	}

	/**
	*/
	function __set ($name, $value) {
		if (!$this->_preload_complete) {
			$this->_preload_data();
		}
		$this->$name = $value;
		return $this->$name;
	}

	/**
	*/
	function _preload_data () {
		if ($this->_preload_complete) {
			return true;
		}
		$this->_preload_complete = true;
		$this->_dir_array = array(
			'framework'			=> YF_PATH. tpl()->_THEMES_PATH,
			'project'			=> INCLUDE_PATH. tpl()->_THEMES_PATH,
#			'framework_p2'		=> YF_PATH. 'priority2/'. tpl()->_THEMES_PATH,
#			'project_p2'		=> INCLUDE_PATH. 'priority2/'. tpl()->_THEMES_PATH,
#			'framework_user'	=> YF_PATH. 'templates/user/',
		);
		foreach ((array)_class('sites_info')->info as $site_dir_array) {
			$this->_dir_array[$site_dir_array['name']] = $site_dir_array['REAL_PATH'].'templates/';		
		}
	}

	/**
	*/
	function show () {
		return $this->_show_themes();
	}

	/**
	*/
	function _show_themes () {
		$themes = $this->_get_themes();

		$num_stpls_array = cache_get($this->CACHE_NAME, 60);
		if (empty($num_stpls_array)) {
			foreach ((array)$themes as $theme_class => $theme_attr) {
				foreach ((array)$theme_attr as $theme_path => $theme_name) {
					$num_stpls_array[$theme_name][$this->_dir_array[$theme_class]] = count($this->_get_stpls_in_theme($theme_name, $this->_dir_array[$theme_class]));
				}
			}
			cache_set($this->CACHE_NAME, $num_stpls_array);
		}
		// Process records
		$rp = realpath($this->_dir_array['project']);
		foreach ((array)$themes as $theme_class => $theme_attr) {
			if ($rp == realpath($this->_dir_array[$theme_class]) && $theme_class != 'project') {
				continue;
			}
			$replace3 = array(
				'location'			=> $theme_class,
				'themes_lib_dir'	=> realpath($this->_dir_array[$theme_class]),
				'add_url'			=> './?object='.$_GET['object'].'&action=add_theme_form&location='.$theme_class,
			);
			$items .= tpl()->parse($_GET['object'].'/themes_location_item', $replace3);
 
			foreach ((array)$theme_attr as $theme_path => $theme_name) {
				$replace2 = array(
					'num'				=> ++$i,
					'bg_class'			=> 'bg2',
					'name'				=> $theme_name,
					'num_stpls'			=> $num_stpls_array[$theme_name][$this->_dir_array[$theme_class]],
					'theme_url'			=> './?object='.$_GET['object'].'&action=show_stpls_in_theme&theme='.$theme_name.'&location='.$theme_class,
					'edit_url'			=> './?object='.$_GET['object'].'&action=edit_theme&theme='.$theme_name.'&location='.$theme_class,
					'location'			=> $theme_class,
				);
				$items .= tpl()->parse($_GET['object'].'/themes_item', $replace2);
			}
		}
		$replace = array(
			'items' 		=> $items,
			'add_url'		=> './?object='.$_GET['object'].'&action=add_theme_form',
			'import_url'	=> './?object='.$_GET['object'].'&action=import',
		);
		return tpl()->parse($_GET['object'].'/themes_main', $replace);
	}

	/**
	*/
	function edit_theme () {
		if (false !== strpos($_GET['location'], 'framework')) {
			return $this->_framework_warning();
		}
		$new_theme_name	= $_POST['theme_name'];
		if (empty($_GET['theme']) && empty($new_theme_name)) {
			return _e('Theme name required!');
		}
		if (main()->is_post()) {
			if ($_GET['theme'] != $new_theme_name) {
				rename($this->_dir_array[$_GET['location']]. $_GET['theme'], $this->_dir_array[$_GET['location']]. $new_theme_name);
			}
			return js_redirect('./?object='.$_GET['object'].'&action=show');
		}
		$replace = array(
			'form_action'	=> './?object='.$_GET['object'].'&action='.$_GET['action'].'&theme='.$_GET['theme'].'&location='.$_GET['location'],
			'back_url'		=> './?object='.$_GET['object'].'&action=show',
			'theme_name' 	=> _prepare_html($_GET['theme']),
			'location'		=> $_GET['location'],
		);
		return tpl()->parse($_GET['object'].'/edit_theme', $replace);
	}

	/**
	*/
	function show_stpls_in_theme () {
		$this->theme_name = $_GET['theme'];
		$this->_cur_theme_path = $this->_dir_array[$_GET['location']]. $this->theme_name;
		$files_array = _class('dir')->scan_dir($this->_cur_theme_path, false);
		$items_array = $this->_show_stpls_list($files_array);

		list($items_array, $pages, $total) = common()->divide_pages($items_array, null, null, $PER_PAGE);
		$items = implode('', $items_array);

		$replace = array(
			'items'			=> $items,
			'pages'			=> $pages,
			'total'			=> intval($total),
			'theme_name'	=> $this->theme_name,
			'back_url'		=> './?object='.$_GET['object'].'&action=show',
			'form_action'	=> './?object='.$_GET['object'].'&action=save_stpl&theme='.$this->theme_name.'&location='.$_GET['location'],
			'location'		=> $_GET['location'],
		);
		return tpl()->parse($_GET['object'].'/stpls_list_main', $replace);
	}

	/**
	* External API method
	*/
	function _get_stpls_for_type ($type = 'user') {
		$theme_name = $type == 'admin' ? 'admin' : 'user';

		$CACHE_NAME = 'stpls_list_for_'.$type;
		$TTL = 600;
		$items = cache_get($CACHE_NAME, $TTL);
		if (!empty($items)) {
			return $items;
		}
		$items = array();

		$STPL_EXT = '.stpl';
		$pattern_include = array('', '#\.stpl$#i');
		$pattern_exclude = '#(svn|git)#i';

		$cur_theme_path = $this->_dir_array['framework']. $theme_name. '/';
		foreach ((array)_class('dir')->scan_dir($cur_theme_path, true, $pattern_include, $pattern_exclude) as $v) {
			$name = substr($v, strlen($cur_theme_path), -strlen($STPL_EXT));
			$items[$name] = $name;
		}
/*		$cur_theme_path = $this->_dir_array['framework_p2']. $theme_name. '/';
		foreach ((array)_class('dir')->scan_dir($cur_theme_path, true, $pattern_include, $pattern_exclude) as $v) {
			$name = substr($v, strlen($cur_theme_path), -strlen($STPL_EXT));
			$items[$name] = $name;
		}
*/		$cur_theme_path = $this->_dir_array['project']. $theme_name. '/';
		foreach ((array)_class('dir')->scan_dir($cur_theme_path, true, $pattern_include, $pattern_exclude) as $v) {
			$name = substr($v, strlen($cur_theme_path), -strlen($STPL_EXT));
			$items[$name] = $name;
		}
/*		$cur_theme_path = $this->_dir_array['project_p2']. $theme_name. '/';
		foreach ((array)_class('dir')->scan_dir($cur_theme_path, true, $pattern_include, $pattern_exclude) as $v) {
			$name = substr($v, strlen($cur_theme_path), -strlen($STPL_EXT));
			$items[$name] = $name;
		}
*/		// Inherit user templates from framework and project
		if ($type == 'admin') {
			$cur_theme_path = $this->_dir_array['framework']. 'user'. '/';
			foreach ((array)_class('dir')->scan_dir($cur_theme_path, true, $pattern_include, $pattern_exclude) as $v) {
				$name = substr($v, strlen($cur_theme_path), -strlen($STPL_EXT));
				$items[$name] = $name;
			}

			$cur_theme_path = $this->_dir_array['project']. 'user'. '/';
			foreach ((array)_class('dir')->scan_dir($cur_theme_path, true, $pattern_include, $pattern_exclude) as $v) {
				$name = substr($v, strlen($cur_theme_path), -strlen($STPL_EXT));
				$items[$name] = $name;
			}
		}
		if (isset($items[''])) {
			unset($items['']);
		}
		ksort($items);
		cache_set($CACHE_NAME, $items);
		return $items;
	}

	/**
	* Internal method
	*/
	function _show_stpls_list ($files_array = array(), $level = 0) {
		asort($files_array);
		$body = array();
		foreach ((array)$files_array as $cur_file_path => $file_name) {
			if (false !== strpos($cur_file_path, '.svn')) {
				continue;
			}
			if (false !== strpos($cur_file_path, '.git')) {
				continue;
			}
			if (is_array($file_name)) {
				$body[$cur_file_path.'_dir'] = $this->_show_stpls_item($cur_file_path, $level, true);
				$body = array_merge($body, (array)$this->_show_stpls_list($file_name, $level + 1));
			} else {
				if (common()->get_file_ext($file_name) != 'stpl') {
					continue;
				}
				$body[$cur_file_path] = $this->_show_stpls_item($cur_file_path, $level);
			}
		}
		return $body;
	}

	/**
	* Internal method
	*/
	function _show_stpls_item ($file_path = '', $level = 0, $is_folder = false) {
		static $i, $j;
		$name = str_replace(array($this->_cur_theme_path.'/', '.stpl'), '', $file_path);
		if (substr($name, 0, 6) == 'images') {
			return false;
		}
		$replace = array(
			'name'			=> $is_folder ? '<b>'.$name.'</b>' : $name,
			'bg_class'		=> !($i++%2) ? 'bg1' : 'bg2',
			'num'			=> !$is_folder ? ++$j : '',
			'pad'			=> $level * 50/* + ($is_folder ? 20 : 0)*/, // In pixels
			'stpl_size'		=> !$is_folder ? filesize($file_path) : '',
			'edit_stpl_url'	=> './?object='.$_GET['object'].'&action='.($is_folder ? 'edit_dir' : 'edit_stpl').'&name='.$name.'&theme='.$this->theme_name.'&location='.$_GET['location'],
			'location'		=> $_GET['location'],
		);
		return tpl()->parse($_GET['object'].'/stpls_list_item', $replace);
	}

	/**
	*/
	function edit_stpl () {
		$theme_name	= $_GET['theme'];
		$stpl_name	= $_GET['name'];
		if (!validate(array($theme_name, $stpl_name), 'trim|required')) {
			return _e('Template name and theme required!');
		}
		if (main()->is_post()) {
			if (false !== strpos($_GET['location'], 'framework')) {
// TODO: use readonly mode with message and ability to save changed file inside project
				return $this->_framework_warning();
			}
			$lib_stpl_path	= $this->_dir_array[$_GET['location']]. $theme_name. '/'. $stpl_name. tpl()->_STPL_EXT;
			$text = $_POST['stpl_text'] ?: $_POST['stpl_text_hidden'];
			if (!file_exists($lib_stpl_path)) {
				_mkdir_m(dirname($lib_stpl_path));
			}
			file_put_contents($lib_stpl_path, $text);
			return js_redirect('./?object='.$_GET['object'].'&action=show_stpls_in_theme&theme='.$theme_name.'&location='.$_GET['location']);
		}
		$stpl_path = $this->_dir_array[$_GET['location']]. $theme_name. '/'. $stpl_name. tpl()->_STPL_EXT;
		if ($_GET['location'] == 'framework_user') {
			$stpl_path = YF_PATH. 'templates/user/'. $stpl_name. tpl()->_STPL_EXT;
		}
		if (!file_exists($stpl_path)) {
			return _e('Cannot find template: '.$stpl_path);
		}
		$stpl_text = file_get_contents($stpl_path);
		$stpl_text = _prepare_html($stpl_text, 0);
		$replace = array(
			'form_action'	=> './?object='.$_GET['object'].'&action='.$_GET['action'].'&name='.$stpl_name.'&theme='.$theme_name.'&location='.$_GET['location'],
			'theme_name'	=> $theme_name,
			'stpl_name'		=> $stpl_name,
			'stpl_text'		=> $stpl_text,
			'back_url'		=> './?object='.$_GET['object'].'&action=show_stpls_in_theme&theme='.$theme_name.'&location='.$_GET['location'],
			'location'		=> $_GET['location'],
		);
		$div_id = 'editor_html';
		$hidden_id = 'stpl_text_hidden';
		return '<h4>edit: '.$replace['stpl_name'].' for theme: '.$replace['theme_name'].', inside: '.$replace['location'].'</h4>'.
			form($replace, array(
				'data-onsubmit' => '$(this).find("#'.$hidden_id.'").val( $("#'.$div_id.'").data("ace_editor").session.getValue() );',
			))
			->container('<div id="'.$div_id.'" style="width: 90%; height: 500px;">'.$stpl_text.'</div>', '', array(
				'id'	=> $div_id,
				'wide'	=> 1,
				'ace_editor' => array('mode' => 'html'),
			))
			->hidden($hidden_id)
			->save_and_back();
	}

	/**
	* Show STPL template's content stored in file
	*/
	function show_file_src () {
		$path = base64_decode($_GET['path']);
		$stpl_text = file_get_contents($path);

		$replace = array(
			'stpl_text'	=> trim($stpl_text),
			'location'	=> $path,
		);
		return tpl()->parse($_GET['object'].'/view_content', $replace);
	}

	/**
	* Get themes full paths and names
	*/
	function _get_themes () {
		$themes = array();
		foreach ((array)$this->_dir_array as $k => $d){
			$dh = opendir($d);
// TODO: convert into _class('dir')
			while (false !== ($f = readdir($dh))) {
				$dir_name = $d.$f;
				if (false !== strpos($dir_name, '.svn')) {
					continue;
				}
				if (false !== strpos($dir_name, '.git')) {
					continue;
				}
				if (is_dir($dir_name) && $f != '.' && $f != '..') {
					$themes[$k][$d. $f. '/'] = $f;
				}
			}
			if ($dh) {
				closedir($dh);
			}
			if (is_array($themes[$k])) {
				ksort($themes[$k]);
			}
		}
		return $themes;
	}

	/**
	*/
	function _get_themes_names () {
		$names = array();
		foreach ((array)$this->_get_themes() as $where => $themes) {
			foreach ((array)$themes as $_path => $_name) {
				$names[$where][$_name] = $_name;
			}
		}
		return $names;
	}

	/**
	*/
	function _get_stpls_in_theme($theme_name, $theme_path) {
		foreach ((array)_class('dir')->scan_dir($theme_path. $theme_name) as $file_name) {
			if (common()->get_file_ext($file_name) != 'stpl') {
				continue;
			}
			$stpls[$file_name] = $file_name;
		}
		return $stpls;
	}

	/**
	*/
	function _get_themes_for_select() {
		$cache_name = 'themes_for_select';
		$data = cache_get($cache_name);
		if (!$data) {
			foreach ((array)$this->_get_themes_names() as $_location => $_themes) {
				foreach ((array)$_themes as $_theme) {
					$data[$_theme] = $_theme;
				}
			}
			cache_set($cache_name, $data);
		}
		return $data;
	}

	/**
	*/
	function _framework_warning () {
		return _e( tpl()->parse($_GET['object'].'/framework_warning') );
	}

	/**
	*/
	function _hook_settings(&$selected = array()) {
#		return array(
#			array('text', 'template_editor__ACE_THEME'),
#		);
	}
}
