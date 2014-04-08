<?php

/**
* Absttraction layer over HTML5/CSS frameworks.
* Planned support for these plugins: 
*	Bootstrap 2		http://twbs.github.io/bootstrap/2.3.2/
*	Bootstrap 3		http://twbs.github.io/bootstrap/3
*	Zurb Foundation	http://foundation.zurb.com/
*	Pure CSS		http://purecss.io/
*/
class yf_html {

	/**
	* Catch missing method call
	*/
	function __call($name, $arguments) {
		trigger_error(__CLASS__.': No method '.$name, E_USER_WARNING);
		return false;
	}

	/**
	* We cleanup object properties when cloning
	*/
	function __clone() {
		foreach ((array)get_object_vars($this) as $k => $v) {
			$this->$k = null;
		}
	}

	/**
	* Need to avoid calling render() without params
	*/
	function __toString() {
		return $this->render();
	}

	/**
	*/
	function _init() {
		$this->is_bs3 = (conf('css_framework') == 'bs3');
		$this->rnd = substr(md5(microtime()), 0, 8);
	}

	/**
	* Get and sort items ordered array (recursively)
	*/
	function _recursive_sort_items($items = array(), $skip_item_id = 0, $parent_id = 0) {
		$children = array();
		foreach ((array)$items as $id => $info) {
			$parent_id = (int)$info['parent_id'];
			if ($skip_item_id == $id) {
				continue;
			}
			$children[$parent_id][$id] = $id;
		}
		$ids = $this->_count_levels(0, $children);
		$new_items = array();
		foreach ((array)$ids as $id => $level) {
			$new_items[$id] = $items[$id] + array('level' => $level);
		}		
		return $new_items;
	}

	/**
	*/
	function _count_levels($start_id = 0, &$children, $level = 0) {
		$ids = array();
		foreach ((array)$children[$start_id] as $id => $_tmp) {
			$ids[$id] = $level;
			if (isset($children[$id])) {
				foreach ((array)$this->_count_levels($id, $children, $level + 1) as $_id => $_level) {
					$ids[$_id] = $_level;
				}
			}
		}
		return $ids;
	}

	/**
	* Wrapper for template engine
	* Example:
	*	return html()->dd_table(db()->get_2d('SELECT * FROM '.db('countries')));
	*/
	function chained_wrapper($params = array()) {
		$this->_chained_mode = true;
		$this->_params = $params;
		return $this;
	}

	/**
	*/
	function dd_table($replace = array(), $field_types = array(), $extra = array()) {
		if (DEBUG_MODE) {
			$ts = microtime(true);
		}
		$form = form($replace, array(
			'legend' => $replace['title'],
			'no_form' => 1,
			'dd_mode' => 1,
			'dd_class' => 'span6 col-lg-6',
		));
		foreach ((array)$replace as $name => $val) {
			$func = 'container';
			$_extra = array(
				'desc' => $name,
				'value' => $val,
			);
			$ft = $field_types[$name];
			if (isset($ft)) {
				if (is_array($ft)) {
					if (isset($ft['func'])) {
						$func = $ft['func'];
					}
					$_extra = (array)$ft + $_extra;
				} else {
					$func = $ft;
				}
			}
			$_extra += (array)$extra;
			// Callback to decide if we need to show this field or not
			if (isset($_extra['display_func']) && is_callable($_extra['display_func'])) {
				$_display_allowed = $_extra['display_func']($val, $_extra);
				if (!$_display_allowed) {
					continue;
				}
			}
			if ($func) {
				$form->$func($val, $_extra);
			}
		}
		$legend = $extra['legend'] ? '<legend>'._prepare_html(t($extra['legend'])).'</legend>' : '';
		$div_class = $extra['div_class'] ? $extra['div_class'] : 'span6 col-lg-6';
		if (DEBUG_MODE) {
			debug('dd_table[]', array(
				'fields'		=> $replace,
				'field_types'	=> $field_types,
				'extra'			=> $extra,
				'time'			=> round(microtime(true) - $ts, 5),
				'trace'			=> main()->trace_string(),
			));
		}
		return '<div class="row-fluid">'.$legend.'<div class="'.$div_class.'">'.$form.'</div></div>';
	}

	/**
	*/
	function modal ($extra = array()) {
		$def_style = $extra['inline'] ? 'position: relative; top: auto; left: auto; right: auto; margin: 0 auto 20px; z-index: 1; max-width: 100%;' : '';
		$extra['style'] = $extra['style'] ?: $def_style;
		return '
			<div class="modal" style="'.$extra['style'].'">
				<div class="modal-header">'
					.($extra['show_close'] ? '<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>' : '')
					.($extra['header'] ? '<h3>'.$extra['header'].'</h3>' : '')
				.'</div>
				<div class="modal-body">'.$extra['body'].'</div>'
				.($extra['footer'] ? '<div class="modal-footer">'.$extra['footer'].'</div>' : '')
			.'</div>';
	}

	/**
	*/
	function tabs ($tabs = array(), $extra = array()) {
		$headers = array();
		$items = array();
		foreach ((array)$tabs as $k => $v) {
			if (!is_array($v)) {
				$content = $v;
				$v = array();
			} else {
				$content = $v['content'];
			}
			$name = $v['name'] ?: $k;
			$desc = $v['desc'] ?: ucfirst(str_replace('_', ' ', $name));
			$id = $v['id'] ?: 'tab_'.$k;
			if (isset($extra['selected'])) {
				$is_active = ($extra['selected'] == $k);
			} else {
				$is_active = (++$i == 1);
			}
			$css_class = ($is_active || $extra['show_all']) ? 'active' : 'fade';
			if ($extra['class']) {
				$css_class .= ' '.$extra['class'];
			}
			$class_head = $v['class_head'] ?: $extra['class_head'];
			$class_body = $v['class_body'] ?: $extra['class_body'];
			if (!$extra['no_headers']) {
				$headers[] = '<li class="'.($is_active ? 'active' : ''). ($class_head ? ' '.$class_head : '').'"><a href="#'.$id.'" data-toggle="tab">'.t($desc).'</a></li>';
			}
			$items[] = '<div class="tab-pane '.$css_class. ($class_body ? ' '.$class_body : '').'" id="'.$id.'">'.$content.'</div>';
		}
		$extra['id'] = $extra['id'] ?: 'tabs_'.$this->rnd;
		$body .= $headers ? '<ul id="'.$extra['id'].'" class="nav nav-tabs">'.implode(PHP_EOL, (array)$headers). '</ul>'. PHP_EOL : '';
		$body .= '<div id="'.$extra['id'].'_content" class="tab-content">'. implode(PHP_EOL, (array)$items).'</div>';
		return $body;
	}

	/**
	*/
	function accordion ($data = array(), $extra = array()) {
		$items = array();
		$extra['id'] = $extra['id'] ?: __FUNCTION__.'_'.$this->rnd;
		foreach ((array)$data as $k => $v) {
			if (!is_array($v)) {
				$content = $v;
				$v = array();
			} else {
				$content = $v['body'];
			}
			$name = $v['name'] ?: $k;
			$desc = $v['desc'] ?: ucfirst(str_replace('_', ' ', $name));
			$id = $v['id'] ?: 'accordion_item_'.$k;
			if (isset($extra['selected'])) {
				$is_selected = ($extra['selected'] == $k);
			} else {
				$is_selected = (++$i == 1);
			}
			$class_group = $v['class_group'] ?: $extra['class_group'];
			$class_head = $v['class_head'] ?: $extra['class_head'];
			$class_body = $v['class_body'] ?: $extra['class_body'];

			$items[] = 
				'<div class="accordion-group panel panel-default'.($class_group ? ' '.$class_group : '').'">
					<div class="accordion-heading panel-heading'.($class_head ? ' '.$class_head : '').'">
						'.($this->is_bs3 ? '<h4 class="panel-title">' : '').'
						<a class="accordion-toggle" data-toggle="collapse" data-parent="#'.$extra['id'].'" href="#'.$id.'">'.$desc.'</a>
						'.($this->is_bs3 ? '</h4>' : '').'
					</div>
					<div id="'.$id.'" class="accordion-body panel-collapse collapse'.($is_selected ? ' in' : ''). ($class_body ? ' '.$class_body : '').'">
						<div class="accordion-inner panel-body">'.$content.'</div>
					</div>
				</div>';
		}
		return '<div class="panel-group accordion'.($extra['class'] ? ' '.$extra['class'] : '').'" id="'.$extra['id'].'">'.implode(PHP_EOL, (array)$items).'</div>';
	}

	/**
	*/
	function carousel ($data = array(), $extra = array()) {
		$items = array();
		$headers = array();
		$extra['id'] = $extra['id'] ?: __FUNCTION__.'_'.$this->rnd;
		foreach ((array)$data as $k => $v) {
			if (!is_array($v)) {
				$img_src = $v;
				$v = array();
			} else {
				$img_src = $v['img'];
			}
			$desc = $v['desc'];
			$alt = $v['alt'] ?: strip_tags($desc);
			$id = $v['id'] ?: 'carousel_item_'.$k;
			if (isset($extra['selected'])) {
				$is_active = ($extra['selected'] == $k);
			} else {
				$is_active = (++$i == 1);
			}
			$class_head = $v['class_head'] ?: $extra['class_head'];
			$class_body = $v['class_body'] ?: $extra['class_body'];

			$headers[] = '<li data-target="#'.$extra['id'].'" data-slide-to="'.($i - 1).'" class="'.($is_active ? 'active' : ''). ($class_head ? ' '.$class_head : '').'"></li>';
			$items[] = 
				'<div class="item'.($is_active ? ' active' : ''). ($class_body ? ' '.$class_body : '').'">
					<img src="'.$img_src.'" alt="'.$alt.'">
					'.($desc ? '<div class="carousel-caption">'.$desc.'</div>' : '').'
				</div>';
		}
		$controls = '
			<a class="left carousel-control" href="#'.$extra['id'].'" data-slide="prev"><span class="icon icon-chevron-left"></span></a>
			<a class="right carousel-control" href="#'.$extra['id'].'" data-slide="next"><span class="icon icon-chevron-right"></span></a>
		';
		return '<div id="'.$extra['id'].'" class="carousel slide'.($extra['class'] ? ' '.$extra['class'] : '').'" data-ride="carousel">
				<ol class="carousel-indicators">'.implode(PHP_EOL, $headers).'</ol>
				<div class="carousel-inner">'.implode(PHP_EOL, $items).'</div>
				'.(!$extra['no_controls'] ? $controls : '').'
			</div>';
	}

	/**
	*/
	function alert ($data = array(), $extra = array()) {
		$close_btn = (!$extra['no_close'] && !$data['no_close']) ? '<button type="button" class="close" data-dismiss="alert">×</button>' : '';
		$head = is_array($data) ? $data['head'] : '';
		$body = is_array($data) ? $data['body'] : $data;
		$alert_type = $extra['alert'] ?: (is_array($data) ? $data['alert'] : '');
		if (!$alert_type) {
			$alert_type = 'error';
		}
		return '
			<div class="alert alert-block alert-'.$alert_type.' fade in'.($extra['class'] ? ' '.$extra['class'] : '').'">
				'.$close_btn.'
				'.($head ? '<h4 class="alert-heading">'.$head.'</h4>' : '').'
				'.$body.'
			</div>';
	}

	/**
	*/
	function navbar ($data = array(), $extra = array()) {
		$items = array();
		$extra['id'] = $extra['id'] ?: __FUNCTION__.'_'.$this->rnd;
		$brand = '';
		if (isset($data['brand'])) {
			$b = $data['brand'];
			unset($data['brand']);
			$brand = '<a class="brand'.($b['class'] ? ' '.$b['class'] : '').'" href="'.$b['link'].'" title="'.$b['name'].'">'.$b['name'].'</a>';
		}
		$data = _prepare_html($data);
		foreach ((array)$data as $k => $v) {
			if (isset($extra['selected'])) {
				$is_selected = ($extra['selected'] == $k);
			} else {
				$is_selected = (++$i == 1);
			}
			$class_item = $v['class_item'] ?: $extra['class_item'];
			$items[] = '<li class="'.($is_selected ? ' active' : ''). ($class_item ? ' '.$class_item : '').'"><a href="'.$v['link'].'" title="'.$v['name'].'">'.$v['name'].'</a></li>';
		}
		return 
			'<div class="navbar'.($extra['class'] ? ' '.$extra['class'] : '').'" id="'.$extra['id'].'">
				<div class="navbar-inner">'
					.$brand
					.'<ul class="nav">'.implode(PHP_EOL, (array)$items).'</a>
				</div>
			</div>';
	}

	/**
	*/
	function breadcrumbs ($data = array(), $extra = array()) {
		$items = array();
		$extra['id'] = $extra['id'] ?: __FUNCTION__.'_'.$this->rnd;
		$divider = $extra['divider'] ?: '/';
		$len = count($data);
		$data = _prepare_html($data);
		foreach ((array)$data as $k => $v) {
			$is_last = (++$i == $len);
			$class_item = $v['class_item'] ?: $extra['class_item'];
			$items[] = '<li class="'.($is_last ? ' active' : ''). ($class_item ? ' '.$class_item : '').'">
				'.(($is_last || !$v['link']) ? $v['name'] 
					: '<a href="'.$v['link'].'" title="'.$v['name'].'">'.$v['name'].'</a>'.(!$this->is_bs3 ? ' <span class="divider">'.$divider.'</span>' : '')
				).'
			</li>';
		}
		$tag = $this->is_bs3 ? 'ol' : 'ul';
		return '<'.$tag.' class="breadcrumb'.($extra['class'] ? ' '.$extra['class'] : '').'" id="'.$extra['id'].'"">'.implode(PHP_EOL, (array)$items).'</'.$tag.'>';
	}

	/**
	*/
	function thumbnails ($data = array(), $extra = array()) {
		$items = array();
		$columns = (int)$extra['columns'] ?: 3;
		$row_class = 'span'.round(12 / $columns);
		foreach ((array)$data as $k => $v) {
			if (!is_array($v)) {
				$img_src = $v;
				$v = array();
			} else {
				$img_src = $v['img'];
			}
			$class_item = $v['class_item'] ?: $extra['class_item'];
			$items[] = 
				'<li class="'.$row_class. ($class_item ? ' '.$class_item : ''). ($v['style'] ? ' style="'.$v['style'].'"' : '').'">
					<div class="thumbnail">
						<img alt="'._prepare_html($v['alt'] ?: $v['head']).'" src="'._prepare_html($img_src).'" />
						'.(($v['head'] || $v['body']) ? '<div class="caption">'.($v['head'] ? '<h3>'._prepare_html($v['head']).'</h3>' : '').' '.$v['body'].'</div>' : '').'
					</div>
				</li>';
		}
		$body = array();
		foreach (array_chunk($items, $columns) as $_items) {
			$body[] = '<ul class="thumbnails'.($extra['class'] ? ' '.$extra['class'] : '').'">'.implode(PHP_EOL, (array)$_items).'</ul>';
		}
		return implode(PHP_EOL, $body);
	}

	/**
	*/
	function progress_bar ($data = array(), $extra = array()) {
		$extra['id'] = $extra['id'] ?: __FUNCTION__.'_'.$this->rnd;
		$items = array();
		foreach ((array)$data as $v) {
			if (!is_array($v)) {
				$val = $v;
				$v = array();
			} else {
				$val = $v['val'];
			}
			$type = $v['type'] ?: $extra['type'];
			$class_item = $v['class_item'] ?: $extra['class_item'];
			$items[] = '<div class="bar bar-'.$type. ($class_item ? ' '.$class_item : '').'" style="width: '.$val.'%;'.($v['style'] ? ' '.$v['style'] : '').'"></div>';
		}
		return '<div class="progress'.($extra['class'] ? ' '.$extra['class'] : '').'" id="'.$extra['id'].'">'.implode(PHP_EOL, (array)$items).'</div>';
	}

	/**
	*/
	function pagination ($data = array(), $extra = array()) {
		$extra['id'] = $extra['id'] ?: __FUNCTION__.'_'.$this->rnd;
		if (isset($data['prev'])) {
			$prev = $data['prev'];
			unset($data['prev']);
		}
		if (isset($data['next'])) {
			$next = $data['next'];
			unset($data['next']);
		}
		$items = array();
// TODO: auto-detect current page and need of first. last
		if ($prev) {
			$items[] = '<li><a href="'.$prev.'">'.t('Prev').'</a></li>';
		}
		foreach ((array)$data as $page => $link) {
			$items[] = '<li><a href="'.$link.'">'.$page.'</a></li>';
		}
		if ($next) {
			$items[] = '<li><a href="'.$next.'">'.t('Next').'</a></li>';
		}
		return '<div class="pagination'.($extra['class'] ? ' '.$extra['class'] : '').'" id="'.$extra['id'].'"><ul>'.implode(PHP_EOL, $items).'</ul></div>';
	}

	/**
	*/
	function panel ($data = array(), $extra = array()) {
		$extra['id'] = $extra['id'] ?: __FUNCTION__.'_'.$this->rnd;
		return 
			'<div class="panel panel-primary'.($extra['class'] ? ' '.$extra['class'] : '').'" id="'.$extra['id'].'">
				<div class="panel-heading">
					<h3 class="panel-title">'.$data['title'].'</h3>
				</div>
				<div class="panel-body">'.$data['body'].'</div>
			</div>';
	}

	/**
	*/
	function jumbotron ($data = array(), $extra = array()) {
		$extra['id'] = $extra['id'] ?: __FUNCTION__.'_'.$this->rnd;
		return '<div class="jumbotron'.($extra['class'] ? ' '.$extra['class'] : '').'" id="'.$extra['id'].'"><h1>'.$data['head'].'</h1>'.$data['body'].'</div>';
	}

	/**
	*/
	function well ($body = '', $extra = array()) {
		$extra['id'] = $extra['id'] ?: __FUNCTION__.'_'.$this->rnd;
		if (!$extra['class']) {
			$extra['class'] = 'well-lg';
		}
		return '<div class="well well-lg'.($extra['class'] ? ' '.$extra['class'] : '').'" id="'.$extra['id'].'">'.$body.'</div>';
	}

	/**
	*/
	function list_group ($data = array(), $extra = array()) {
		$extra['id'] = $extra['id'] ?: __FUNCTION__.'_'.$this->rnd;
		$items = array();
		foreach ((array)$data as $v) {
			if (!is_array($v)) {
				$body = $v;
				$v = array();
			} else {
				$body = $v['body'];
			}
			$type = $v['type'] ?: $extra['type'];
			$class_item = $v['class_item'] ?: $extra['class_item'];
			$items[] = '<li class="list-group-item'. ($class_item ? ' '.$class_item : '').'"><span class="badge">'.$v['badge'].'</span> '.$body.'</li>';
		}
		return '<ul class="list-group'.($extra['class'] ? ' '.$extra['class'] : '').'" id="'.$extra['id'].'">'.implode(PHP_EOL, (array)$items).'</ul>';
	}

	/**
	*/
	function media_objects ($data = array(), $extra = array()) {
		$extra['id'] = $extra['id'] ?: __FUNCTION__.'_'.$this->rnd;
		if ($data) {
			$data = $this->_recursive_sort_items($data);
		}
		$keys = array_keys($data);
		$keys_counter = array_flip($keys);
		$items = array();
		foreach ((array)$data as $id => $item) {
			$next_id = $keys[$keys_counter[$id] + 1];
			$next_item = $next_id ? $data[$next_id] : array();
			$close_num_levels = 1;
			if ($next_item) {
				$close_num_levels = $item['level'] - $next_item['level'] + 1;
				if ($close_num_levels < 0) {
					$close_num_levels = 0;
				}
			}
			$items[] = '
				<div class="media">
					<a class="pull-left" href="'.$item['link'].'"><img class="media-object" alt="'.$item['alt'].'" src="'.$item['img'].'"></a>
					<div class="media-body">
						<h4 class="media-heading">'.$item['head'].'</h4>'
						.$item['body'].'
			';
			if ($close_num_levels) {
				$items[] = str_repeat(PHP_EOL.'</div></div>'.PHP_EOL, $close_num_levels);
			}
		}
		return '<div class="media-objects'.($extra['class'] ? ' '.$extra['class'] : '').'" id="'.$extra['id'].'">'.implode(PHP_EOL, (array)$items).'</div>';
	}

	/**
	*/
	function menu ($data = array(), $extra = array()) {
		$extra['id'] = $extra['id'] ?: __FUNCTION__.'_'.$this->rnd;
		if ($data) {
			$data = $this->_recursive_sort_items($data);
		}
		$keys = array_keys($data);
		$keys_counter = array_flip($keys);
		$items = array();
		$ul_opened = false;
		foreach ((array)$data as $id => $item) {
			$next_item = $data[ $keys[$keys_counter[$id] + 1] ];
			$has_children = false;
			$close_li = 1;
			$close_ul = 0;
			if ($next_item) {
				if ($next_item['level'] > $item['level']) {
					$has_children = true;
				}
				$close_li = $item['level'] - $next_item['level'] + 1;
				if ($close_li < 0) {
					$close_li = 0;
				}
			}
			$items[] = '
				<li class="dropdown">
					<a href="'.$item['link'].'" class="dropdown-toggle">'. $item['name']. ($has_children ? ' <b class="caret"></b>' : ''). '</a>'
				;
			if ($has_children) {
				$ul_opened = true;
				$items[] = PHP_EOL. '<ul class="dropdown-menu sub-menu">'. PHP_EOL;
			} elseif ($close_li) {
				if ($ul_opened && !$has_children && $item['level'] != $next_item['level']) {
					$ul_opened = false;
					$close_ul = 1;
				}
				$tmp = str_repeat(PHP_EOL. ($close_ul ? '</li></ul>' : '</li>'). PHP_EOL, $close_li);
				if ($close_li > 1 && $close_ul) {
					$tmp = substr($tmp, 0, -strlen('</ul>'.PHP_EOL)). PHP_EOL;
				}
				$items[] = $tmp;
			}
		}
		return '<div class="navbar'.($extra['class'] ? ' '.$extra['class'] : '').'" id="'.$extra['id'].'">
					<div class="navbar-inner">
						<ul class="nav navbar-nav">'.implode(PHP_EOL, (array)$items).'</ul>
					</div>
				</div>';
	}

	/**
	*/
	function grid ($data = array(), $extra = array()) {
		$extra['id'] = $extra['id'] ?: __FUNCTION__.'_'.$this->rnd;
		$rows = array();
		$ul_opened = false;
		foreach ((array)$data as $id => $row) {
			$items = array();
			$row_col = count($row) ? floor(12 / count($row)) : 1;
			if ($row_col < 1) {
				$row_col = 1;
			}
			foreach ((array)$row as $rid => $item) {
				$body = $item[0] ?: $item['body'];
				$col = $item[1] ?: $item['col'];
				$class = $item['class'];
				if (!$col) {
					$col = $row_col;
				}
				$items[] = '<div class="span'.$col.' col-lg-'.$col.($class ? ' '.$class : '').'">'.$body.'</div>';
			}
			$rows[] = '<div class="row-fluid show-grid">'.implode(PHP_EOL, $items).'</div>';
		}
		return '<div class="grid'.($extra['class'] ? ' '.$extra['class'] : '').'" id="'.$extra['id'].'">'.implode(PHP_EOL, (array)$rows).'</div>';
	}
}
